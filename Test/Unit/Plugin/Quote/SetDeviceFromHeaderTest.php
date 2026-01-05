<?php
namespace DigitalHub\RuleByDevice\Test\Unit\Plugin\Quote;

use PHPUnit\Framework\TestCase;
use DigitalHub\RuleByDevice\Plugin\Quote\SetDeviceFromHeader;
use Magento\Framework\HTTP\PhpEnvironment\Request;
use Magento\Quote\Model\Quote;
use Magento\Quote\Api\CartRepositoryInterface;

class SetDeviceFromHeaderTest extends TestCase
{
    public function testAfterGetSetsDeviceWhenHeaderValid()
    {
        $request = $this->createMock(Request::class);
        $request->method('getHeader')->with('X-Device-Type')->willReturn('iOs');

        $plugin = new SetDeviceFromHeader($request);

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->expects($this->once())->method('getData')->with('device_type')->willReturn(null);
        $quote->expects($this->once())->method('setData')->with('device_type', 'ios');

        $result = $plugin->afterGet($this->createMock(CartRepositoryInterface::class), $quote);
        $this->assertSame($quote, $result);
    }

    public function testAfterGetDoesNotSetWhenHeaderInvalid()
    {
        $request = $this->createMock(Request::class);
        $request->method('getHeader')->with('X-Device-Type')->willReturn('unknown');

        $plugin = new SetDeviceFromHeader($request);

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->expects($this->once())->method('getData')->with('device_type')->willReturn(null);
        $quote->expects($this->never())->method('setData');

        $result = $plugin->afterGet($this->createMock(CartRepositoryInterface::class), $quote);
        $this->assertSame($quote, $result);
    }

    public function testAfterGetDoesNotOverwriteExisting()
    {
        $request = $this->createMock(Request::class);
        $request->method('getHeader')->with('X-Device-Type')->willReturn('android');

        $plugin = new SetDeviceFromHeader($request);

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->expects($this->once())->method('getData')->with('device_type')->willReturn('ios');
        $quote->expects($this->never())->method('setData');

        $result = $plugin->afterGet($this->createMock(CartRepositoryInterface::class), $quote);
        $this->assertSame($quote, $result);
    }
}

