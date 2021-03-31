<?php
/*
 * Copyright © Websolute spa. All rights reserved.
 * See LICENSE and/or COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Websolute\TransporterImporter\Manipulator\Json;

use Magento\Framework\Serialize\Serializer\Json;
use Monolog\Logger;
use Websolute\TransporterBase\Api\ManipulatorInterface;
use Websolute\TransporterBase\Exception\TransporterException;
use Websolute\TransporterEntity\Api\Data\EntityInterface;
use Websolute\TransporterImporter\Model\DotConvention;

class BooleanManipulator implements ManipulatorInterface
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
     * @var bool
     */
    private $mandatory;

    /**
     * @param Logger $logger
     * @param Json $serializer
     * @param DotConvention $dotConvention
     * @param string $field
     * @param bool $mandatory
     */
    public function __construct(
        Logger $logger,
        Json $serializer,
        DotConvention $dotConvention,
        string $field,
        bool $mandatory = true
    ) {
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->dotConvention = $dotConvention;
        $this->field = $field;
        $this->mandatory = $mandatory;
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
        $identifiers = $this->dotConvention->getFromSecond($this->field);

        if (!array_key_exists($downloaderIdentifier, $entities)) {
            if ($this->mandatory) {
                throw new TransporterException(__('Invalid downloaderIdentifier for class %1', self::class));
            } else {
                return;
            }
        }

        $entity = $entities[$downloaderIdentifier];
        $data = $entity->getDataManipulated();
        $data = $this->serializer->unserialize($data);

        $field = &$data;

        foreach ($identifiers as $identifier) {
            if (!array_key_exists($identifier, $field)) {
                return;
            }
            $field = &$field[$identifier];
        }

        if ($field !== null) {
            $field = boolval($field);
        }

        $data = $this->serializer->serialize($data);
        $entity->setDataManipulated($data);
    }
}
