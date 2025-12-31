<?php

/**
 * @copyright  Copyright (c) 2023 Digital Hub (https://digitalhub.com.br)
 * @author     Dev Team <devteam@digitalhub.com.br>
 */

declare(strict_types=1);

namespace DigitalHub\RuleByDevice\Test\Unit\Model\System;

use DigitalHub\RuleByDevice\Model\System\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\MockObject\MockObject;

class ConfigTest extends TestCase
{

    /** @var Config */
    protected Config $instance;
    /** @var ScopeConfigInterface|MockObject */
    protected ScopeConfigInterface|MockObject $scopeConfigMock;

    /**
     * Set up the test
     */
    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->instance = new Config($this->scopeConfigMock);
    }

    /**
     * Test flag
     */
    public function testIsEnabled(): void
    {
        $this->scopeConfigMock->expects(static::once())
            ->method('isSetFlag')
            ->with(ConfigInterface::ONESIGNAL_PATH_ENABLE)
            ->willReturn(true);
        static::assertTrue($this->instance->isEnabled());
    }

    /**
     * Test flag disabled
     */
    public function testIsNotEnabled(): void
    {
        $this->scopeConfigMock->expects(static::once())
            ->method('isSetFlag')
            ->with(ConfigInterface::ONESIGNAL_PATH_ENABLE)
            ->willReturn(false);
        static::assertFalse($this->instance->isEnabled());
    }
}
