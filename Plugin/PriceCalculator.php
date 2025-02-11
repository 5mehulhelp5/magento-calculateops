<?php
declare(strict_types=1);

namespace BredaBeds\CalculateOps\Plugin;

use Magento\Catalog\Model\Product;
use Magento\CatalogRule\Pricing\Price\CatalogRulePrice;
use Magento\Framework\Pricing\Price\BasePriceProviderInterface;

class PriceCalculator
{

    public function __construct(
        private \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        private \Magento\Catalog\Model\Product\PriceModifierInterface $priceModifier
    ) { }

    /**
     * Calculate custom option price with catalog rules applied
     */
    public function calculateCustomOptionPrice(
        Product $product,
        float $optionPrice,
        bool $isPercent,
        string $caller = ''
    ): float {
        $regularPrice = (float)$product->getPriceInfo()
            ->getPrice(\Magento\Catalog\Pricing\Price\RegularPrice::PRICE_CODE)
            ->getValue();
            
        $catalogRulePrice = $this->priceModifier->modifyPrice(
            $regularPrice,
            $product
        );
        
        $basePrice = $this->getBasePriceWithoutCatalogRules($product);

        //ooedit: logging
        $logString = sprintf(
            'Price Calculation: Product ID: %s, Regular: %f, Catalog Rule: %f, Base: %f, Option: %f, IsPercent: %d, Caller: %s',
            $product->getId(),
            $regularPrice,
            $catalogRulePrice,
            $basePrice,
            $optionPrice,
            $isPercent ? 1 : 0,
            $caller
        );
        //\BredaBeds\Core\Helper\Notify::printLog($logString);

        // Always calculate the option price
        $finalPrice = $this->calculateOptionPrice($optionPrice, $isPercent, $regularPrice);

        if ($catalogRulePrice < $basePrice) {
            $totalPrice = $regularPrice + $finalPrice;
            $totalWithRules = $this->priceModifier->modifyPrice($totalPrice, $product);
            $finalPrice = $totalWithRules - $catalogRulePrice;
        }

        return $finalPrice;
    }

    private function getBasePriceWithoutCatalogRules(Product $product): float
    {
        $basePrice = null;
        foreach ($product->getPriceInfo()->getPrices() as $price) {
            if ($price instanceof BasePriceProviderInterface
                && $price->getPriceCode() !== CatalogRulePrice::PRICE_CODE
                && $price->getValue() !== false
            ) {
                $basePrice = min(
                    $price->getValue(),
                    $basePrice ?? $price->getValue()
                );
            }
        }

        return $basePrice ?? $product->getPrice();
    }

    private function calculateOptionPrice(float $optionPrice, bool $isPercent, float $basePrice): float
    {
        return $isPercent ? $basePrice * $optionPrice / 100 : $optionPrice;
    }
}
