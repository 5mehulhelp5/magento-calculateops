<?php
declare(strict_types=1);

namespace Ootri\Calculateops\Catalog\Block\Product\View\Options;

use Magento\Catalog\Block\Product\View\Options\AbstractOptions;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Catalog\Helper\Data as CatalogHelper;
use Ootri\Calculateops\Plugin\PriceCalculator;

class OptionsProvider extends AbstractOptions
{
    private PriceCalculator $priceCalculator;

    public function __construct(
        Context $context,
        PricingHelper $pricingHelper,
        CatalogHelper $catalogData,
        PriceCalculator $priceCalculator,
        array $data = []
    ) {
        parent::__construct($context, $pricingHelper, $catalogData, $data);
        $this->priceCalculator = $priceCalculator;
    }

    /**
     * Override to use catalog rule prices for custom options
     */
    protected function _formatPrice($value, $flag = true)
    {
        if ($value['pricing_value'] == 0) {
            return '';
        }

        $calculatedPrice = $this->priceCalculator->calculateCustomOptionPrice(
            $this->getProduct(),
            (float)$value['pricing_value'],
            (bool)$value['is_percent']
        );

        if ($calculatedPrice !== null) {
            // Update the data-price-amount attribute value
            $this->getProduct()->setCustomOptionDisplayPrice($calculatedPrice);
            
            // Also store the original price for display if needed
            $this->getProduct()->setCustomOptionOriginalPrice($value['pricing_value']);
            
            $value['pricing_value'] = $calculatedPrice;
        }

        return parent::_formatPrice($value, $flag);
    }
}