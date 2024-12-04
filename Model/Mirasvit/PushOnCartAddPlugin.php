<?php
declare(strict_types=1);

namespace Surething\Calculateops\Model\Mirasvit;

use Magento\Catalog\Model\Product;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\Tm\Converter\DataConverter;
use Mirasvit\Tm\Model\DataLayer;
use Mirasvit\Tm\Registry;
use Mirasvit\Tm\Repository\AdditionalProductData;

class PushOnCartAddPlugin
{
    public function __construct(
        private CustomerSession $customerSession,
        private DataLayer $dataLayer,
        private DataConverter $dataConverter,
        private Registry $registry,
        private StoreManagerInterface $storeManager,
        private AdditionalProductData $productData
    ) {}

    public function afterAddProduct(Quote $subject, $result, Product $product, $request = null)
    {
        if (!is_object($result)) {
            return $result;
        }

        $productId = (int)$product->getId();
        if ($this->registry->hasCartAddedProduct($productId)) {
            return $result;
        }

        try {
            // Get currency
            $currency = $subject->getBaseCurrencyCode();
            if (!$currency) {
                $store = $this->storeManager->getStore($result->getStoreId());
                $currency = $store->getBaseCurrency()->getCode();
            }

            // Get product data for GTM without modifying any prices
            $productData = $this->dataConverter->getProductData($product, $currency);

            // Add any additional product data
            foreach ($this->productData->getList() as $additionalData) {
                $productData = array_merge(
                    $additionalData->getAdditionalCartProductData(
                        $product,
                        $productData,
                        $this->customerSession->getCustomer()
                    ),
                    $productData
                );
            }

            // Prepare event data
            $eventData = [
                'currency' => $currency,
                'value' => $productData['price'],
                'items' => [$productData]
            ];

            // Add customer group data
            foreach ($this->productData->getList() as $additionalData) {
                $eventData = $additionalData->addCustomerGroup(
                    $eventData,
                    (int)$this->customerSession->getCustomerGroupId(),
                    $this->storeManager->getStore()
                );
            }

            // Push to data layer
            $this->dataLayer->setCheckoutData([
                0 => 'event',
                1 => 'add_to_cart',
                2 => $eventData,
                'gtm_id' => microtime(true)
            ]);

            $this->registry->addCartAddedProduct($productId);
        } catch (\Exception $e) {
            // Silently fail for GTM rather than break cart functionality
        }

        return $result;
    }
}