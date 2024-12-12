<?php
declare(strict_types=1);

namespace Ootri\Calculateops\Plugin;

use Hyva\Theme\ViewModel\ProductPrice;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Option\Value;
use Magento\Catalog\Pricing\Price\CustomOptionPrice;

class ProductPriceViewModelPlugin
{

    public function __construct(
        private PriceCalculator $priceCalculator
    ){ }

    public function afterGetCustomOptionPrice(
        ProductPrice $subject,
        float $result,
        $option,
        string $priceType,
        ?Product $product = null
    ): float {
        if ($priceType !== CustomOptionPrice::PRICE_CODE) {
            return $result;
        }

        $calculatedPrice = $this->priceCalculator->calculateCustomOptionPrice(
            $product ?? $subject->getProduct(),
            $result,
            $option instanceof Value ? $option->getPriceType() === Value::TYPE_PERCENT : $option->getPriceType() === 'percent'
        );

        return $calculatedPrice ?? $result;
    }
}