<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-search-ultimate
 * @version   2.2.70
 * @copyright Copyright (C) 2024 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\Search\Ui\ScoreRule\Form\Control;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class PreviewButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getButtonData()
    {
        $ns = 'search_scorerule_form.search_scorerule_form';

        return [
            'label'          => __('Preview'),
            'class'          => 'preview',
            'sort_order'     => 30,
            'on_click'       => '',
            'data_attribute' => [
                'mage-init' => [
                    'buttonAdapter' => [
                        'actions' => [
                            [
                                'targetName' => $ns . '.preview_modal',
                                'actionName' => 'toggleModal',
                            ],
                            [
                                'targetName' => $ns . '.preview_modal.preview_listing',
                                'actionName' => 'render',
                            ],
                            [
                                'targetName' => $ns . '.preview_modal.preview_listing',
                                'actionName' => 'reload',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
