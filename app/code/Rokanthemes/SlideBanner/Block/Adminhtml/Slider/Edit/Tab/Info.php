<?php
 
namespace Rokanthemes\SlideBanner\Block\Adminhtml\Slider\Edit\Tab;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\Data\FormFactory;
use Magento\Cms\Model\Wysiwyg\Config;
 
class Info extends Generic implements TabInterface
{
    /**
     * @var \Magento\Cms\Model\Wysiwyg\Config
     */
    protected $_wysiwygConfig;
 
    protected $_newsStatus;
    protected $_objectManager;
    protected $_systemStore;
 
   /**
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param Config $wysiwygConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        Config $wysiwygConfig,
        \Magento\Store\Model\System\Store $systemStore,
		\Magento\Framework\ObjectManagerInterface $objectManager,
        array $data = []
    ) {
        $this->_wysiwygConfig = $wysiwygConfig;
        $this->_objectManager = $objectManager;
        $this->_systemStore = $systemStore;
        parent::__construct($context, $registry, $formFactory, $data);
    }
 
    /**
     * Prepare form fields
     *
     * @return \Magento\Backend\Block\Widget\Form
     */
    protected function _prepareForm()
    {
       /** @var $model \Rokanthemes\SlideBanner\Model\Slide */
        $model = $this->_coreRegistry->registry('slider_form_data');
 
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('slider_');
        $form->setFieldNameSuffix('slider');
 
        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('General')]
        );
 
        $fieldset->addField(
            'slider_title',
            'text',
            [
                'name'        => 'slider_title',
                'label'    => __('Title'),
                'required'     => true,
            ]
        );
        $fieldset->addField(
            'slider_identifier',
            'text',
            [
                'name'        => 'slider_identifier',
                'label'    => __('Identifier'),
                'required'     => true,
            ]
        );
        $fieldset->addField(
            'slider_status',
            'select',
            [
                'name'        => 'slider_status',
                'label'    => __('Status'),
                'required'     => false,
				'values'=> [['value'=>1, 'label'=> __('Enable')], ['value'=>2, 'label'=> __('Disable')]]
            ]
        );
        $fieldset->addField(
           'store_ids',
           'multiselect',
           [
             'name'     => 'store_ids[]',
             'label'    => __('Store Views'),
             'title'    => __('Store Views'),
             'required' => true,
             'values'   => $this->_systemStore->getStoreValuesForForm(false, true),
           ]
        );
 
        $data = $model->getData();
        if(isset($data['store_ids']) && $data['store_ids'] != ''){
            $data['store_ids'] = json_decode($data['store_ids'], true);
        }
        $form->setValues($data);
        $this->setForm($form);
 
        return parent::_prepareForm();
    }
    /**
     * Prepare label for tab
     *
     * @return string
     */
    public function getTabLabel()
    {
        return __('Slider Info');
    }
 
    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return __('Slider Info');
    }
 
    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }
 
    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }
}