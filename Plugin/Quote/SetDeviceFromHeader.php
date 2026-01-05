<?php
namespace DigitalHub\RuleByDevice\Plugin\Quote;

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\HTTP\PhpEnvironment\Request;
use Magento\Quote\Model\Quote;
use Psr\Log\LoggerInterface;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;
use Magento\Framework\App\State as AppState;

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
     * @var AppState
     */
    private $appState;

    /**
     * Allowed device types
     * @var string[]
     */
    private $allowed = ['web', 'android', 'ios'];

    /**
     * @var QuoteResource
     */
    private $quoteResource;

    /**
     * Track processed quotes for the current PHP process/request to avoid redundant persistence
     * @var array
     */
    private static $processedQuotes = [];

    /**
     * Cached production mode value for this process/request.
     * @var bool|null
     */
    private $isProductionCached = null;

    public function __construct(Request $request, LoggerInterface $logger, AppState $appState, QuoteResource $quoteResource)
    {
        $this->request = $request;
        $this->logger = $logger;
        $this->appState = $appState;
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
        } catch (\Throwable $e) {
            $this->logWarning('RuleByDevice: error reading X-Device-Type header: ' . $e->getMessage());
            $value = null;
        }

        $value = strtolower(trim((string)$value));
        if ($value === '') {
            $this->logInfo('RuleByDevice: X-Device-Type header is missing or has an empty/whitespace-only value on request');
            return $quote;
        }

        if (!in_array($value, $this->allowed, true)) {
            $this->logInfo('RuleByDevice: header value not allowed: ' . (string)$value);
            // normalize unknown values to null (do not set)
            return $quote;
        }

        // Only set device_type if not already present on the quote
        if ($quote->getData('device_type')) {
            $this->logInfo('RuleByDevice: quote already has device_type=' . (string)$quote->getData('device_type') . ' - not overwriting');
            return $quote;
        }

        // Determine a key to track processing for this request: prefer quote ID, else object hash
        $quoteId = (int)$quote->getId();
        if ($quoteId > 0) {
            $key = 'id:' . $quoteId;
        } else {
            $key = 'obj:' . spl_object_hash($quote);
        }

        // If we've already processed this quote in the current request, skip redundant persistence
        if (!empty(self::$processedQuotes[$key])) {
            $this->logInfo('RuleByDevice: already processed device for quote key=' . $key . ' in this request; skipping');
            // still set in-memory if needed
            if (!$quote->getData('device_type')) {
                $quote->setData('device_type', $value);
            }
            return $quote;
        }

        // Set device_type in-memory
        try {
            $quote->setData('device_type', $value);
        } catch (\Throwable $e) {
            $this->logWarning('RuleByDevice: failed to set device_type in-memory for quote - ' . $e->getMessage());
            // mark processed to avoid repeated attempts
            self::$processedQuotes[$key] = true;
            return $quote;
        }

        // Persist only if quote has an ID; avoid persisting new/unsaved quotes
        if ($quoteId > 0) {
            try {
                if (method_exists($this->quoteResource, 'saveAttribute')) {
                    $this->quoteResource->saveAttribute($quote, 'device_type');
                } else {
                    // fallback to saving the quote safely
                    $this->quoteResource->save($quote);
                }
                $this->logInfo('RuleByDevice: persisted device_type attribute for quoteId=' . $quoteId);
            } catch (\Throwable $e) {
                // Log and continue; do not throw to avoid breaking calling flows
                $this->logWarning('RuleByDevice: failed to persist device_type for quoteId=' . $quoteId . ' - ' . $e->getMessage());
            }
        } else {
            $this->logInfo('RuleByDevice: quote has no ID yet; skipping persistence for device_type (in-memory only)');
        }

        // Mark as processed for this request so subsequent interceptions don't redo persistence
        self::$processedQuotes[$key] = true;

        return $quote;
    }

    private function isProduction()
    {
        if ($this->isProductionCached !== null) {
            return $this->isProductionCached;
        }
        try {
            $this->isProductionCached = (string)$this->appState->getMode() === AppState::MODE_PRODUCTION;
        } catch (\Throwable $_) {
            // If we cannot determine, be conservative and assume non-production
            $this->isProductionCached = false;
        }
        return $this->isProductionCached;
    }

    /**
     * Log info only when not in production
     */
    private function logInfo($message)
    {
        if (!$this->isProduction()) {
            try {
                $this->logger->info($message);
            } catch (\Throwable $_) {
                // swallow logging failures
            }
        }
    }

    /**
     * Log warning only when not in production
     */
    private function logWarning($message)
    {
        if (!$this->isProduction()) {
            try {
                $this->logger->warning($message);
            } catch (\Throwable $_) {
                // swallow logging failures
            }
        }
    }
}
