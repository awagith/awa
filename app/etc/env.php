<?php
return [
    'deployment' => [
        'blue_green' => [
            'enabled' => '1'
        ]
    ],
    'force_html_minification' => 1,
    'backend' => [
        'frontName' => 'admin_49fxvZi8FI'
    ],
    'crypt' => [
        'key' => 'base64uD9ER7x9c7V0Qx1MIlBwH0AH6LWZjSu5VDJbgbCHWlg='
    ],
    'db' => [
        'table_prefix' => '',
        'connection' => [
            'default' => [
                'host' => 'localhost',
                'unix_socket' => '/var/run/mysqld/mysqld.sock',
                'dbname' => 'magento',
                'username' => 'magento',
                'password' => 'Aw4m0t0s2025Mage',
                'model' => 'mysql4',
                'engine' => 'innodb',
                'initStatements' => 'SET NAMES utf8;',
                'active' => '1',
                'driver_options' => [
                    1014 => false
                ]
            ]
        ]
    ],
    'resource' => [
        'default_setup' => [
            'connection' => 'default'
        ]
    ],
    'x-frame-options' => 'SAMEORIGIN',
    'MAGE_MODE' => 'production',
    'session' => [
        'save' => 'redis',
        'redis' => [
            'host' => '::1',
            'port' => '6379',
            'password' => 'Aw4R3d1s2026Sec',
            'timeout' => '2.5',
            'persistent_identifier' => '',
            'database' => '0',
            'compression_threshold' => '2048',
            'compression_library' => 'gzip',
            'log_level' => '1',
            'max_concurrency' => '6',
            'break_after_frontend' => '5',
            'break_after_adminhtml' => '10',
            'first_lifetime' => '600',
            'bot_first_lifetime' => '60',
            'bot_lifetime' => '7200',
            'disable_locking' => '1',
            'min_lifetime' => '60',
            'max_lifetime' => '2592000',
            'retries' => '0',
            'sentinel_master' => '',
            'sentinel_servers' => '',
            'sentinel_connect_retries' => '5',
            'sentinel_verify_master' => '0'
        ]
    ],
    'cache' => [
        'frontend' => [
            'default' => [
                'id_prefix' => 'b79_',
                'backend' => 'Magento\\Framework\\Cache\\Backend\\Redis',
                'backend_options' => [
                    'server' => '::1',
                    'database' => '1',
                    'port' => '6379',
                    'password' => 'Aw4R3d1s2026Sec',
                    'compress_data' => '1',
                    'compression_lib' => 'gzip',
                    'use_lua' => '1',
                    'use_lua_on_gc' => '1',
                    'timeout' => '3',
                    'read_timeout' => '3'
                ]
            ],
            'page_cache' => [
                'id_prefix' => 'b79_',
                'backend' => 'Magento\\Framework\\Cache\\Backend\\Redis',
                'backend_options' => [
                    'server' => '::1',
                    'database' => '2',
                    'port' => '6379',
                    'password' => 'Aw4R3d1s2026Sec',
                    'compress_data' => '0',
                    'compression_lib' => '',
                    'timeout' => '3',
                    'read_timeout' => '3'
                ]
            ]
        ],
        'allow_parallel_generation' => true,
        'graphql' => [
            'id_salt' => 'HhY2PPZTdfygajMZD1JsKliXSJMOGRMd'
        ]
    ],
    'http_cache_hosts' => [
        [
            'host' => 'localhost',
            'port' => '6081'
        ]
    ],
    'lock' => [
        'provider' => 'cache'
    ],
    'directories' => [
        'document_root_is_pub' => true
    ],
    'cache_types' => [
        'config' => 1,
        'layout' => 1,
        'block_html' => 1,
        'collections' => 1,
        'reflection' => 1,
        'db_ddl' => 1,
        'compiled_config' => 1,
        'eav' => 1,
        'customer_notification' => 1,
        'config_integration' => 1,
        'config_integration_api' => 1,
        'graphql_query_resolver_result' => 1,
        'full_page' => 1,
        'config_webservice' => 1,
        'translate' => 1
    ],
    'install' => [
        'date' => 'Sun, 17 Nov 2025 21:58:00 +0000'
    ],
    'downloadable_domains' => [
        'srv1113343.hstgr.cloud'
    ],
    'remote_storage' => [
        'driver' => 'file'
    ],
    'queue' => [
        'consumers_wait_for_messages' => 1
    ],
    'cron_consumers_runner' => [
        'cron_run' => true,
        'max_messages' => 1000,
        'consumers' => [
            'erp.order.sync.consumer',
            'erp.order.sync.retry.consumer',
            'grupoawamotos.b2b.whatsapp.consumer'
        ]
    ],
    'config' => [
        'async' => 0
    ],
    'db_logger' => [
        'output' => 'disabled',
        'log_everything' => 0,
        'query_time_threshold' => '0.001',
        'include_stacktrace' => 0
    ],
    '_system_env_locked_backup' => [
        'default' => [
            'payment' => [
                'payflowpro' => [
                    'partner' => '',
                    'user' => '',
                    'pwd' => '',
                    'sandbox_flag' => '',
                    'proxy_host' => '',
                    'proxy_port' => '',
                    'debug' => ''
                ],
                'payflow_link' => [
                    'pwd' => '',
                    'sandbox_flag' => '',
                    'use_proxy' => '',
                    'proxy_host' => '',
                    'proxy_port' => '',
                    'debug' => '',
                    'url_method' => 'GET'
                ],
                'payflow_express' => [
                    'debug' => ''
                ],
                'paypal_express_bml' => [
                    'publisher_id' => ''
                ],
                'paypal_express' => [
                    'debug' => '0',
                    'merchant_id' => ''
                ],
                'hosted_pro' => [
                    'debug' => ''
                ],
                'paypal_billing_agreement' => [
                    'debug' => '0'
                ],
                'checkmo' => [
                    'mailing_address' => null
                ],
                'payflow_advanced' => [
                    'user' => '',
                    'pwd' => '',
                    'sandbox_flag' => '',
                    'proxy_host' => '',
                    'proxy_port' => '',
                    'debug' => '',
                    'url_method' => 'GET'
                ]
            ],
            'payment_all_paypal' => [
                'paypal_payflowpro' => [
                    'settings_paypal_payflow' => [
                        'heading_cc' => '',
                        'settings_paypal_payflow_advanced' => [
                            'paypal_payflow_settlement_report' => [
                                'heading_sftp' => ''
                            ]
                        ]
                    ]
                ],
                'payflow_link' => [
                    'settings_payflow_link' => [
                        'settings_payflow_link_advanced' => [
                            'payflow_link_settlement_report' => [
                                'heading_sftp' => ''
                            ]
                        ]
                    ]
                ],
                'payments_pro_hosted_solution' => [
                    'pphs_settings' => [
                        'pphs_settings_advanced' => [
                            'pphs_settlement_report' => [
                                'heading_sftp' => ''
                            ]
                        ]
                    ]
                ],
                'express_checkout' => [
                    'settings_ec' => [
                        'settings_ec_advanced' => [
                            'express_checkout_settlement_report' => [
                                'heading_sftp' => ''
                            ]
                        ]
                    ]
                ]
            ],
            'paypal' => [
                'fetch_reports' => [
                    'ftp_login' => null,
                    'ftp_password' => null,
                    'ftp_sandbox' => '0',
                    'ftp_ip' => null,
                    'ftp_path' => null
                ],
                'general' => [
                    'business_account' => null,
                    'merchant_country' => 'BR'
                ],
                'wpp' => [
                    'api_username' => null,
                    'api_password' => null,
                    'api_signature' => null,
                    'api_cert' => '',
                    'sandbox_flag' => '0',
                    'proxy_host' => '',
                    'proxy_port' => ''
                ]
            ],
            'catalog' => [
                'search' => [
                    'elasticsearch8_server_hostname' => 'localhost',
                    'opensearch_server_hostname' => '127.0.0.1',
                    'elasticsearch8_server_port' => '9200',
                    'opensearch_server_port' => '9200',
                    'elasticsearch8_index_prefix' => 'magento2',
                    'opensearch_index_prefix' => 'magento2',
                    'elasticsearch8_enable_auth' => '0',
                    'opensearch_enable_auth' => '0',
                    'elasticsearch8_username' => '',
                    'opensearch_username' => '',
                    'elasticsearch8_password' => '',
                    'opensearch_password' => '',
                    'elasticsearch8_server_timeout' => '15',
                    'opensearch_server_timeout' => '15'
                ],
                'productalert_cron' => [
                    'error_email' => 'j@jessestain.com.br'
                ],
                'product_video' => [
                    'youtube_api_key' => ''
                ],
                'frontend' => [
                    'flat_catalog_product' => '1',
                    'flat_catalog_category' => '1'
                ]
            ],
            'admin' => [
                'url' => [
                    'custom' => '',
                    'custom_path' => ''
                ]
            ],
            'web' => [
                'unsecure' => [
                    'base_url' => 'https://srv1113343.hstgr.cloud/',
                    'base_link_url' => 'https://srv1113343.hstgr.cloud/',
                    'base_static_url' => 'https://srv1113343.hstgr.cloud/static/',
                    'base_media_url' => 'https://srv1113343.hstgr.cloud/media/'
                ],
                'secure' => [
                    'base_url' => 'https://srv1113343.hstgr.cloud/',
                    'base_link_url' => 'https://srv1113343.hstgr.cloud/',
                    'base_static_url' => 'https://srv1113343.hstgr.cloud/static/',
                    'base_media_url' => 'https://srv1113343.hstgr.cloud/media/'
                ],
                'default' => [
                    'front' => 'cms'
                ],
                'cookie' => [
                    'cookie_path' => null,
                    'cookie_domain' => 'srv1113343.hstgr.cloud'
                ],
                'url' => [
                    'redirect_to_base' => '0'
                ]
            ],
            'cataloginventory' => [
                'source_selection_distance_based_google' => [
                    'api_key' => ''
                ]
            ],
            'currency' => [
                'import' => [

                ]
            ],
            'sitemap' => [
                'generate' => [

                ]
            ],
            'trans_email' => [
                'ident_general' => [
                    'name' => 'AWA Motos',
                    'email' => 'contato@awamotos.com.br'
                ],
                'ident_sales' => [
                    'name' => 'Vendas AWA Motos',
                    'email' => 'contato@awamotos.com.br'
                ],
                'ident_support' => [
                    'name' => 'Suporte AWA Motos',
                    'email' => 'suporte@awamotos.com.br'
                ],
                'ident_custom1' => [
                    'name' => 'Contato AWA Motos',
                    'email' => 'contato@awamotos.com.br'
                ],
                'ident_custom2' => [
                    'name' => 'Atacado AWA Motos',
                    'email' => 'atacado@awamotos.com.br'
                ]
            ],
            'contact' => [
                'email' => [
                    'recipient_email' => 'contato@awamotos.com.br'
                ]
            ],
            'sales_email' => [
                'order' => [
                    'copy_to' => 'contato@awamotos.com.br'
                ],
                'order_comment' => [
                    'copy_to' => 'contato@awamotos.com.br'
                ],
                'invoice' => [
                    'copy_to' => 'contato@awamotos.com.br'
                ],
                'invoice_comment' => [
                    'copy_to' => 'contato@awamotos.com.br'
                ],
                'shipment' => [
                    'copy_to' => 'contato@awamotos.com.br'
                ],
                'shipment_comment' => [
                    'copy_to' => 'contato@awamotos.com.br'
                ],
                'creditmemo' => [
                    'copy_to' => 'contato@awamotos.com.br'
                ],
                'creditmemo_comment' => [
                    'copy_to' => 'contato@awamotos.com.br'
                ]
            ],
            'checkout' => [
                'payment_failed' => [
                    'copy_to' => null
                ]
            ],
            'google' => [
                'analytics' => [
                    'account' => ''
                ],
                'gtag' => [
                    'analytics4' => [
                        'measurement_id' => ''
                    ],
                    'adwords' => [
                        'conversion_id' => ''
                    ]
                ]
            ],
            'recaptcha_backend' => [
                'type_recaptcha' => [
                    'public_key' => '',
                    'private_key' => ''
                ],
                'type_invisible' => [
                    'public_key' => '',
                    'private_key' => ''
                ],
                'type_recaptcha_v3' => [
                    'public_key' => '',
                    'private_key' => ''
                ]
            ],
            'recaptcha_frontend' => [
                'type_recaptcha' => [
                    'public_key' => '',
                    'private_key' => ''
                ],
                'type_invisible' => [
                    'public_key' => '',
                    'private_key' => ''
                ],
                'type_recaptcha_v3' => [
                    'public_key' => '',
                    'private_key' => ''
                ]
            ],
            'system' => [
                'smtp' => [
                    'host' => 'smtp.gmail.com',
                    'port' => '465'
                ],
                'gmailsmtpapp' => [
                    'active' => '1',
                    'auth' => 'LOGIN',
                    'ssl' => 'ssl',
                    'smtphost' => 'smtp.gmail.com',
                    'smtpport' => '465',
                    'username' => 'b2b.awamotos@gmail.com',
                    'password' => '0:3:Ht4ys4D71TrokEZFg/JzGXr9uRvMGaFAgT17gGHri/eadChjgkkTHqRtG1XXP0Y=',
                    'set_reply_to' => '1',
                    'set_from' => '1'
                ],
                'full_page_cache' => [
                    'varnish' => [
                        'access_list' => 'localhost',
                        'backend_host' => 'localhost',
                        'backend_port' => '8080'
                    ],
                    'caching_application' => '2'
                ]
            ],
            'dev' => [
                'restrict' => [
                    'allow_ips' => null
                ],
                'js' => [
                    'session_storage_key' => 'mage-cache-timeout',
                    'merge_files' => '0',
                    'minify_files' => '1',
                    'enable_js_bundling' => '0'
                ],
                'css' => [
                    'merge_css_files' => '0',
                    'minify_files' => '0'
                ],
                'template' => [
                    'minify_html' => '1'
                ]
            ],
            'oauth' => [
                'consumer' => [
                    'enable_integration_as_bearer' => '1'
                ]
            ],
            'grupoawamotos_maintenance' => [
                'general' => [
                    'enabled' => '0',
                    'mode' => 'maintenance',
                    'whitelist_ips' => '162.120.185.215'
                ]
            ]
        ]
    ]
];
