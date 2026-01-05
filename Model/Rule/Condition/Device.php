<?php
namespace DigitalHub\RuleByDevice\Model\Rule\Condition;

use Magento\Rule\Model\Condition\AbstractCondition;

class Device extends AbstractCondition
{
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

    public function validate(\Magento\Framework\Model\AbstractModel $model)
    {
        $quote = null;
        if (method_exists($model, 'getQuote')) {
            $quote = $model->getQuote();
        } elseif ($model instanceof \Magento\Quote\Model\Quote) {
            $quote = $model;
        }

        if (!$quote) {
            return false;
        }

        // normalize device read from quote (may be empty)
        $device = strtolower(trim((string)$quote->getData('device_type')));
        // fallback: if quote has no device_type, try to read header from current request (do not mutate quote)
        if ($device === '') {
            try {
                $om = \Magento\Framework\App\ObjectManager::getInstance();
                $request = $om->get(\Magento\Framework\HTTP\PhpEnvironment\Request::class);
                $deviceHeader = $request->getHeader('X-Device-Type');
                if (empty($deviceHeader)) {
                    // server var fallback
                    $server = $request->getServer();
                    if (is_callable([$server, 'get'])) {
                        $deviceHeader = $server->get('HTTP_X_DEVICE_TYPE');
                    } elseif (is_array($server)) {
                        $deviceHeader = $server['HTTP_X_DEVICE_TYPE'] ?? null;
                    }
                }
                $device = strtolower(trim((string)$deviceHeader));
            } catch (\Throwable $_) {
                $device = '';
            }
        }

        if ($device === '') {
            return false;
        }

        // get condition value which can be a string (comma separated) or an array
        $rawValue = $this->getValue();
        $values = [];
        if (is_array($rawValue)) {
            // normalize array values to strings/lowercase
            foreach ($rawValue as $v) {
                $v = strtolower(trim((string)$v));
                if ($v !== '') {
                    $values[] = $v;
                }
            }
        } else {
            $raw = trim((string)$rawValue);
            if ($raw === '') {
                return false;
            }
            foreach (array_map('trim', explode(',', $raw)) as $v) {
                $v = strtolower((string)$v);
                if ($v !== '') {
                    $values[] = $v;
                }
            }
        }

        // final check
        return in_array($device, $values, true);
    }
}
