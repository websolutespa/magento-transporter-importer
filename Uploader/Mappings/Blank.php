<?php
/*
 * Copyright © Websolute spa. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Websolute\TransporterImporter\Uploader\Mappings;

use Websolute\TransporterImporter\Api\Uploader\Mapping\MappingTypeInterface;

class Blank implements MappingTypeInterface
{
    /**
     * @var string
     */
    private $head;

    /**
     * @param string $head
     */
    public function __construct(
        string $head
    ) {
        $this->head = $head;
    }

    /**
     * @param array $data
     * @return string
     */
    public function execute(array $data): string
    {
        return '';
    }

    /**
     * @return string
     */
    public function getHead(): string
    {
        return $this->head;
    }
}
