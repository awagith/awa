<?php

declare(strict_types=1);

namespace GrupoAwamotos\CatalogFix\Block\System\Config\Form\Field;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Registry;
use Rokanthemes\Themeoption\Block\System\Config\Form\Field\Color as ThemeoptionColor;

/**
 * Fix: Rokanthemes Color.php used $base.'pub/media/js/' which:
 * 1. Pointed to a non-existent directory
 * 2. Is not the correct Magento path for module assets
 * 3. Had a registry bug: ->registry() (getter) used instead of ->register() (setter)
 *
 * Fix: use getViewFileUrl() for the correct pub/static path and fix the registry call.
 */
class Color extends ThemeoptionColor
{
    protected function _getElementHtml(AbstractElement $element): string
    {
        $html = $element->getElementHtml();

        /** @var Registry $registry */
        $registry = $this->_coreRegistry;

        if (!$registry->registry('colorpicker_loaded')) {
            $jsUrl = $this->getViewFileUrl('GrupoAwamotos_CatalogFix::js/jscolor.js');
            $html .= '<script type="text/javascript" src="' . $this->escapeUrl($jsUrl) . '"></script>';
            $html .= '<style type="text/css">'
                . 'input.jscolor { padding-right: 44px !important; }'
                . 'input.jscolor.disabled, input.jscolor[disabled] { pointer-events: none; }'
                . '.jscolor-native-swatch { width: 28px; height: 28px; margin-left: 4px;'
                . ' vertical-align: middle; cursor: pointer; border: 1px solid #adadad;'
                . ' border-radius: 3px; padding: 0; background: none; }'
                . '</style>';
            $registry->register('colorpicker_loaded', 1);
        }

        $html .= '<script type="text/javascript">'
            . 'var el = document.getElementById("' . $this->escapeJs((string) $element->getHtmlId()) . '");'
            . 'if (el) { el.className = el.className + " jscolor"; }'
            . '</script>';

        return $html;
    }
}
