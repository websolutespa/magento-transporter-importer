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

class MandatoryAutoFillManipulator implements ManipulatorInterface
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
     * @var string
     */
    private $value;

    /**
     * @param Logger $logger
     * @param Json $serializer
     * @param DotConvention $dotConvention
     * @param string $destination
     * @param string $value
     */
    public function __construct(
        Logger $logger,
        Json $serializer,
        DotConvention $dotConvention,
        string $destination,
        string $value
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
        $identifiers = $this->dotConvention->getFromSecond($this->destination);

        if (!array_key_exists($downloaderIdentifier, $entities)) {
            throw new TransporterException(__('Invalid downloaderIdentifier for class %1', self::class));
        }

        $entity = $entities[$downloaderIdentifier];
        $data = $entity->getDataManipulated();
        $data = $this->serializer->unserialize($data);

        $field = &$data;

        foreach ($identifiers as $key => $identifier) {
            if ($identifier === '*' && is_array($field)) {
                $subFields = &$field;
                $remainingIdentifiers = array_slice($identifiers, array_search($identifier, $identifiers) + 1);
                $remainingIdentifiers = $this->dotConvention->serialize($remainingIdentifiers);
                foreach ($subFields as &$subField) {
                    $val = $this->dotConvention->getValue($subField, $remainingIdentifiers);

                    if (is_null($val) || $val === '') {
                        $this->dotConvention->setValue($subField, $remainingIdentifiers, $this->value);
                    }
                }
                break;
            }
            if (!array_key_exists($identifier, $field)) {
                if ($key < (count($identifiers) - 1)) {
                    $field[$identifier] = [];
                } else {
                    $field[$identifier] = '';
                }
            }
            $field = &$field[$identifier];
        }

        if (is_null($field) || $field === '') {
            $field = $this->value;
        }

        $data = $this->serializer->serialize($data);
        $entity->setDataManipulated($data);
    }
}
