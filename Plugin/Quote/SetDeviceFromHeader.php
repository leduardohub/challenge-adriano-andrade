<?php
namespace DigitalHub\RuleByDevice\Plugin\Quote;

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\HTTP\PhpEnvironment\Request;
use Magento\Quote\Model\Quote;

class SetDeviceFromHeader
{
    /**
     * @var Request
     */
    private $request;

    /**
     * Allowed device types
     * @var string[]
     */
    private $allowed = ['web', 'android', 'ios'];

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function afterGet(CartRepositoryInterface $subject, Quote $quote)
    {
        return $this->applyDevice($quote);
    }

    public function afterGetActive(CartRepositoryInterface $subject, Quote $quote)
    {
        return $this->applyDevice($quote);
    }

    public function afterGetForCustomer(CartRepositoryInterface $subject, Quote $quote)
    {
        return $this->applyDevice($quote);
    }

    private function applyDevice(Quote $quote)
    {
        // only set if not already set to avoid interfering with other flows
        if ($quote->getData('device_type')) {
            return $quote;
        }

        $value = strtolower((string) $this->request->getHeader('X-Device-Type'));
        if (!in_array($value, $this->allowed, true)) {
            // normalize unknown values to null (do not set)
            return $quote;
        }

        $quote->setData('device_type', $value);
        return $quote;
    }
}
