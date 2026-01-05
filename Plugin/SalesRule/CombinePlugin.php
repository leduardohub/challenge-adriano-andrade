<?php
namespace DigitalHub\RuleByDevice\Plugin\SalesRule;

class CombinePlugin
{
    public function afterGetNewChildSelectOptions(\Magento\SalesRule\Model\Rule\Condition\Combine $subject, $result)
    {
        $additional = [
            'value' => 'DigitalHub\RuleByDevice\Model\Rule\Condition\Device',
            'label' => __('Dispositivo')
        ];

        // append under "Conditions Combination" top level
        $result[] = ['value' => $additional['value'], 'label' => $additional['label']];

        return $result;
    }
}
