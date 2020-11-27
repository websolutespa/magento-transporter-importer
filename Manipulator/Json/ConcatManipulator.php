<?php
/*
 * Copyright © Websolute spa. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Websolute\TransporterImporter\Manipulator\Json;

use Exception;
use Magento\Framework\Serialize\Serializer\Json;
use Monolog\Logger;
use Websolute\TransporterImporter\Model\DotConvention;
use Websolute\TransporterBase\Api\ManipulatorInterface;
use Websolute\TransporterBase\Exception\TransporterException;
use Websolute\TransporterEntity\Api\Data\EntityInterface;

class ConcatManipulator implements ManipulatorInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var string
     */
    private $destination;

    /**
     * @var string[]
     */
    private $paths;

    /**
     * @var string
     */
    private $glue;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @var DotConvention
     */
    private $dotConvention;

    /**
     * @param Logger $logger
     * @param string $destination
     * @param string[] $paths
     * @param string $glue
     * @param Json $serializer
     * @param DotConvention $dotConvention
     */
    public function __construct(
        Logger $logger,
        string $destination,
        array $paths,
        string $glue,
        Json $serializer,
        DotConvention $dotConvention
    ) {
        $this->logger = $logger;
        $this->destination = $destination;
        $this->paths = $paths;
        $this->glue = $glue;
        $this->serializer = $serializer;
        $this->dotConvention = $dotConvention;
    }

    /**
     * @param int $activityId
     * @param string $manipulatorType
     * @param string $entityIdentifier
     * @param EntityInterface[] $entities
     * @throws TransporterException
     */
    public function execute(int $activityId, string $manipulatorType, string $entityIdentifier, array $entities): void
    {
        $value = $this->getValue($entities);

        $downloaderIdentifier = $this->dotConvention->getFirst($this->destination);

        $entity = $entities[$downloaderIdentifier];
        $data = $entity->getDataManipulated();
        $data = $this->serializer->unserialize($data);

        if (!array_key_exists($downloaderIdentifier, $entities)) {
            throw new TransporterException(__('Invalid downloaderIdentifier for class ', self::class));
        }

        $field = &$data;

        $identifiers = $this->dotConvention->getFromSecond($this->destination);
        foreach ($identifiers as $key => $identifier) {
            if (!array_key_exists($identifier, $field)) {
                if ($key < (count($identifiers) - 1)) {
                    $field[$identifier] = [];
                } else {
                    $field[$identifier] = '';
                }
            }
            $field = &$field[$identifier];
        }

        $field = $value;

        $data = $this->serializer->serialize($data);
        $entity->setDataManipulated($data);
    }

    /**
     * @param array $entities
     * @return string
     * @throws TransporterException
     */
    private function getValue(array $entities): string
    {
        $value = '';

        foreach ($this->paths as $path) {
            $downloaderIdentifier = $this->dotConvention->getFirst($path);
            $entity = $entities[$downloaderIdentifier];
            $data = $entity->getDataManipulated();
            $data = $this->serializer->unserialize($data);

            if (!array_key_exists($downloaderIdentifier, $entities)) {
                throw new TransporterException(__('Invalid downloaderIdentifier for class ', self::class));
            }

            $identifiers = $this->dotConvention->getFromSecond($path);

            $pathValue = $data;
            foreach ($identifiers as $identifier) {
                if (!array_key_exists($identifier, $pathValue)) {
                    throw new TransporterException(__('Non existing identifier %1', $path));
                }
                $pathValue = $pathValue[$identifier];
            }
            $value .= $pathValue . $this->glue;
        }

        return (string)substr($value, 0, -strlen($this->glue));
    }
}
