<?php
/*
 * Copyright © Websolute spa. All rights reserved.
 * See LICENSE and/or COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Websolute\TransporterImporter\Manipulator\Json;

use Magento\Framework\Serialize\Serializer\Json;
use Monolog\Logger;
use Websolute\TransporterImporter\Model\DotConvention;
use Websolute\TransporterBase\Api\ManipulatorInterface;
use Websolute\TransporterBase\Exception\TransporterException;
use Websolute\TransporterEntity\Api\Data\EntityInterface;

class FixedManipulator implements ManipulatorInterface
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
     * @var mixed
     */
    private $value;

    /**
     * @param Logger $logger
     * @param Json $serializer
     * @param DotConvention $dotConvention
     * @param string $destination
     * @param $value
     */
    public function __construct(
        Logger $logger,
        Json $serializer,
        DotConvention $dotConvention,
        string $destination,
        $value
    ) {
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->dotConvention = $dotConvention;
        $this->destination = $destination;
        $this->value = $value;
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
        $identifiers = $this->dotConvention->getFromSecondInDotConvention($this->destination);

        if (!array_key_exists($downloaderIdentifier, $entities)) {
            throw new TransporterException(__('Invalid downloaderIdentifier for class %1', self::class));
        }

        $entity = $entities[$downloaderIdentifier];
        $data = $entity->getDataManipulated();
        $data = $this->serializer->unserialize($data);

        $this->dotConvention->setValue($data, $identifiers, $this->value);

        $data = $this->serializer->serialize($data);
        $entity->setDataManipulated($data);
    }
}
