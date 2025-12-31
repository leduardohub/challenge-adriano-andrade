<?php

/**
 * @copyright  Copyright (c) 2023 Digital Hub (https://digitalhub.com.br)
 * @author     Dev Team <devteam@digitalhub.com.br>
 */

declare(strict_types=1);

namespace DigitalHub\RuleByDevice\Model\System;

use DigitalHub\RuleByDevice\Api\System\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Config implements ConfigInterface
{

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * @inheritDoc
     */
    public function isEnabled(string $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT): bool
    {
        return $this->scopeConfig->isSetFlag(static::CATALOG_RULE_PATH_ENABLE, $scope);
    }

    /**
     * @inheritDoc
     */
    public function getDeviceList(string $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT): string
    {
        return (string)$this->scopeConfig->getValue(static::CATALOG_RULE_PATH_DEVICE_LIST, $scope);
    }
}
