<?php
/*
 * Copyright © Websolute spa. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Websolute\TransporterImporter\Uploader\Csv;

use Websolute\TransporterImporter\Api\Uploader\Mapping\MappingTypeInterface;

class GetHeadersFromMappings
{
    /**
     * @param MappingTypeInterface[] $mappings
     * @return string[]
     */
    public function execute(array $mappings)
    {
        $headers = [];
        foreach ($mappings as $mapping) {
            $headers[] = $mapping->getHead();
        }
        return $headers;
    }
}
