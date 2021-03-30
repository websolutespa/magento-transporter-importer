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

class CopyToChildrenManipulator implements ManipulatorInterface
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
    private $childrenOption;

    /**
     * @var string
     */
    private $pathAttributeWhereCopy;

    /**
     * @var string
     */
    private $field;

    /**
     * @param Logger $logger
     * @param Json $serializer
     * @param DotConvention $dotConvention
     * @param string $childrenOption
     * @param string $pathAttributeWhereCopy
     * @param string $field
     */
    public function __construct(
        Logger $logger,
        Json $serializer,
        DotConvention $dotConvention,
        string $childrenOption,
        string $pathAttributeWhereCopy,
        string $field
    ) {
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->dotConvention = $dotConvention;
        $this->childrenOption = $childrenOption;
        $this->pathAttributeWhereCopy = $pathAttributeWhereCopy;
        $this->field = $field;
    }

    /**
     * @param int $activityId
     * @param string $manipulatorType
     * @param string $entityIdentifier
     * @param EntityInterface[] $entities
     * @throws TransporterException
     * @throws TransporterException
     */
    public function execute(
        int $activityId,
        string $manipulatorType,
        string $entityIdentifier,
        array $entities
    ): void {
        $downloaderIdentifier = $this->dotConvention->getFirst($this->pathAttributeWhereCopy);

        $identifier = $this->dotConvention->getFromSecondInDotConvention($this->pathAttributeWhereCopy);
        if (!array_key_exists($downloaderIdentifier, $entities)) {
            throw new TransporterException(__('Invalid downloaderIdentifier for class %1', self::class));
        }

        $entity = $entities[$downloaderIdentifier];
        $data = $entity->getDataManipulated();
        $data = $this->serializer->unserialize($data);

        //for childrens
        $options = $data[$this->childrenOption];
        foreach ($options as $key => $option) {
            if (!isset($data[$this->childrenOption][$key][$this->field])) {

                if (!isset($data[$identifier])) {
                    $value = $this->dotConvention->getValue($data, $identifier);
                } else {
                    $value = $data[$identifier];
                }

                $data[$this->childrenOption][$key][$this->field] = $value;
            }
        }

        $data = $this->serializer->serialize($data);
        $entity->setDataManipulated($data);
    }
}
