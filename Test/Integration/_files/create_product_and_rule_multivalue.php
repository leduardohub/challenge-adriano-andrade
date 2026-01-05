<?php
/**
 * Fixture to create a simple product and a sales rule using the Device condition with multiple allowed values
 */
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

// create product
/** @var \Magento\Catalog\Model\Product $product */
$product = $objectManager->create(\Magento\Catalog\Model\Product::class);
$attributeSetId = 4;
$sku = 'e2e-device-product-mv';
$product->setTypeId('simple')
    ->setAttributeSetId($attributeSetId)
    ->setSku($sku)
    ->setName('E2E Device Product MV')
    ->setPrice(100)
    ->setStatus(1)
    ->setVisibility(4)
    ->setStockData(['use_config_manage_stock' => 1, 'is_in_stock' => 1, 'qty' => 100])
    ->setWebsiteIds([$objectManager->get(\Magento\Store\Model\StoreManagerInterface::class)->getDefaultStoreView()->getWebsiteId()]);
$product->save();

// create sales rule
/** @var \Magento\SalesRule\Model\Rule $rule */
$rule = $objectManager->create(\Magento\SalesRule\Model\Rule::class);
$rule->setName('Device 10% off MV')
    ->setIsActive(1)
    ->setCouponType(\Magento\SalesRule\Model\Rule::COUPON_TYPE_NO_COUPON)
    ->setSimpleAction('by_percent')
    ->setDiscountAmount(10)
    ->setStopRulesProcessing(1)
    ->setWebsiteIds([$objectManager->get(\Magento\Store\Model\StoreManagerInterface::class)->getStore()->getWebsiteId()])
    ->setCustomerGroupIds([\Magento\Customer\Model\GroupManagement::NOT_LOGGED_IN_ID]);

$conditions = [
    'type' => \Magento\SalesRule\Model\Rule\Condition\Combine::class,
    'aggregator' => 'all',
    'value' => '1',
    'conditions' => [
        [
            'type' => \DigitalHub\RuleByDevice\Model\Rule\Condition\Device::class,
            'attribute' => 'device_type',
            'operator' => '==',
            'value' => 'android,ios'
        ]
    ]
];

$rule->getConditions()->loadArray($conditions);
$rule->save();

// expose product sku to tests via fixture storage
\Magento\TestFramework\Fixture\DataFixtureStorageManager::getStorage()->persist('e2e_device_product_mv_sku', $sku);

return;

