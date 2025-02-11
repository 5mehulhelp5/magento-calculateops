<?php
declare(strict_types=1);

namespace BredaBeds\CalculateOps\Plugin;

use Magento\Catalog\Model\Product\Option;
use Magento\Catalog\Model\Product\Option\Value;
use Magento\Catalog\Model\Product\Option\Type\Select as SelectType;

class SelectTypePlugin
{

    /**
     * Options with single value selection
     */
    private array $singleSelectionTypes = [
        Option::OPTION_TYPE_DROP_DOWN,
        Option::OPTION_TYPE_RADIO
    ];

    public function __construct(
        private \BredaBeds\CalculateOps\Plugin\PriceCalculator $priceCalculator
    ){ }

    public function aroundGetOptionPrice(SelectType $subject, callable $proceed, $optionValue, $basePrice)
    {
        $option = $subject->getOption();
        $result = 0;

        $isSingleSelection = in_array($option->getType(), $this->singleSelectionTypes, true);

        if (!$isSingleSelection) {
            foreach (explode(',', (string)$optionValue) as $value) {
                $_result = $option->getValueById($value);
                if ($_result) {
                    $result += $this->priceCalculator->calculateCustomOptionPrice(
                        $option->getProduct(),
                        (float)$_result->getPrice(),
                        $_result->getPriceType() === Value::TYPE_PERCENT,
                        'SelectTypePlugin foreach'
                    );
                } else {
                    if ($subject->getListener()) {
                        $subject->getListener()->setHasError(true)->setMessage($subject->_getWrongConfigurationMessage());
                        break;
                    }
                }
            }
        } else {
            $_result = $option->getValueById($optionValue);
            if ($_result) {
                $result = $this->priceCalculator->calculateCustomOptionPrice(
                    $option->getProduct(),
                    (float)$_result->getPrice(),
                    $_result->getPriceType() === Value::TYPE_PERCENT,
                    'SelectTypePlugin else'
                );
            } else {
                if ($subject->getListener()) {
                    $subject->getListener()->setHasError(true)->setMessage($subject->_getWrongConfigurationMessage());
                }
            }
        }

        return $result;
    }
}
