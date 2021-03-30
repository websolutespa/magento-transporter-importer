<?php
/*
 * Copyright © Websolute spa. All rights reserved.
 * See LICENSE and/or COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Websolute\TransporterImporter\Uploader\Mappings\Json;

use Websolute\TransporterImporter\Api\Uploader\Mapping\MappingTypeInterface;
use Websolute\TransporterImporter\Model\DotConvention;
use Websolute\TransporterBase\Exception\TransporterException;

class ImplodePath implements MappingTypeInterface
{
    /**
     * @var string
     */
    private $head;

    /**
     * @var string
     */
    private $path;

    /**
     * @var DotConvention
     */
    private $dotConvention;

    /**
     * @var string
     */
    private $separator;

    /**
     * @param string $head
     * @param string $path
     * @param string $separator
     * @param DotConvention $dotConvention
     */
    public function __construct(
        DotConvention $dotConvention,
        string $head,
        string $path,
        string $separator = ','
    ) {
        $this->dotConvention = $dotConvention;
        $this->head = $head;
        $this->path = $path;
        $this->separator = $separator;
    }

    /**
     * @param array $data
     * @return string
     * @throws TransporterException
     */
    public function execute(array $data): string
    {
        $identifiers = $this->dotConvention->getAll($this->path);

        $results = [];
        $result = '';
        $value = $data;
        end($identifiers);
        $lastKey = key($identifiers);
        foreach ($identifiers as $key => $identifier) {
            if (!array_key_exists($identifier, $value)) {
                throw new TransporterException(__('Non existing field %1', $this->path));
            }
            $value = $value[$identifier];

            if ($lastKey === $key) {
                $results = $value;
            }
        }

        foreach ($results as $key => $value) {
            $result .= $this->separator . $key . '=' . $value;
        }

        $result = ltrim($result, $this->separator);
        return $result;
    }

    /**
     * @return string
     */
    public function getHead(): string
    {
        return $this->head;
    }
}
