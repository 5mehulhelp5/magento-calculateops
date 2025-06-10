<?php

// Reviewed on 2025-06-10
/* Compare with:
./vendor/hyva-themes/magento2-theme-module/src/ViewModel/ProductPrice.php
https://gitlab.hyva.io/hyva-themes/magento2-theme-module/-/blob/main/src/ViewModel/ProductPrice.php
https://gitlab.hyva.io/hyva-themes/magento2-theme-module/-/commits/main/src/ViewModel/ProductPrice.php?ref_type=heads
*/

declare(strict_types=1);

namespace BredaBeds\CalculateOps\Plugin;

use Hyva\Theme\ViewModel\ProductPrice;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Option\Value;
use Magento\Catalog\Pricing\Price\CustomOptionPrice;

class ProductPriceViewModelPlugin
{

    public function __construct(
        private \BredaBeds\CalculateOps\Plugin\PriceCalculator $priceCalculator
    ){ }

    public function afterGetCustomOptionPrice(
        ProductPrice $subject,
        float $result,
        $option,
        string $priceType,
        ?Product $product = null
    ): array {
        if ($priceType !== CustomOptionPrice::PRICE_CODE) {
            //2025-01-09 (include both final/regular prices)
            //return $result;
            return ['final' => $result, 'regular' => $result];
        }

        $calculatedPrice = $this->priceCalculator->calculateCustomOptionPrice(
            $product ?? $subject->getProduct(),
            $result,
            $option instanceof Value ? $option->getPriceType() === Value::TYPE_PERCENT : $option->getPriceType() === 'percent'
        );

        //2025-01-09 (include both final/regular prices)
        //return $calculatedPrice ?? $result;
        return [
            'final' => $calculatedPrice ?? $result,
            'regular' => $result  // Original price before discount
        ];
    }
}