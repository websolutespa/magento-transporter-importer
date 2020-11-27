<?php
/*
 * Copyright © Websolute spa. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Websolute\TransporterImporter\Uploader;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\File\Csv as File;
use Magento\Framework\Filesystem\DirectoryList;
use Monolog\Logger;
use Websolute\TransporterImporter\Api\Uploader\Mapping\MappingTypeInterface;
use Websolute\TransporterImporter\Model\Config;
use Websolute\TransporterImporter\Uploader\Csv\GetHeadersFromMappings;
use Websolute\TransporterBase\Api\UploaderInterface;
use Websolute\TransporterBase\Exception\TransporterException;
use Websolute\TransporterEntity\Api\EntityRepositoryInterface;

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
     * @var Config
     */
    private $config;

    /**
     * @param Logger $logger
     * @param string $fileName
     * @param string $filePath
     * @param array $mappings
     * @param EntityRepositoryInterface $entityRepository
     * @param File $csv
     * @param DirectoryList $directoryList
     * @param GetHeadersFromMappings $getHeadersFromMappings
     * @param Config $config
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
        Config $config
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
                $data = $this->getData($entities);
                $this->appendToFile($file, $data);
            } catch (FileSystemException $e) {
                if ($this->config) {
                    $this->logger->error(__(
                        'activityId:%1 ~ Uploader ~ uploaderType:%2 ~ entityIdentifier:%3 ~ ERROR ~ error:%4',
                        $activityId,
                        $uploaderType,
                        $entityIdentifier,
                        $e->getMessage()
                    ));
                }
                continue;
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
        return $directory . DIRECTORY_SEPARATOR . $this->filePath . DIRECTORY_SEPARATOR . $fileName;
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
     * @param array $entities
     * @return array[]
     */
    private function getData(array $entities): array
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
    private function appendToFile(string $file, array $data)
    {
        $this->csv->appendData($file, $data, 'a+');
    }
}
