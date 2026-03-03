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

namespace Mirasvit\Search\Plugin;

use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Mirasvit\Search\Service\SynonymService;

class ValidateSynonymsPlugin
{
    private $messageManager;

    private $resultRedirectFactory;

    private $synonymService;

    public function __construct(
        MessageManagerInterface $messageManager,
        RedirectFactory $resultRedirectFactory,
        SynonymService $synonymService
    ) {
        $this->messageManager        = $messageManager;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->synonymService        = $synonymService;
    }

    public function aroundExecute($subject, \Closure $proceed)
    {
        $data = $subject->getRequest()->getPostValue();
        $synonyms = $subject->getRequest()->getParam('synonyms');

        if (isset($data['synonyms']) && $data['synonyms']) {
            if ($term = $this->synonymService->validateSynonyms($data['synonyms'])) {
                $resultRedirect = $this->resultRedirectFactory->create();
                $this->messageManager->addErrorMessage(__('Unable to save the following synonym group: %1', $data['synonyms']));
                $this->messageManager->addErrorMessage(__('The term %1 can\'t use only special symbols.', $term));
                return $resultRedirect->setPath('*/*/edit', ['group_id' => $data['group_id']]);
            }
        }

        return $proceed($subject);
    }
}
