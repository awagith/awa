<?php

namespace Rokanthemes\Faq\Model\Config\Source;

class Status implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => '1', 'label' => __('Enable')],
            ['value' => '2', 'label' => __('Disable')]
        ];
    }
}