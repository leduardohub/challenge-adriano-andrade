<?php
namespace DigitalHub\RuleByDevice\Test\Integration\Plugin\Quote;

use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Magento\Quote\Model\Quote;
use Magento\Quote\Api\CartRepositoryInterface;

/**
 * @magentoAppIsolation enabled
 */
class SetDeviceFromHeaderTest extends TestCase
{
    public function testSetsDeviceWhenHeaderValid()
    {
        $om = Bootstrap::getObjectManager();
        /** @var \Magento\Framework\HTTP\PhpEnvironment\Request $request */
        $request = $om->get(\Magento\Framework\HTTP\PhpEnvironment\Request::class);
        // Ensure headers/server vars are clean to avoid leaking state between tests
        if (method_exists($request->getHeaders(), 'clearHeaders')) {
            $request->getHeaders()->clearHeaders();
        }
        $request->getServer()->set('HTTP_X_DEVICE_TYPE', '');
        // Set the HTTP header and also the server variable (some code reads headers, others use server)
        $request->getHeaders()->addHeaderLine('X-Device-Type', 'Android');
        $request->getServer()->set('HTTP_X_DEVICE_TYPE', 'Android');

        /** @var CartRepositoryInterface $subject */
        $subject = $om->get(CartRepositoryInterface::class);
        /** @var \DigitalHub\RuleByDevice\Plugin\Quote\SetDeviceFromHeader $plugin */
        $plugin = $om->create(\DigitalHub\RuleByDevice\Plugin\Quote\SetDeviceFromHeader::class);

        /** @var Quote $quote */
        $quote = $om->create(Quote::class);

        $returned = $plugin->afterGet($subject, $quote);

        $this->assertEquals('android', $returned->getData('device_type'));
    }

    public function testDoesNotSetWhenHeaderInvalid()
    {
        $om = Bootstrap::getObjectManager();
        /** @var \Magento\Framework\HTTP\PhpEnvironment\Request $request */
        $request = $om->get(\Magento\Framework\HTTP\PhpEnvironment\Request::class);
        if (method_exists($request->getHeaders(), 'clearHeaders')) {
            $request->getHeaders()->clearHeaders();
        }
        $request->getServer()->set('HTTP_X_DEVICE_TYPE', '');
        $request->getHeaders()->addHeaderLine('X-Device-Type', 'mobile');
        $request->getServer()->set('HTTP_X_DEVICE_TYPE', 'mobile'); // not allowed

        $subject = $om->get(CartRepositoryInterface::class);
        $plugin = $om->create(\DigitalHub\RuleByDevice\Plugin\Quote\SetDeviceFromHeader::class);
        $quote = $om->create(Quote::class);

        $returned = $plugin->afterGet($subject, $quote);

        $this->assertEmpty($returned->getData('device_type'));
    }

    public function testDoesNotOverwriteExistingValue()
    {
        $om = Bootstrap::getObjectManager();
        /** @var \Magento\Framework\HTTP\PhpEnvironment\Request $request */
        $request = $om->get(\Magento\Framework\HTTP\PhpEnvironment\Request::class);
        if (method_exists($request->getHeaders(), 'clearHeaders')) {
            $request->getHeaders()->clearHeaders();
        }
        $request->getServer()->set('HTTP_X_DEVICE_TYPE', '');
        $request->getHeaders()->addHeaderLine('X-Device-Type', 'android');
        $request->getServer()->set('HTTP_X_DEVICE_TYPE', 'android');

        $subject = $om->get(CartRepositoryInterface::class);
        $plugin = $om->create(\DigitalHub\RuleByDevice\Plugin\Quote\SetDeviceFromHeader::class);
        $quote = $om->create(Quote::class);
        $quote->setData('device_type', 'ios');

        $returned = $plugin->afterGet($subject, $quote);

        $this->assertEquals('ios', $returned->getData('device_type'));
    }
}

