<?php
namespace DigitalHub\RuleByDevice\Plugin\Quote;

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\HTTP\PhpEnvironment\Request;
use Magento\Quote\Model\Quote;
use Psr\Log\LoggerInterface;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;

class SetDeviceFromHeader
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Allowed device types
     * @var string[]
     */
    private $allowed = ['web', 'android', 'ios'];

    /**
     * @var QuoteResource
     */
    private $quoteResource;

    public function __construct(Request $request, LoggerInterface $logger, QuoteResource $quoteResource)
    {
        $this->request = $request;
        $this->logger = $logger;
        $this->quoteResource = $quoteResource;
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
        // Try to obtain header from Request object first
        try {
            $value = $this->request->getHeader('X-Device-Type');
        } catch (\Throwable $_) {
            $value = null;
        }


        $value = strtolower(trim((string)$value));
        if ($value === '') {
            $this->logger->info('RuleByDevice: X-Device-Type header is missing or has an empty/whitespace-only value on request');
            return $quote;
        }

        if (!in_array($value, $this->allowed, true)) {
            $this->logger->info('RuleByDevice: header value not allowed: ' . (string)$value);
            // normalize unknown values to null (do not set)
            return $quote;
        }

        // Only set device_type if not already present on the quote
        if ($quote->getData('device_type')) {
            $this->logger->info('RuleByDevice: quote already has device_type=' . (string)$quote->getData('device_type') . ' - not overwriting');
            return $quote;
        }

        // Safely set device_type on the quote and persist only the attribute to avoid full save side-effects
        try {
            $this->logger->info('RuleByDevice: setting device_type=' . $value . ' for quoteId=' . (string)$quote->getId());
            // set on quote (in-memory)
            $quote->setData('device_type', $value);
            // persist only the device_type attribute
            $this->quoteResource->saveAttribute($quote, 'device_type');
        } catch (\Throwable $e) {
            $this->logger->error('RuleByDevice: error saving device_type=' . $value . ' for quoteId=' . (string)$quote->getId() . ' - ' . $e->getMessage());
        }

        return $quote;
    }
}
