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

class TrimManipulator implements ManipulatorInterface
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
    private $field;

    /**
     * @param Logger $logger
     * @param string $field
     * @param Json $serializer
     * @param DotConvention $dotConvention
     */
    public function __construct(
        Logger $logger,
        Json $serializer,
        DotConvention $dotConvention,
        string $field
    ) {
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->dotConvention = $dotConvention;
        $this->field = $field;
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
        $downloaderIdentifier = $this->dotConvention->getFirst($this->field);
        $identifiers = $this->dotConvention->getFromSecondInDotConvention($this->field);

        if (!array_key_exists($downloaderIdentifier, $entities)) {
            throw new TransporterException(__('Invalid downloaderIdentifier for class %1', self::class));
        }

        $entity = $entities[$downloaderIdentifier];
        $data = $entity->getDataManipulated();
        $data = $this->serializer->unserialize($data);

        $field = (string)$this->dotConvention->getValue($data, $identifiers);
        $field = trim($field);

        $this->dotConvention->setValue($data, $identifiers, $field);

        $data = $this->serializer->serialize($data);
        $entity->setDataManipulated($data);
    }
}
