<?php
namespace DigitalHub\RuleByDevice\Model\Rule\Condition;

use Magento\Rule\Model\Condition\AbstractCondition;
use Magento\Framework\App\RequestInterface;
use Magento\Rule\Model\Condition\Context;
use Magento\Framework\Model\AbstractModel;
use Magento\Quote\Model\Quote;

class Device extends AbstractCondition
{
    private RequestInterface $request;

    public function __construct(
        Context $context,
        RequestInterface $request,
        array $data = []
    ) {
        $this->request = $request;
        parent::__construct($context, $data);
    }

    public function loadAttributeOptions()
    {
        $this->setAttributeOption(['device_type' => __('Dispositivo')]);
        return $this;
    }

    public function getAttributeElementHtml()
    {
        return $this->getAttributeOptionValue($this->getAttribute());
    }

    public function getInputType()
    {
        return 'select';
    }

    public function getValueElementType()
    {
        return 'multiselect';
    }

    public function getValueSelectOptions()
    {
        return [
            ['value' => 'web', 'label' => __('Web')],
            ['value' => 'android', 'label' => __('Android')],
            ['value' => 'ios', 'label' => __('iOS')],
        ];
    }

    public function validate(AbstractModel $model)
    {
        $quote = null;

        if (method_exists($model, 'getQuote')) {
            $quote = $model->getQuote();
        } elseif ($model instanceof Quote) {
            $quote = $model;
        }

        if (!$quote) {
            return false;
        }

        // Normalize device from quote (may be empty)
        $device = strtolower(trim((string) $quote->getData('device_type')));

        // Fallback: read from request header (do not mutate quote)
        if ($device === '') {
            try {
                $deviceHeader = $this->request->getHeader('X-Device-Type');

                if (empty($deviceHeader)) {
                    $server = $this->request->getServer();
                    if (is_array($server)) {
                        $deviceHeader = $server['HTTP_X_DEVICE_TYPE'] ?? null;
                    }
                }

                $device = strtolower(trim((string) $deviceHeader));
            } catch (\Throwable $e) {
                // Best-effort detection: must not break rule validation
                error_log(
                    'Device condition: failed to resolve device type from request: ' . $e->getMessage()
                );
                $device = '';
            }
        }

        if ($device === '') {
            return false;
        }

        // Normalize condition values
        $rawValue = $this->getValue();
        $values = [];

        if (is_array($rawValue)) {
            foreach ($rawValue as $v) {
                $v = strtolower(trim((string) $v));
                if ($v !== '') {
                    $values[] = $v;
                }
            }
        } else {
            $raw = trim((string) $rawValue);
            if ($raw === '') {
                return false;
            }

            foreach (array_map('trim', explode(',', $raw)) as $v) {
                $v = strtolower((string) $v);
                if ($v !== '') {
                    $values[] = $v;
                }
            }
        }

        return in_array($device, $values, true);
    }
}
