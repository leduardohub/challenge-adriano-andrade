<?php
namespace DigitalHub\RuleByDevice\Test\Unit\Model\Rule\Condition;

use PHPUnit\Framework\TestCase;
use DigitalHub\RuleByDevice\Model\Rule\Condition\Device;
use Magento\Quote\Model\Quote;

class DeviceTest extends TestCase
{
    public function testValidateMatchesSingleValue()
    {
        $deviceCondition = (new \ReflectionClass(Device::class))->newInstanceWithoutConstructor();
        $deviceCondition->setValue('ios');

        $quote = $this->createMock(Quote::class);
        $quote->method('getData')->with('device_type')->willReturn('ios');

        $this->assertTrue($deviceCondition->validate($quote));
    }

    public function testValidateDoesNotMatch()
    {
        $deviceCondition = (new \ReflectionClass(Device::class))->newInstanceWithoutConstructor();
        $deviceCondition->setValue('android');

        $quote = $this->createMock(Quote::class);
        $quote->method('getData')->with('device_type')->willReturn('ios');

        $this->assertFalse($deviceCondition->validate($quote));
    }

    public function testValidateMatchesMultipleValues()
    {
        $deviceCondition = (new \ReflectionClass(Device::class))->newInstanceWithoutConstructor();
        $deviceCondition->setValue('web,ios');

        $quote = $this->createMock(Quote::class);
        $quote->method('getData')->with('device_type')->willReturn('web');

        $this->assertTrue($deviceCondition->validate($quote));
    }

    public function testValidateEmptyDevice()
    {
        $deviceCondition = (new \ReflectionClass(Device::class))->newInstanceWithoutConstructor();
        $deviceCondition->setValue('ios');

        $quote = $this->createMock(Quote::class);
        $quote->method('getData')->with('device_type')->willReturn('');

        $this->assertFalse($deviceCondition->validate($quote));
    }
}

