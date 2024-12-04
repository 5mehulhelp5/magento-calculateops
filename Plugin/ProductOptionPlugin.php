<?php
declare(strict_types=1);

namespace Ootri\Calculateops\Plugin;

use Magento\Catalog\Model\Product\Option;
use Magento\Catalog\Model\Product\Option\Value;

class ProductOptionPlugin
{
    public function __construct(
        private PriceCalculator $priceCalculator
    ){ }

    public function aroundGetPrice(Option $subject, callable $proceed, $flag = false)
    {
        if ($flag) {
            return $this->priceCalculator->calculateCustomOptionPrice(
                $subject->getProduct(),
                (float)$subject->getData(Option::KEY_PRICE),
                $subject->getPriceType() === Value::TYPE_PERCENT,
                'ProductOptionPlugin'
            );
        }
        return $proceed($flag);
    }
}
