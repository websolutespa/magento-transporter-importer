<?php
/*
 * Copyright © Websolute spa. All rights reserved.
 * See LICENSE and/or COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Websolute\TransporterImporter\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    const IMPORT_IS_ENABLED_CONFIG_PATH = 'transporter_importer/general/enabled';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Websolute\TransporterBase\Model\Config
     */
    private $baseConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        \Websolute\TransporterBase\Model\Config $baseConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->baseConfig = $baseConfig;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->baseConfig->isEnabled() && (bool)$this->scopeConfig->getValue(
            self::IMPORT_IS_ENABLED_CONFIG_PATH,
            ScopeInterface::SCOPE_STORE
        );
    }
}
