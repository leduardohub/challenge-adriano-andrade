<?php
namespace DigitalHub\RuleByDevice\Test\Integration\Model\Rule\Condition;

use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Magento\Framework\DataObject;

/**
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class DeviceTest extends TestCase
{
    public function testValidateReturnsTrueWhenQuoteHasMatchingDevice()
    {
        $om = Bootstrap::getObjectManager();
        /** @var \DigitalHub\RuleByDevice\Model\Rule\Condition\Device $condition */
        $condition = $om->create(\DigitalHub\RuleByDevice\Model\Rule\Condition\Device::class);

        $condition->setData('attribute', 'device_type');
        $condition->setData('operator', '==');
        $condition->setData('value', 'android');

        $quote = $om->create(\Magento\Quote\Model\Quote::class);
        $quote->setData('device_type', 'android');

        $this->assertTrue($condition->validate($quote));
    }

    public function testValidateReturnsFalseWhenQuoteHasDifferentDevice()
    {
        $om = Bootstrap::getObjectManager();
        $condition = $om->create(\DigitalHub\RuleByDevice\Model\Rule\Condition\Device::class);

        $condition->setData('attribute', 'device_type');
        $condition->setData('operator', '==');
        $condition->setData('value', 'ios');

        $quote = $om->create(\Magento\Quote\Model\Quote::class);
        $quote->setData('device_type', 'web');

        $this->assertFalse($condition->validate($quote));
    }

    public function testValidateReturnsFalseWhenQuoteDeviceEmpty()
    {
        $om = Bootstrap::getObjectManager();
        $condition = $om->create(\DigitalHub\RuleByDevice\Model\Rule\Condition\Device::class);

        $condition->setData('attribute', 'device_type');
        $condition->setData('operator', '==');
        $condition->setData('value', 'android');

        $quote = $om->create(\Magento\Quote\Model\Quote::class);

        $this->assertFalse($condition->validate($quote));
    }
}

