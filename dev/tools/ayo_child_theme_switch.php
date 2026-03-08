#!/usr/bin/env php
<?php

declare(strict_types=1);

use Magento\Framework\App\Area;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Theme\Model\ResourceModel\Theme\CollectionFactory as ThemeCollectionFactory;

require dirname(__DIR__, 2) . '/app/bootstrap.php';

/**
 * Script idempotente para status/ativação/rollback do tema Ayo Home5 child.
 *
 * Uso:
 *   php dev/tools/ayo_child_theme_switch.php status --store-code default
 *   php dev/tools/ayo_child_theme_switch.php activate --store-code default
 *   php dev/tools/ayo_child_theme_switch.php rollback --store-code default
 */

function out(string $message): void
{
    fwrite(STDOUT, $message . PHP_EOL);
}

function fail(string $message): void
{
    fwrite(STDERR, '[FAIL] ' . $message . PHP_EOL);
}

/**
 * @return array{command:string, store-code:string, child-code:string, base-code:string, no-cache-clean:bool}
 */
function parseArgs(array $argv): array
{
    $command = $argv[1] ?? 'status';
    if (!in_array($command, ['status', 'activate', 'rollback'], true)) {
        throw new InvalidArgumentException('Comando inválido. Use: status|activate|rollback');
    }

    $options = [
        'store-code' => 'default',
        'child-code' => 'AWA_Custom/ayo_home5_child',
        'base-code' => 'ayo/ayo_home5',
        'no-cache-clean' => false,
    ];

    for ($i = 2, $max = count($argv); $i < $max; $i++) {
        $arg = (string) $argv[$i];
        if ($arg === '--no-cache-clean') {
            $options['no-cache-clean'] = true;
            continue;
        }

        if (!str_starts_with($arg, '--')) {
            throw new InvalidArgumentException('Opção inválida: ' . $arg);
        }

        $key = substr($arg, 2);
        if (!array_key_exists($key, $options)) {
            throw new InvalidArgumentException('Opção não suportada: --' . $key);
        }

        $value = $argv[$i + 1] ?? null;
        if (!is_string($value) || $value === '') {
            throw new InvalidArgumentException('Valor ausente para --' . $key);
        }

        $options[$key] = $value;
        $i++;
    }

    return [
        'command' => $command,
        'store-code' => (string) $options['store-code'],
        'child-code' => (string) $options['child-code'],
        'base-code' => (string) $options['base-code'],
        'no-cache-clean' => (bool) $options['no-cache-clean'],
    ];
}

/**
 * @return \Magento\Theme\Model\Theme|null
 */
function findThemeByCode(ThemeCollectionFactory $themeCollectionFactory, string $themeCode)
{
    $collection = $themeCollectionFactory->create();

    foreach ($collection as $theme) {
        $code = (string) $theme->getCode();
        $themePath = (string) $theme->getThemePath();
        $fullPath = method_exists($theme, 'getFullPath') ? (string) $theme->getFullPath() : '';

        if ($code === $themeCode || $themePath === $themeCode || $fullPath === 'frontend/' . $themeCode) {
            return $theme;
        }
    }

    return null;
}

/**
 * @return \Magento\Theme\Model\Theme|null
 */
function findThemeById(ThemeCollectionFactory $themeCollectionFactory, int $themeId)
{
    $collection = $themeCollectionFactory->create();

    foreach ($collection as $theme) {
        if ((int) $theme->getId() === $themeId) {
            return $theme;
        }
    }

    return null;
}

/**
 * @param mixed $rawValue
 */
function describeCurrentTheme($rawValue, ThemeCollectionFactory $themeCollectionFactory): string
{
    if ($rawValue === null || $rawValue === '') {
        return '(vazio / herdado)';
    }

    $rawString = (string) $rawValue;
    if (ctype_digit($rawString)) {
        $theme = findThemeById($themeCollectionFactory, (int) $rawString);
        if ($theme !== null) {
            return sprintf('%s (ID %s | %s)', $rawString, (string) $theme->getId(), (string) $theme->getCode());
        }

        return $rawString . ' (ID não resolvido)';
    }

    $theme = findThemeByCode($themeCollectionFactory, $rawString);
    if ($theme !== null) {
        return sprintf('%s (ID %s)', $rawString, (string) $theme->getId());
    }

    return $rawString . ' (código não resolvido)';
}

