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

namespace Mirasvit\Search\Service;

class CloudService
{
    const ENDPOINT = 'http://mirasvit.com/media/cloud/';

    public function getList(string $module, string $entity): array
    {
        $list = $this->request($module, $entity, 'list');

        $result = [];
        if ($list) {
            foreach ($list as $item) {
                $result[] = [
                    'value' => $item['identifier'],
                    'label' => $item['identifier'],
                ];
            }
        }

        return $result;
    }

    public function get(string $module, string $entity, string $identifier): ?string
    {
        return $this->request($module, $entity, 'get', ['identifier' => $identifier]);
    }

    private function request(string $module, string $entity, string $action, array $optional = [])
    {
        $args = [
            'module' => $module,
            'entity' => $entity,
            'action' => $action,
        ];

        $args = array_merge_recursive($args, $optional);

        $query = http_build_query($args);

        try {
            $result = json_decode(file_get_contents(self::ENDPOINT . '?' . $query), true);

            if ($result['success']) {
                return $result['data'];
            } else {
                return null;
            }
        } catch (\Exception $e) {
            return null;
        }
    }
}
