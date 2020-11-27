<?php
/*
 * Copyright © Websolute spa. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Websolute\TransporterImporter\Uploader\Mappings\Json;

use Websolute\TransporterImporter\Api\Uploader\Mapping\MappingTypeInterface;
use Websolute\TransporterImporter\Model\DotConvention;
use Websolute\TransporterBase\Exception\TransporterException;

class Path implements MappingTypeInterface
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
     * @param string $head
     * @param string $path
     * @param DotConvention $dotConvention
     */
    public function __construct(
        string $head,
        string $path,
        DotConvention $dotConvention
    ) {
        $this->head = $head;
        $this->path = $path;
        $this->dotConvention = $dotConvention;
    }

    /**
     * @param array $data
     * @return string
     * @throws TransporterException
     */
    public function execute(array $data): string
    {
        $identifiers = $this->dotConvention->getAll($this->path);

        $value = $data;
        foreach ($identifiers as $identifier) {
            if (!array_key_exists($identifier, $value)) {
                throw new TransporterException(__('Non existing field %1', $this->path));
            }
            $value = $value[$identifier];
        }

        return (string)$value;
    }

    /**
     * @return string
     */
    public function getHead(): string
    {
        return $this->head;
    }
}
