<?php
/*
 * Copyright © Websolute spa. All rights reserved.
 * See LICENSE and/or COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Websolute\TransporterImporter\Uploader;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\File\Csv as File;
use Magento\Framework\Filesystem\DirectoryList;
use Monolog\Logger;
use Websolute\TransporterBase\Api\TransporterConfigInterface;
use Websolute\TransporterBase\Api\UploaderInterface;
use Websolute\TransporterBase\Exception\TransporterException;
use Websolute\TransporterEntity\Api\EntityRepositoryInterface;
use Websolute\TransporterImporter\Api\Uploader\Mapping\MappingTypeInterface;
use Websolute\TransporterImporter\Uploader\Csv\GetHeadersFromMappings;

class CsvUploader implements UploaderInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var string
     */
    private $fileName;

    /**
     * @var string
     */
    private $filePath;

    /**
     * @var MappingTypeInterface[]
     */
    private $mappings;

    /**
     * @var EntityRepositoryInterface
     */
    private $entityRepository;

    /**
     * @var File
     */
    private $csv;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var GetHeadersFromMappings
     */
    private $getHeadersFromMappings;

    /**
     * @var TransporterConfigInterface
     */
    private $config;

    /**
     * @var bool
     */
    private $addActivityIdToPath;

    /**
     * @param Logger $logger
     * @param string $fileName
     * @param string $filePath
     * @param array $mappings
     * @param EntityRepositoryInterface $entityRepository
     * @param File $csv
     * @param DirectoryList $directoryList
     * @param GetHeadersFromMappings $getHeadersFromMappings
     * @param TransporterConfigInterface $config
     * @param bool $addActivityIdToPath
     * @throws TransporterException
     */
    public function __construct(
        Logger $logger,
        string $fileName,
        string $filePath,
        array $mappings,
        EntityRepositoryInterface $entityRepository,
        File $csv,
        DirectoryList $directoryList,
        GetHeadersFromMappings $getHeadersFromMappings,
        TransporterConfigInterface $config,
        bool $addActivityIdToPath = false
    ) {
        $this->logger = $logger;
        $this->fileName = $fileName;
        $this->filePath = $filePath;
        $this->mappings = $mappings;
        foreach ($mappings as $mapping) {
            if (!$mapping instanceof MappingTypeInterface) {
                throw new TransporterException(__("Invalid type for mappings"));
            }
        }

        $this->entityRepository = $entityRepository;
        $this->csv = $csv;
        $this->directoryList = $directoryList;
        $this->getHeadersFromMappings = $getHeadersFromMappings;
        $this->config = $config;
        $this->addActivityIdToPath = $addActivityIdToPath;
    }

    /**
     * @param int $activityId
     * @param string $uploaderType
     * @throws TransporterException
     */
    public function execute(int $activityId, string $uploaderType): void
    {
        try {
            $file = $this->getFileNameWithPath($activityId);
            $this->emptyFileAndWriteHeaders($file);
        } catch (FileSystemException $e) {
            throw new TransporterException(__(
                'activityId:%1 ~ Uploader ~ uploaderType:%2 ~ ERROR ~ error:%3',
                $activityId,
                $uploaderType,
                $e->getMessage()
            ));
        }

        $allActivityEntities = $this->entityRepository->getAllDataManipulatedByActivityIdGroupedByIdentifier($activityId);
        foreach ($allActivityEntities as $entityIdentifier => $entities) {
            $this->logger->info(__(
                'activityId:%1 ~ Uploader ~ uploaderType:%2 ~ entityIdentifier:%3 ~ START',
                $activityId,
                $uploaderType,
                $entityIdentifier
            ));

            try {
                $this->appendChildrenFirst($file, $entities);
            } catch (FileSystemException $e) {
                $this->logger->error(__(
                    'activityId:%1 ~ Uploader ~ uploaderType:%2 ~ entityIdentifier:%3 ~ ERROR ~ error:%4',
                    $activityId,
                    $uploaderType,
                    $entityIdentifier,
                    $e->getMessage()
                ));

                if (!$this->config->continueInCaseOfErrors()) {
                    throw new TransporterException(__(
                        'activityId:%1 ~ Uploader ~ uploaderType:%2 ~ entityIdentifier:%3 ~ END ~ Because of continueInCaseOfErrors = false',
                        $activityId,
                        $uploaderType,
                        $entityIdentifier
                    ));
                }
            }

            $this->logger->info(__(
                'activityId:%1 ~ Uploader ~ uploaderType:%2 ~ entityIdentifier:%3 ~ END',
                $activityId,
                $uploaderType,
                $entityIdentifier
            ));
        }
    }

    /**
     * @param int $activityId
     * @return string
     * @throws FileSystemException
     */
    private function getFileNameWithPath(int $activityId)
    {
        $fileName = $this->fileName . '_' . (string)$activityId . '_01.csv';
        $directory = $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        $path = $directory . DIRECTORY_SEPARATOR . $this->filePath . DIRECTORY_SEPARATOR;
        if ($this->addActivityIdToPath) {
            $path .= $activityId . DIRECTORY_SEPARATOR;
            @mkdir($path, 0755, true);
        }
        $path .= $fileName;
        return $path;
    }

    /**
     * @param string $file
     * @throws FileSystemException
     */
    private function emptyFileAndWriteHeaders(string $file)
    {
        $headers = [$this->getHeadersFromMappings->execute($this->mappings)];
        $this->csv->appendData($file, $headers, 'w+');
    }

    /**
     * @param string $file
     * @param array $entities
     */
    protected function appendChildrenFirst(string $file, array $entities)
    {
    }

    /**
     * @param array $entities
     * @return array[]
     * @throw TransporterException
     */
    protected function getData(array $entities): array
    {
        $data = [];
        foreach ($this->mappings as $mapping) {
            $data[] = $mapping->execute($entities);
        }
        return [$data];
    }

    /**
     * @param string $file
     * @param array $data
     * @throws FileSystemException
     */
    protected function appendToFile(string $file, array $data)
    {
        $this->csv->appendData($file, $data, 'a+');
    }
}