try {
    $args = parseArgs($argv);

    $bootstrap = Bootstrap::create(BP, $_SERVER);
    $om = $bootstrap->getObjectManager();

    /** @var State $state */
    $state = $om->get(State::class);
    try {
        $state->setAreaCode(Area::AREA_ADMINHTML);
    } catch (Throwable $e) {
        // Area code já definido no processo atual; segue normalmente.
    }

    /** @var StoreManagerInterface $storeManager */
    $storeManager = $om->get(StoreManagerInterface::class);
    /** @var ScopeConfigInterface $scopeConfig */
    $scopeConfig = $om->get(ScopeConfigInterface::class);
    /** @var WriterInterface $configWriter */
    $configWriter = $om->get(WriterInterface::class);
    /** @var TypeListInterface $cacheTypeList */
    $cacheTypeList = $om->get(TypeListInterface::class);
    /** @var ReinitableConfigInterface $reinitableConfig */
    $reinitableConfig = $om->get(ReinitableConfigInterface::class);
    /** @var ThemeCollectionFactory $themeCollectionFactory */
    $themeCollectionFactory = $om->get(ThemeCollectionFactory::class);

    $store = $storeManager->getStore($args['store-code']);
    $storeId = (int) $store->getId();
    $storeCode = (string) $store->getCode();

    $configPath = 'design/theme/theme_id';
    $currentRaw = $scopeConfig->getValue($configPath, ScopeInterface::SCOPE_STORE, $storeCode);

    out('=== AYO THEME SWITCH ===');
    out(sprintf('Store: %s (ID %d)', $storeCode, $storeId));
    out('Atual: ' . describeCurrentTheme($currentRaw, $themeCollectionFactory));

    if ($args['command'] === 'status') {
        out('Child target: ' . $args['child-code']);
        out('Base rollback: ' . $args['base-code']);
        exit(0);
    }

    $targetCode = $args['command'] === 'activate' ? $args['child-code'] : $args['base-code'];
    $targetTheme = findThemeByCode($themeCollectionFactory, $targetCode);
    if ($targetTheme === null) {
        throw new RuntimeException('Tema alvo não encontrado/registrado: ' . $targetCode);
    }

    $targetThemeId = (int) $targetTheme->getId();
    $targetThemeCode = (string) $targetTheme->getCode();

    if ((string) $currentRaw === (string) $targetThemeId || (string) $currentRaw === $targetThemeCode) {
        out(sprintf('[OK] Nenhuma alteração necessária. Tema alvo já está ativo: %s (ID %d)', $targetThemeCode, $targetThemeId));
        exit(0);
    }

    $configWriter->save($configPath, (string) $targetThemeId, ScopeInterface::SCOPE_STORES, $storeId);
    $reinitableConfig->reinit();

    if (!$args['no-cache-clean']) {
        foreach (['config', 'layout', 'block_html', 'full_page'] as $cacheType) {
            try {
                $cacheTypeList->cleanType($cacheType);
                out('[OK] Cache limpo: ' . $cacheType);
            } catch (Throwable $e) {
                out('[WARN] Falha ao limpar cache ' . $cacheType . ': ' . $e->getMessage());
            }
        }
    }

    $updatedRaw = $scopeConfig->getValue($configPath, ScopeInterface::SCOPE_STORE, $storeCode);
    out(sprintf(
        '[OK] %s aplicado: %s (ID %d) no store %s',
        strtoupper($args['command']),
        $targetThemeCode,
        $targetThemeId,
        $storeCode
    ));
    out('Atual pós-escrita: ' . describeCurrentTheme($updatedRaw, $themeCollectionFactory));
    out(sprintf(
        'Rollback command: php dev/tools/ayo_child_theme_switch.php rollback --store-code %s',
        $storeCode
    ));

    exit(0);
} catch (Throwable $e) {
    fail($e->getMessage());
    exit(1);
}
