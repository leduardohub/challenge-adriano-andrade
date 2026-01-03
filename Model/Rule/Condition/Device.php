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

        $device = (string)$quote->getData('device_type');
        if ($device === '') {
            return false;
        }

        $value = (string)$this->getValue();
        if ($value === '') {
            return false;
        }
        $values = array_map('trim', explode(',', $value));
        return in_array($device, $values, true);
    }
}
