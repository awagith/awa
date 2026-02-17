---
applyTo: "**/*.phtml"
---

# Regras para Templates PHTML (Magento 2)

## Estrutura do Template
```php
<?php
/**
 * Template description
 *
 * @var \VendorName\ModuleName\Block\BlockClass $block
 */
declare(strict_types=1);
?>
<!-- HTML com escape de output -->
```

## Padrões Obrigatórios
- SEMPRE usar `$block->escapeHtml()` para output de texto
- SEMPRE usar `$block->escapeUrl()` para URLs
- SEMPRE usar `$block->escapeHtmlAttr()` para atributos HTML
- NUNCA usar `echo` direto sem escape
- NUNCA colocar lógica de negócio no template — use Block ou ViewModel
- Usar `/** @var */` para type hint do `$block`
- RequireJS para JavaScript inline via `data-mage-init` ou `x-magento-init`

## Padrão de Output
```php
<?= $block->escapeHtml($block->getTitle()) ?>
<?= $block->escapeUrl($block->getActionUrl()) ?>
<?= /* @noEscape */ $block->getChildHtml('child_block') ?>
```

## NUNCA
- `echo` sem `$block->escape*()` (XSS)
- Lógica complexa dentro do template (if/else com mais de 3 levels)
- Queries de banco no template
- ObjectManager no template
- Hardcode de URLs ou IDs de entidade

## Tema Ayo (Rokanthemes) — Contexto para Templates

> Fonte canônica: `docs/theme-ayo.md` (usar este bloco como resumo operacional para PHTML).

Resumo prático para customização PHTML (detalhes no documento canônico):

- Templates principais: `header.phtml`, `header/logo.phtml`, `footer.phtml`
- Blocos CMS frequentes: `footer_*`, `rokanthemes_custom_menu*`, `rokanthemes_vertical_menu*`, `fixed_right`
- Widget slider CMS: `{{block class="Rokanthemes\SlideBanner\Block\Slider" slider_id="homepageslider" template="slider.phtml"}}`
- Variantes `ayo_home*` podem sobrescrever header/footer

### Padrão de Submenu Customizado (Mobile)
Para abrir submenu em mobile, adicionar após `level-top`:
```html
<div class="open-children-toggle"></div>
```

### Regras ao Customizar Templates do Tema
- NUNCA alterar templates diretamente em `app/code/Rokanthemes/` — copiar para `app/design/frontend/ayo/ayo_default/`
- Manter estrutura HTML + classes CSS do tema para não quebrar estilos
- Widget de newsletter no footer: `{{block class="Magento\Newsletter\Block\Subscribe" template="subscribe.phtml"}}`
- Para override de header/footer em variantes, criar o arquivo na pasta da variante (ex: `ayo_home2/`)
