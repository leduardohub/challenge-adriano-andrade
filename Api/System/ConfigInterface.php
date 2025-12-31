<?php

/**
 * @copyright  Copyright (c) 2023 Digital Hub (https://digitalhub.com.br)
 * @author     Dev Team <devteam@digitalhub.com.br>
 */

declare(strict_types=1);

namespace DigitalHub\RuleByDevice\Api\System;

use Magento\Framework\App\Config\ScopeConfigInterface;

interface ConfigInterface
{

    public const CATALOG_RULE_PATH_ENABLE = 'digitalhub_rule_by_device/general/enabled';
    public const CATALOG_RULE_PATH_DEVICE_LIST = 'digitalhub_rule_by_device/general/device_list';

    /**
     * Module enabled
     *
     * @param string $scope
     * @return bool
     */
    public function isEnabled(string $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT): bool;

    /**
     * Get device list
     *
     * @param string $scope
     * @return string
     */
    public function getDeviceList(string $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT): string;
}
