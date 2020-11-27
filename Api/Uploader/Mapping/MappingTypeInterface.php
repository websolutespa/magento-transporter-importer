<?php
/*
 * Copyright © Websolute spa. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Websolute\TransporterImporter\Api\Uploader\Mapping;

interface MappingTypeInterface
{
    /**
     * @return string
     */
    public function getHead(): string;

    /**
     * @param array $data
     * @return string
     */
    public function execute(array $data): string;
}
