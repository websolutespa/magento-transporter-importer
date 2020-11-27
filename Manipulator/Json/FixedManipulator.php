<?php
/*
 * Copyright © Websolute spa. All rights reserved.
 * See COPYING.txt for license details.
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
     * @var string
     */
    private $destination;

    /**
     * @var string
     */
    private $value;

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
     * @param string $value
     * @param Json $serializer
     * @param DotConvention $dotConvention
     */
    public function __construct(
        Logger $logger,
        string $destination,
        string $value,
        Json $serializer,
        DotConvention $dotConvention
    ) {
        $this->logger = $logger;
        $this->destination = $destination;
        $this->value = $value;
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
            if (!array_key_exists($identifier, $field)) {
                if ($key < (count($identifiers) - 1)) {
                    $field[$identifier] = [];
                } else {
                    $field[$identifier] = '';
                }
            }
            $field = &$field[$identifier];
        }

        $field = $this->value;

        $data = $this->serializer->serialize($data);
        $entity->setDataManipulated($data);
    }
}
