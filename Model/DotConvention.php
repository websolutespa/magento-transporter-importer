<?php
/*
 * Copyright © Websolute spa. All rights reserved.
 * See LICENSE and/or COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Websolute\TransporterImporter\Model;

use Magento\Framework\Serialize\Serializer\Json;
use Websolute\TransporterBase\Exception\TransporterException;

class DotConvention
{
    /**
     * @var Json
     */
    private $serializer;

    /**
     * @param Json $serializer
     */
    public function __construct(
        Json $serializer
    ) {
        $this->serializer = $serializer;
    }

    /**
     * @param array $data
     * @param string $path
     * @param $value
     * @throws TransporterException
     */
    public function setValue(array &$data, string $path, $value): void
    {
        $field = &$data;

        $identifiers = explode('.', $path);

        foreach ($identifiers as $key => $identifier) {
            if ($identifier === '*' && is_array($field)) {
                $subFields = &$field;
                $remainingIdentifiers = array_slice($identifiers, array_search($identifier, $identifiers) + 1);
                $remainingIdentifiers = $this->serialize($remainingIdentifiers);
                foreach ($subFields as &$subField) {
                    $this->setValue($subField, $remainingIdentifiers, $value);
                }
                return;
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

        $field = $value;
    }

    /**
     * @param array $values
     * @return string
     */
    public function serialize(array $values): string
    {
        return implode('.', $values);
    }

    /**
     * @param array $data
     * @param string $path
     * @return mixed
     * @throws TransporterException
     */
    public function getValue(array $data, string $path)
    {
        $identifier = $this->getFirst($path);

        if (!array_key_exists($identifier, $data)) {
            $serializeData = $this->serializer->serialize($data);
            throw new TransporterException(
                __('Invalid identifier/path: %1 into provided data: %2', $path, $serializeData)
            );
        }

        $value = $data[$identifier];

        $identifiers = $this->getFromSecond($path);

        foreach ($identifiers as $identifier) {
            if (!array_key_exists($identifier, $value)) {
                $serializeData = $this->serializer->serialize($data);
                throw new TransporterException(__('Invalid identifier/path: %1 into provided data: %2', $path, $serializeData));
            }
            $value = $value[$identifier];
        }

        return $value;
    }

    /**
     * @param string $path
     * @return string
     */
    public function getFirst(string $path): string
    {
        $values = $this->getAll($path);

        return (string)array_shift($values);
    }

    /**
     * @param string $path
     * @return string[]
     */
    public function getAll(string $path): array
    {
        return explode('.', $path);
    }

    /**
     * @param string $path
     * @return array
     */
    public function getFromSecond(string $path): array
    {
        $values = $this->getAll($path);

        array_shift($values);

        return $values;
    }

    /**
     * @param array $data
     * @param string $path
     * @return mixed
     * @throws TransporterException
     */
    public function getValueFromSecond(array $data, string $path)
    {
        $identifiers = $this->getFromSecond($path);

        $value = $data;

        foreach ($identifiers as $identifier) {
            if (!array_key_exists($identifier, $value)) {
                $serializeData = $this->serializer->serialize($data);
                throw new TransporterException(__('Invalid identifier/path: %1 into provided data: %2', $path, $serializeData));
            }
            $value = $value[$identifier];
        }

        return $value;
    }

    /**
     * @param string $path
     * @return string
     */
    public function getFromSecondInDotConvention(string $path): string
    {
        $values = $this->getAll($path);

        array_shift($values);

        return implode('.', $values);
    }
}
