<?php
/*
 * Copyright © Websolute spa. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Websolute\TransporterImporter\Model;

use Websolute\TransporterBase\Exception\TransporterException;

class DotConvention
{
    /**
     * @param string $value
     * @return string
     * @throws TransporterException
     */
    public function getFirst(string $value): string
    {
        $values = $this->getAll($value);

        return (string)array_shift($values);
    }

    /**
     * @param string $value
     * @return string[]
     * @throws TransporterException
     */
    public function getAll(string $value): array
    {
        $values = explode('.', $value);

        if (count($values) < 2) {
            throw new TransporterException(__('Invalid identifier: %1', $value));
        }

        return $values;
    }

    /**
     * @param string $value
     * @return array
     * @throws TransporterException
     */
    public function getFromSecond(string $value): array
    {
        $values = $this->getAll($value);

        array_shift($values);

        return $values;
    }
}
