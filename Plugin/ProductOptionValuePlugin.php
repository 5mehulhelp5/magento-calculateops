<?php
declare(strict_types=1);

namespace Ootri\Calculateops\Plugin;

use Magento\Catalog\Model\Product\Option\Value;

class ProductOptionValuePlugin
{
    
    public function __construct(
        private PriceCalculator $priceCalculator
    ){ }

    public function aroundGetPrice(Value $subject, callable $proceed, $flag = false)
    {
        if ($flag) {
            return $this->priceCalculator->calculateCustomOptionPrice(
                $subject->getProduct(),
                (float)$subject->getData(Value::KEY_PRICE),
                $subject->getPriceType() === Value::TYPE_PERCENT,
                'ProductOptionValuePlugin'
            );
        }
        return $proceed($flag);
    }
}
