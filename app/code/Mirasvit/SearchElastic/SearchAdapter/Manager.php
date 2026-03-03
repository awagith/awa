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

namespace Mirasvit\SearchElastic\SearchAdapter;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Manager
{
    private $scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    public function status(string &$output)
    {
        $client = $this->getClient();

        if (!$client->ping()) {
            return false;
        }

        $indices = $client->cat()->indices();
        if (is_array($indices)) {
            usort($indices, function ($a, $b) {
                return strcmp($a['index'], $b['index']);
            });

            $output .= "docs count | index name" . PHP_EOL;
            $output .= "-----------|----------------------" . PHP_EOL;

            foreach ($indices as $info) {
                $count  = (string)$info['docs.count'];
                $output .= str_repeat(' ', 10 - strlen($count)) . $count . " | " . $info['index'] . PHP_EOL;
            }
        }
        $output .= PHP_EOL;

        $stats = $client->info();

        if (is_object($stats)) { // ES8
            $stats = $stats->asArray();
        }

        $output .= $this->prettyPrint($stats);

        $indices = $client->indices()->stats();
        if (is_object($indices)) { // ES8
            $indices = $indices->asArray();
        }

        $output .= $this->prettyPrint($indices);

        try {
            $mapping = $client->indices()->getMapping([
                'index' => '*',
            ]);
            if (is_object($mapping)) { // ES8
                $mapping = $mapping->asArray();
            }
            $output  .= $this->prettyPrint($mapping);

            $settings = $client->indices()->getSettings([
                'index' => '*',
            ]);
            if (is_object($settings)) { // ES8
                $settings = $settings->asArray();
            }
            $output .= $this->prettyPrint($settings);
        } catch (\Exception $e) {
            $output .= $e->getMessage();
        }

        return true;
    }

    public function getESConfig(): array
    {
        $prefix = 'elasticsearch7';
        if ($this->scopeConfig->getValue('catalog/search/engine') == 'elasticsearch8') {
            $prefix = 'elasticsearch8';
        } elseif ($this->scopeConfig->getValue('catalog/search/engine') == 'opensearch') {
            $prefix = 'opensearch';
        }

        $options = [
            'hostname'   => $this->scopeConfig->getValue('catalog/search/' . $prefix . '_server_hostname'),
            'port'       => $this->scopeConfig->getValue('catalog/search/' . $prefix . '_server_port'),
            'index'      => $this->scopeConfig->getValue('catalog/search/' . $prefix . '_index_prefix'),
            'enableAuth' => $this->scopeConfig->getValue('catalog/search/' . $prefix . '_enable_auth'),
            'username'   => $this->scopeConfig->getValue('catalog/search/' . $prefix . '_username'),
            'password'   => $this->scopeConfig->getValue('catalog/search/' . $prefix . '_password'),
            'timeout'    => $this->scopeConfig->getValue('catalog/search/' . $prefix . '_server_timeout'),
        ];

        $hostname = preg_replace('/http[s]?:\/\//i', '', $options['hostname']);
        // @codingStandardsIgnoreStart
        $protocol = parse_url($options['hostname'], PHP_URL_SCHEME);
        // @codingStandardsIgnoreEnd
        if (!$protocol) {
            $protocol = 'http';
        }

        $authString = '';
        if (!empty($options['enableAuth']) && (int)$options['enableAuth'] === 1) {
            $authString = "{$options['username']}:{$options['password']}@";
        }

        $portString = '';
        if (!empty($options['port'])) {
            $portString = ':' . $options['port'];
        }

        $host = $protocol . '://' . $authString . $hostname . $portString;

        $options['hosts'] = [$host];

        return $options;
    }

    public function reset(string &$output = ''): bool
    {
        $client = $this->getClient();

        if (!$client->ping()) {
            return false;
        }

        if ($client->cat()->indices()) {
            $indices = $client->cat()->indices();
            foreach ($indices as $index) {
                try {
                    $this->getClient()->indices()->close([
                        'index' => $index['index'],
                    ]);
                } catch (\Exception $e) {
                    $output .= $e->getMessage();
                }

                try {
                    $this->getClient()->indices()->delete([
                        'index' => $index['index'],
                    ]);
                } catch (\Exception $e) {
                    $output .= $e->getMessage();
                }
            }
        }

        $output .= $this->prettyPrint($client->indices()->delete([
            'index' => '*',
        ]));

        return true;
    }

    public function resetStore(string &$output = ''): bool
    {
        $indexPrefix = $this->getESConfig()['index'];
        $client      = $this->getClient();

        if (!$client->ping()) {
            return false;
        }

        if ($client->cat()->indices()) {
            $indices = $client->cat()->indices();
            foreach ($indices as $index) {
                if (!preg_match('/^' . $indexPrefix . '_[^_]{1}.+/', $index['index'])) {
                    continue;
                }

                try {
                    $this->getClient()->indices()->close([
                        'index' => $index['index'],
                    ]);
                } catch (\Exception $e) {
                    $output .= $e->getMessage();
                }

                try {
                    $this->getClient()->indices()->delete([
                        'index' => $index['index'],
                    ]);
                } catch (\Exception $e) {
                    $output .= $e->getMessage();
                }
            }
        }

        return true;
    }

    private function getClient()
    {
        $esConfig = $this->getESConfig();

        if (class_exists('Elastic\Elasticsearch\ClientBuilder')) { // ES8
            return \Elastic\Elasticsearch\ClientBuilder::fromConfig($esConfig, true);
        }

        return \Elasticsearch\ClientBuilder::fromConfig($esConfig, true);
    }

    private function prettyPrint(array $array, int $offset = 0): string
    {
        $str = "";
        if (is_array($array)) {
            foreach ($array as $key => $val) {
                if (is_array($val)) {
                    $str .= str_repeat(' ', $offset) . $key . ': ' . PHP_EOL . $this->prettyPrint($val, $offset + 5);
                } else {
                    $str .= str_repeat(' ', $offset) . $key . ': ' . $val . PHP_EOL;
                }
            }
        }
        $str .= '</ul>';

        return $str;
    }
}
