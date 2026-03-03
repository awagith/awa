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

namespace Mirasvit\Search\Ui\Index\Source;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\OptionSourceInterface;
use Mirasvit\Search\Api\Data\IndexInterface;
use Mirasvit\Search\Repository\IndexRepository;

class SortBySource implements OptionSourceInterface
{
    private $request;

    private $indexRepository;

    public function __construct(
        RequestInterface $request,
        IndexRepository  $indexRepository
    ) {
        $this->request         = $request;
        $this->indexRepository = $indexRepository;
    }

    public function toOptionArray(): array
    {
        $indexId = (int)$this->request->getParam(IndexInterface::ID);
        if (!$indexId) {
            return [];
        }

        $index = $this->indexRepository->get($indexId);
        if (!$index) {
            return [];
        }

        $instance = $this->indexRepository->getInstance($index);

        $options = [];
        foreach ($instance->getSortingOptions() as $value => $label) {
            $options[] = [
                'label' => (string)$label,
                'value' => (string)$value,
            ];
        }

        return $options;
    }
}