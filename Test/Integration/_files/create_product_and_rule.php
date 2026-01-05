<?php
/**
 * Fixture to create a simple product and a sales rule using the Device condition
 */
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

$sku = 'e2e-device-product';
// create product if it doesn't already exist
/** @var \Magento\Catalog\Model\ResourceModel\Product $productResource */
$productResource = $objectManager->get(\Magento\Catalog\Model\ResourceModel\Product::class);
$existingProductId = $productResource->getIdBySku($sku);
if ($existingProductId) {
    // load existing product
    $product = $objectManager->create(\Magento\Catalog\Model\Product::class)->load($existingProductId);
} else {
    /** @var \Magento\Catalog\Model\Product $product */
    $product = $objectManager->create(\Magento\Catalog\Model\Product::class);
    // determine a valid attribute set id dynamically (don't assume 4)
    $attributeSetId = (int) $product->getDefaultAttributeSetId();
    $product->setTypeId('simple')
        ->setAttributeSetId($attributeSetId)
        ->setSku($sku)
        ->setName('E2E Device Product')
        ->setPrice(100)
        ->setStatus(1)
        ->setVisibility(4)
        ->setStockData(['use_config_manage_stock' => 1, 'is_in_stock' => 1, 'qty' => 100]);
    // set website ids via setData to avoid static analyzer type mismatch
    $product->setData('website_ids', [$objectManager->get(\Magento\Store\Model\StoreManagerInterface::class)->getDefaultStoreView()->getWebsiteId()]);
    $product->save();
}

// create sales rule only if not already present (by name)
$ruleCollection = $objectManager->create(\Magento\SalesRule\Model\ResourceModel\Rule\Collection::class)
    ->addFieldToFilter('name', 'Device 10% off')
    ->setPageSize(1);
if ($ruleCollection->getSize() == 0) {
    $rule = $objectManager->create(\Magento\SalesRule\Model\Rule::class);
    $rule->setName('Device 10% off')
        ->setIsActive(1)
        ->setCouponType(\Magento\SalesRule\Model\Rule::COUPON_TYPE_NO_COUPON)
        ->setSimpleAction('by_percent')
        ->setDiscountAmount(10)
        ->setStopRulesProcessing(1)
        // set website/customer group via setData to avoid static analyzer warnings about types
        ->setData('website_ids', [$objectManager->get(\Magento\Store\Model\StoreManagerInterface::class)->getDefaultStoreView()->getWebsiteId()])
        // ensure rule targets guests explicitly (use constant for clarity)
        ->setData('customer_group_ids', [\Magento\Customer\Model\Group::NOT_LOGGED_IN_ID]);

    $conditions = [
        'type' => \Magento\SalesRule\Model\Rule\Condition\Combine::class,
        'aggregator' => 'all',
        'value' => '1',
        'conditions' => [
            [
                'type' => \DigitalHub\RuleByDevice\Model\Rule\Condition\Device::class,
                'attribute' => 'device_type',
                'operator' => '==',
                'value' => 'android'
            ]
        ]
    ];

    $rule->getConditions()->loadArray($conditions);
    $rule->save();
} else {
    // reuse existing rule
    $rule = $ruleCollection->getFirstItem();
}

// persist the SKU as a DataObject so tests can retrieve it in a consistent shape
\Magento\TestFramework\Fixture\DataFixtureStorageManager::getStorage()->persist(
    'e2e_device_product_sku',
    new \Magento\Framework\DataObject(['sku' => $sku])
);

return;

