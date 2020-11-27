<?php
/*
 * Copyright © Websolute spa. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Websolute\TransporterImporter\Uploader\Mappings;

use Websolute\TransporterImporter\Api\Uploader\Mapping\MappingTypeInterface;

class Fixed implements MappingTypeInterface
{
    /**
     * @var string
     */
    private $head;

    /**
     * @var string
     */
    private $value;

    /**
     * @param string $head
     * @param string $value
     */
    public function __construct(
        string $head,
        string $value
    ) {
        $this->head = $head;
        $this->value = $value;
    }

    /**
     * @param array $data
     * @return string
     */
    public function execute(array $data): string
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getHead(): string
    {
        return $this->head;
    }
}
