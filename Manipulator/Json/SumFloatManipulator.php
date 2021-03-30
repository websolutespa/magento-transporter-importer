<?php
/*
 * Copyright © Websolute spa. All rights reserved.
 * See LICENSE and/or COPYING.txt for license details.
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

class SumFloatManipulator implements ManipulatorInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @var DotConvention
     */
    private $dotConvention;

    /**
     * @var string
     */
    private $destination;

    /**
     * @var string[]
     */
    private $paths;

    /**
     * @param Logger $logger
     * @param Json $serializer
     * @param DotConvention $dotConvention
     * @param string $destination
     * @param string[] $paths
     */
    public function __construct(
        Logger $logger,
        Json $serializer,
        DotConvention $dotConvention,
        string $destination,
        array $paths
    ) {
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->dotConvention = $dotConvention;
        $this->destination = $destination;
        $this->paths = $paths;
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
        $downloaderIdentifier = $this->dotConvention->getFirst($this->destination);

        $entity = $entities[$downloaderIdentifier];
        $data = $entity->getDataManipulated();
        $data = $this->serializer->unserialize($data);

        if (!array_key_exists($downloaderIdentifier, $entities)) {
            throw new TransporterException(__('Invalid downloaderIdentifier for class ', self::class));
        }

        $value = $this->getValue($entities);
        $destination = $this->dotConvention->getFromSecondInDotConvention($this->destination);
        $this->dotConvention->setValue($data, $destination, $value);

        $data = $this->serializer->serialize($data);
        $entity->setDataManipulated($data);
    }

    /**
     * @param array $entities
     * @return float
     * @throws TransporterException
     */
    private function getValue(array $entities): float
    {
        $value = 0.0;

        foreach ($this->paths as $path) {
            $downloaderIdentifier = $this->dotConvention->getFirst($path);
            $entity = $entities[$downloaderIdentifier];
            $data = $entity->getDataManipulated();
            $data = $this->serializer->unserialize($data);

            if (!array_key_exists($downloaderIdentifier, $entities)) {
                throw new TransporterException(__('Invalid downloaderIdentifier for class ', self::class));
            }

            $pathIdentifier = $this->dotConvention->getFromSecondInDotConvention($path);
            $pathValue = $this->dotConvention->getValue($data, $pathIdentifier);
            $value += (float)$pathValue;
        }

        return $value;
    }
}
