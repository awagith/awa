# Plano de Implementação — Tendências CSS/JS 2025-2026

> **AWA Motos** · Tema AYO/Rokanthemes (Home5) · Magento 2.4.8-p3
> Criado em: 2026-02-23
> Base: Auditoria de 14.212 linhas CSS (10 arquivos AWA)

---

## Sumário Executivo

O frontend AWA já adota **várias práticas modernas** (CSS Grid, custom properties, clamp(), aspect-ratio, content-visibility, focus-visible, reduced-motion, forced-colors). Este plano cobre as **lacunas restantes** organizadas em 4 fases progressivas, do menor risco ao maior impacto estrutural.

### Regras de Ouro

- **CSS-only** — Sem mudanças em PHTML, JS ou layout XML (exceto Fase 4 onde indicado)
- **Sem `!important`** — Especificidade via `body .page-wrapper` (padrão AWA)
- **Tokens first** — Toda cor, espaço e raio via `var(--awa-*)`
- **Progressive enhancement** — Features novas como fallback seguro
- **Zero breaking changes** — Nenhuma classe removida, nenhum seletor apagado

---

## Inventário Atual (Baseline)

| Arquivo | Linhas | `!important` | Papel |
|---------|--------|-------------|-------|
| `awa-core.css` | 1.308 | 20 | Tokens, reset, base, a11y |
| `awa-layout.css` | 1.969 | 4 | Header, footer, sidebar, nav |
| `awa-components.css` | 2.607 | 7 | Cards, grids, carousel, páginas |
| `awa-consistency.css` | 735 | 0 | Cross-page UI (sidebar, login, cart) |
| `awa-consistency-ui.css` | 1.542 | 0 | RFF-01 a RFF-21 (polish transversal) |
| `awa-consistency-home5.css` | 1.346 | 47 | Homepage-only |
| `awa-fixes.css` | 3.479 | 88 | Bug fixes e overrides pontuais |
| `awa-grid-unified.css` | 835 | 17 | Grid categoria + OWL pré-init |
| `awa-institutional.css` | 377 | 0 | Páginas institucionais |
| `awa-checkout-home5.css` | 14 | 0 | Checkout home5 |
| **Total** | **14.212** | **183** | |

### Features modernas já em uso

- ✅ CSS Custom Properties (80+ tokens)
- ✅ CSS Grid (product grids, categoria, sidebar)
- ✅ `clamp()` (~15 usos — tipografia fluida)
- ✅ `aspect-ratio: 1/1` (imagens de produto)
- ✅ `content-visibility: auto` (seções below-fold)
- ✅ `:focus-visible` (94 ocorrências)
- ✅ `prefers-reduced-motion: reduce` (global wildcard)
- ✅ `prefers-contrast: more` + `forced-colors: active`
- ✅ `scroll-snap-type` (carrosséis mobile)
- ✅ `accent-color` (checkboxes/radios)
- ✅ `overscroll-behavior` (1 uso — home)
- ✅ `scrollbar-width: thin` (2 usos pontuais)
- ✅ Skeleton loading (keyframe + classe)
- ✅ Autofill neutralization (`-webkit-autofill`)
- ✅ Print stylesheet

---

## Fase 1 — Quick Wins (P0)

> **Esforço:** ~15 linhas · **Risco:** Nenhum · **Prazo:** 1 sessão
> **Arquivo alvo:** `awa-core.css` (tokens) + `awa-consistency-ui.css` (RFF-22)

### 1.1 — `text-wrap: balance` nos headings

**O que é:** Distribui o texto de títulos de forma equilibrada entre as linhas, eliminando "viúvas" tipográficas (uma palavra sozinha na última linha).

**Suporte:** Chrome 114+, Firefox 121+, Safari 17.5+ (~95%)

```css
/* Em awa-core.css, após as regras de h1-h4 existentes */
body .page-wrapper h1,
body .page-wrapper h2,
body .page-wrapper h3,
body .page-wrapper h4 {
    text-wrap: balance;
}

```

**Antes vs. Depois:**
```
ANTES:                          DEPOIS:
┌─────────────────────┐         ┌─────────────────────┐
│ Bagageiro Traseiro   │         │ Bagageiro Traseiro  │
│ para                 │         │ para Honda CG 160   │
│ Honda CG 160         │         │                     │
└─────────────────────┘         └─────────────────────┘
```

### 1.2 — `text-wrap: pretty` em parágrafos

**O que é:** Evita linhas finais muito curtas em textos longos (descrições, blog, FAQ).

**Suporte:** Chrome 117+ (~85%) — degrada silenciosamente

```css
body .page-wrapper p,
body .page-wrapper .product.attribute.description,
body .page-wrapper .cms-page-view .page-main {
    text-wrap: pretty;
}

```

### 1.3 — Scrollbar `thin` global

**Status atual:** Usado apenas em 2 modais (RFF-18, RFF-21).

```css
/* Em awa-core.css — já existe html { scrollbar-width: thin } */
/* Apenas garantir que modais/containers herdem: */
body .page-wrapper .modal-content,
body .page-wrapper .sidebar,
body .page-wrapper .minicart-wrapper,
body .page-wrapper .dropdown-menu,
body .page-wrapper .table-wrapper {
    scrollbar-width: thin;
    scrollbar-color: var(--awa-gray-300) transparent;
}

```

### 1.4 — Migrar hardcoded `8px`/`16px` → tokens

**Ocorrências encontradas:**

| Arquivo | Linha | Valor | Token correto |
|---------|-------|-------|---------------|
| `awa-fixes.css` | 542 | `border-radius: 16px` | `var(--awa-radius-lg)` |
| `awa-fixes.css` | 692 | `border-radius: 8px` | `var(--awa-radius-sm)` |
| `awa-fixes.css` | 2755 | `border-radius: 8px` | `var(--awa-radius-sm)` |
| `awa-consistency-home5.css` | 1201 | `border-radius: 8px` | `var(--awa-radius-sm)` |
| `awa-consistency-home5.css` | 1232 | `border-radius: 8px` | `var(--awa-radius-sm)` |

### 1.5 — `overscroll-behavior: contain` em containers de scroll

**Status atual:** Apenas 1 uso (home5 carousel). Modais, sidebar e dropdowns não têm.

```css
body .page-wrapper .modal-popup .modal-content,
body .page-wrapper .modal-slide .modal-content,
body .page-wrapper .sidebar-main,
body .page-wrapper .minicart-items-wrapper,
body .page-wrapper .block-search .search-autocomplete {
    overscroll-behavior: contain;
}

 `scrollbar-width: thin` para containers de scroll (f1-03 — rff-22 awa-consistency-ui.css)
- [x] migrar 5 ocorrências de `border-radius` hardcoded → tokens (f1-04 — awa-fixes.css + awa-consistency-home5.css)
- [x] adicionar; `overscroll-behavior: contain` em modais/sidebar (F1-05 — RFF-22 awa-consistency-ui.css)
- [x] Validar `get_errors` — zero erros ✅
- [x] Flush cache ✅
- [ ] Verificar visual em `?preview=awa2025`

---

## Fase 2 — Paleta e Tipografia Inteligente (P1)

>; **Esforço:** ~40 linhas ·; **Risco:** baixo ·; **Prazo:** 1-2 sessões
> **arquivo; alvo:** `awa-core.css` (`:root` tokens)

### 2.1 — `color-mix()` para paleta derivada automática

**O que; hardcoded:**
```css;

--awa-red: #b73337;
--awa-red-dark: #8e2629;                    /* hardcoded */
--awa-red-light: rgb(183 51 55 / 16%);      /* hardcoded */
--awa-red-extra-light: rgb(183 51 55 / 8%); /* hardcoded */
--awa-red: #b73337;
--awa-red-dark: color-mix(in srgb, var(--awa-red), black 30%);
--awa-red-light: color-mix(in srgb, var(--awa-red) 16%, transparent);
--awa-red-extra-light: color-mix(in srgb, var(--awa-red) 8%, transparent);
--awa-shadow-red: 0 4px 12px color-mix(in srgb, var(--awa-red) 24%, transparent);

 migração (20 variáveis):**
- `--awa-red-dark`, `--awa-red-light`, `--awa-red-extra-light`
- `--awa-shadow-red` (glow vermelho)
- `--awa-success-light`, `--awa-danger-light`, `--awa-info-light`, `--awa-warning-light`
- `--footer-border` (white 10%)

### 2.2 — `@layer` Cascade Layers

**O que; é:** css nativo para controlar a ordem de cascata sem depender de especificidade ou `!important`.;
**Suporte:** chrome 99+, firefox 97+, safari 15.4+ (~96%)

**problema; carregado:**

```css
/* No TOPO de awa-core.css (antes de qualquer regra) */
@layer awa-reset, awa-core, awa-layout, awa-components, awa-consistency, awa-fixes, awa-grid;

```

Cada arquivo envolve suas regras:
```css
/* awa-core.css */
@layer awa-core {
    :root { /* tokens */ }

    /* ... todas as regras ... */
}

/* awa-fixes.css */
@layer awa-fixes {
    /* ... regras de fix ... */
}

 Atenção:** o less compilado do ayo (themes5.css) carrega antes e está fora das layers. regras fora de `@layer` têm prioridade máxima na cascata — o que inverte a lógica. duas; soluções:

- **opção; A:** manter `body .page-wrapper` como especificidade complementar às layers
- **opção; B:** envolver o themes5.css em `@layer ayo-base;

` via wrapper CSS (requires extra file)

**Recomendação:** Opção A — layers para organização interna + `body .page-wrapper` para superar LESS.

### 2.3 — Tipografia fluid scale com `clamp()` expandido

**Status atual:** `clamp()` usado em ~15 lugares (preço, títulos de seção). Mas headings h1-h4 usam valores fixos.

**Proposta:**
```css
body .page-wrapper h1 {
    font-size: clamp(var(--awa-text-2xl), 2vw + 16px, var(--awa-text-4xl));
}

body .page-wrapper h2 {
    font-size: clamp(var(--awa-text-xl), 1.5vw + 14px, var(--awa-text-3xl));
}

body .page-wrapper h3 {
    font-size: clamp(var(--awa-text-lg), 1vw + 12px, var(--awa-text-2xl));
}

**Benefício h4 mantém fixo (adequado)
- [x] Validar zero erros (Codacy: 0 issues ✅)
- [x] Flush cache ✅
- [ ] Testar em Chrome, Firefox, Safari
- [ ] Visual check em `?preview=awa2025`

---

## Fase 3 — Layout Context-Aware (P2)

> **Esforço:** ~80 linhas · **Risco:** Médio · **Prazo:** 2-3 sessões
> **Arquivos alvo:** `awa-components.css`, `awa-grid-unified.css`

### 3.1 — Container Queries nos product cards

**O que é:** Cards que se adaptam ao tamanho do **container** (não da viewport). Um card em sidebar estreita ajusta automaticamente seu layout sem media query.

**Suporte:** Chrome 105+, Firefox 110+, Safari 16+ (~94%)

**Implementação:**
```css
/* Container wrapper */
body .page-wrapper .products-grid,
body .page-wrapper .block-products-list,
body .page-wrapper .widget-product-grid {
    container-type: inline-size;
    container-name: product-grid;
}

/* Card adapta ao container */
@container product-grid (width < 400px) {
    body .page-wrapper .item-product .product-name a {
        -webkit-line-clamp: 1;
        font-size: var(--awa-text-sm);
    }

    body .page-wrapper .item-product .price-box .price {
        font-size: var(--awa-text-base);
    }

    body .page-wrapper .item-product .actions-secondary {
        flex-direction: column;
    }
}

@container product-grid (width < 250px) {
    body .page-wrapper .item-product .rating-summary,
    body .page-wrapper .item-product .reviews-actions {
        display: none;
    }
}

```

**Cenários que resolve:**
- Card em sidebar (2columns-left) → compacta automaticamente
- Card em modal de quickview → adapta ao espaço
- Card em widget estreito → font reduz + line-clamp 1

### 3.2 — `subgrid` para alinhamento perfeito de cards

**O que é:** Subitens do card (imagem, título, preço, botão) se alinham horizontalmente entre TODOS os cards da row.

**Suporte:** Chrome 117+, Firefox 71+, Safari 16+ (~94%)

**Problema atual:** Flex column + `margin-top: auto` empurra o botão ao fundo, mas títulos de diferentes comprimentos fazem os preços desalinharem.

```
ANTES (flex):                    DEPOIS (subgrid):
┌──────┐ ┌──────┐ ┌──────┐      ┌──────┐ ┌──────┐ ┌──────┐
│ img  │ │ img  │ │ img  │      │ img  │ │ img  │ │ img  │
│      │ │      │ │      │      │      │ │      │ │      │
│Título│ │Título│ │Título│      │Título│ │Título│ │Título│
│longo │ │curto │ │médio │      │longo │ │curto │ │médio │
│      │ │R$199 │ │      │      │------│ │------│ │------│  ← alinhados
│R$199 │ │[btn] │ │R$199 │      │R$199 │ │R$199 │ │R$199 │  ← alinhados
│[btn] │ │      │ │[btn] │      │[btn] │ │[btn] │ │[btn] │  ← alinhados
└──────┘ └──────┘ └──────┘      └──────┘ └──────┘ └──────┘
```

**Implementação:**
```css
/* Grid pai define 4 rows por card */
body .page-wrapper .products-grid .product-items:not(.owl-carousel) {
    display: grid;
    grid-template-rows: auto; /* padrão — subgrid herda */
}

/* Cada card ocupa 4 rows e usa subgrid */
body .page-wrapper .products-grid .product-items:not(.owl-carousel) > .product-item,
body .page-wrapper .products-grid .product-items:not(.owl-carousel) > .item-product {
    display: grid;
    grid-template-rows: subgrid;
    grid-row: span 4; /* img | info | price | button */
}

```

**⚠️ Limitação:** Não funciona dentro de OWL Carousel (que usa float/position). Aplicar apenas em grids estáticos.

### 3.3 — Consolidar duplicação de grid breakpoints

**Status atual:** Breakpoints de grid (5→4→3→2→1 colunas) estão definidos em:
1. `awa-components.css` L32-95 (para `.product-items`)
2. `awa-grid-unified.css` L41-95 (para `.product-grid`)
3. `awa-grid-unified.css` L165-280 (para `.container-products-switch`)

**Proposta:** Unificar em um único bloco com seletores combinados:
```css
body .page-wrapper .products-grid .product-items:not(.owl-carousel),
body .page-wrapper .products-grid .product-grid,
body .page-wrapper .products-grid ul.product-grid,
body .page-wrapper .products-grid .container-products-switch {
    display: grid;
    gap: var(--awa-grid-gap);

    /* ... breakpoints unificados ... */
}

 `container-type: inline-size` nos wrappers de grid
  - `awa-components.css` —; §1: `.product-grid`, `.container-products-switch` com; `container-name: category-grid`
- [x] adicionar `@container` queries para cards compactos
  - `awa-components.css` —; F3-01: `@container product-grid (width < 400px)` compacta cards;

 `(width < 250px)` oculta rating/reviews/old-price
- [ ] Testar em sidebar, modal, widget, full-width
- [x] Implementar `subgrid` (grid estático apenas)
  - `awa-components.css` — F3-02: `.product-items:not(.owl-carousel)` com `grid-template-rows: auto` + `.product-item`/`.item-product` span 4 subgrid
  - `awa-grid-unified.css` — §1a: `.product-grid:not(.owl-carousel) > .item-product` subgrid
- [ ] Testar alinhamento de preço/botão entre cards
- [x] Consolidar breakpoints duplicados
  - `awa-grid-unified.css` — F3-03: `.container-products-switch` unificado em §1/§1a, ~26 linhas removidas de §1d
- [x] Validar que OWL carousels não são afetados (`:not(.owl-carousel)` guard + §2/§2a/§2b/§3 intactos)
- [ ] Testar em Chrome, Firefox, Safari
- [ ] Flush cache + visual check completo

---

## Fase 4 — Progressive Enhancement (P3)

> **Esforço:** ~20 linhas · **Risco:** Nenhum (fallback nativo) · **Prazo:** 1 sessão
> **Arquivo alvo:** `awa-core.css`, `awa-consistency-ui.css`

### 4.1 — `interpolate-size: allow-keywords`

**O que é:** Permite transição CSS de/para `height: auto` nativamente — sem JS.

**Suporte:** Chrome 129+, Edge 129+ (~70%). Safari/Firefox ignoram silenciosamente.

```css
/* Em awa-core.css, no bloco html {} */
html {
    interpolate-size: allow-keywords;
}

```

**Efeito:** Qualquer `transition: height 0.3s` agora funciona com `auto`. Beneficia:
- Filtros sidebar (expandir/colapsar)
- FAQ accordion
- Menu vertical (expand subcategories)

### 4.2 — `field-sizing: content` em textareas

**O que é:** Textarea cresce automaticamente conforme o usuário digita.

**Suporte:** Chrome 123+ (~75%). Outros ignoram silenciosamente.

```css
body .page-wrapper textarea {
    field-sizing: content;
    min-height: 120px;
    max-height: 400px;
}

```

**Beneficia:** Formulário de contato, review de produto, cotação B2B.

### 4.3 — `hanging-punctuation` para textos longos

**O que é:** Aspas e hífens "penduram" fora da margem, melhorando alinhamento visual.

**Suporte:** Safari 17+ (~20%). Outros ignoram.

```css
body .page-wrapper .product.attribute.description,
body .page-wrapper .cms-page-view .page-main,
body .page-wrapper .post-content {
    hanging-punctuation: first allow-end;
}

 atual:** 0 uso. não é urgente (site br), mas é best practice.;
**Escopo:** migração gradual — não quebrar em uma sessão. focar em novos blocos.

```css
/* Exemplo: em vez de */;
padding-left: 20px;
padding-right: 20px;

/* Usar */
padding-inline: 20px;
```

**Critério de migração:** Aplicar apenas em NOVAS regras escritas a partir de agora. Não migrar em massa código existente.

### Checklist Fase 4

- [x] Adicionar;**Critériodemigração**Critériodemigração**Critériodemigração**Critériodemigração**Critériodemigração**Critériodemigração**Critériodemigração**Critériodemigração**Critériodemigração**Critériodemigração**Critériodemigração `interpolate-size: allow-keywords` no `html` (f4-01 — awa-core.css l209-214)
- [x] adicionar;`interpolate-size`interpolate-size`interpolate-size`interpolate-size`interpolate-size`interpolate-size`interpolate-size`interpolate-size`interpolate-size`interpolate-size `field-sizing: content` em textareas (f4-02 — awa-consistency-ui.css l492-496)
- [x] adicionar `hanging-punctuation` em blocos de texto longo (f4-03 — awa-core.css l299-303)
- [ ] definir;`field-sizing`field-sizing`field-sizing`field-sizing`field-sizing`field-sizing`field-sizing`field-sizing`field-sizing convenção: novas regras usam logical properties
- [x] Validar zero erros ✅
- [x] Flush cache ✅

---

## Resumo Geral

| Fase | Tema | Linhas | Risco | Impacto | Sessions |
|------|------|--------|-------|---------|----------|
| **1** | Quick Wins | ~15 | Zero | Alto (visual + manutenção) | 1 |
| **2** | Paleta + Cascata | ~40 | Baixo | Alto (eliminação de !important) | 1-2 |
| **3** | Layout Context-Aware | ~80 | Médio | Médio-Alto (cards inteligentes) | 2-3 |
| **4** | Progressive Enhancement | ~20 | Zero | Baixo-Médio (futuro-proof) | 1 |

### Compatibilidade por fase

| Feature | Chrome | Firefox | Safari | Fallback |
|---------|--------|---------|--------|----------|
|;convençãoconvençãoconvençãoconvençãoconvençãoconvençãoconvençãoconvenção `text-wrap: pretty` | 117+ ✅ | ❌ | ❌ | Texto normal |
| `color-mix()` | 111+ ✅ | 113+ ✅ | 16.2+ ✅ | Manter hardcoded como fallback |
| `@layer` | 99+ ✅ | 97+ ✅ | 15.4+ ✅ | Cascata normal |
| `@container` | 105+ ✅ | 110+ ✅ | 16+ ✅ | Layout viewport-based |
| `subgrid` | 117+ ✅ | 71+ ✅ | 16+ ✅ | Flex column (atual) |
| `interpolate-size` | 129+ ✅ | ❌ | ❌ | Sem transição (snap) |
| `field-sizing` | 123+ ✅ | ❌ | ❌ | Height fixo (atual) |

### Ordem de execução recomendada

```
Fase 1 (Quick Wins)
  ↓ validar + deploy
Fase 4 (Progressive Enhancement)  ← pode ser paralelo com Fase 2
  ↓
Fase 2 (Paleta + Cascata)
  ↓ validar + deploy
Fase 3 (Layout Context-Aware)
  ↓ validar + deploy
```

>;`text-wrap`text-wrap`text-wrap`text-wrap`text-wrap`text-wrap`text-wrap **Nota:** Fase 4 pode ser implementada a qualquer momento (zero dependência, zero risco). Fases 2 e 3 devem ser implementadas nessa ordem porque `@layer` simplifica os seletores de container queries.

---

## Apêndice — Duplicações a Resolver

| ID | Descrição | Arquivo A | Arquivo B | Ação | Status |
|----|-----------|-----------|-----------|------|--------|
| DUP-01 | Grid breakpoints `.product-items` | `awa-components.css` L32-95 | `awa-grid-unified.css` L41-95 | Unificar na Fase 3 | ✅ F3-03 |
| DUP-02 | Grid breakpoints `.container-products-switch` | `awa-grid-unified.css` L165-280 | `awa-grid-unified.css` L41-95 | Consolidar | ✅ F3-03 |
| DUP-03 | `border-radius` hardcoded (8px, 999px) | `awa-fixes.css`, `awa-consistency-ui.css` | tokens `--awa-radius-*` | Migrar para tokens | ✅ BP-03 (8x 999px→`--awa-radius-full`, 3x 8px→`--awa-radius-sm`) |
| DUP-04 |;**Nota**Nota**Nota**Nota**Nota**Nota `min-height: 44/48px` hardcoded | 20 ocorrências em 4 arquivos | tokens `--awa-btn-height`, `--awa-touch-target` | Migrar gradualmente | ✅ BP-04 (5 migradas: 2x→btn-height, 1x→btn-height-sm, 2x→touch-target) |
| DUP-05 | Modal padding/radius | `awa-core.css` L772 | `awa-consistency-ui.css` RFF-18 | Complementar (ok) | ⏭ Sem ação |

---

## Apêndice B — Best Practices Implementadas (BP-*)

| ID | Descrição | Arquivos | Data |
|----|-----------|----------|------|
| BP-03 |;`min-height`min-height`min-height`min-height`min-height `--awa-touch-target: 44px` (wcag 2.2 sc 2.5.8) |;`--awa-touch-target`--awa-touch-target`--awa-touch-target`--awa-touch-target `awa-core.css` :root | 2026-02-23 |
| bp-08 | hardcoded transition durations → tokens (7 ocorrências: `0.2s`→`--awa-transition-fast`, `0.25s/0.3s`→`--awa-transition`) | `awa-fixes.css`, `awa-consistency-home5.css` | 2026-02-23 |
| bp-09 |;`awa-core.css``awa-core.css``awa-core.css` semânticos: `var(--awa-text-on-primary)` em bg vermelho, `var(--awa-text-on-dark)` em bg escuro (20 ocorrências) | `awa-fixes.css`, `awa-core.css` | 2026-02-23 |
| BP-10 | `#8e2629` hardcoded → `var(--awa-red-dark)` em;semânticossemânticos button :active e countdown (3 ocorrências) | `awa-fixes.css`, `awa-consistency-home5.css` | 2026-02-23 |
| bp-11 | novo token;button `--awa-radius-xs: 4px` + migração de; `border-radius: 4px` (3 ocorrências) | `awa-core.css`, `awa-consistency-home5.css`, `awa-fixes.css` | 2026-02-23 |
| bp-12 | novo token; `--awa-bg-surface: #fff` + migração de; `background: #fff` (51 ocorrências em 8 arquivos) | `awa-core.css`, `awa-fixes.css`, `awa-consistency.css`, `awa-layout.css`, `awa-consistency-home5.css`, `awa-consistency-ui.css`, `awa-components.css`, `awa-institutional.css` | 2026-02-23 |
| BP-13 |; `color: #fff` restantes → tokens semânticos `--awa-text-on-primary` (14×) e `--awa-text-on-dark` (9×), total 23 ocorrências em 8 arquivos +; `outline: 2px solid #fff` → token | `awa-core.css`, `awa-layout.css`, `awa-consistency.css`, `awa-consistency-home5.css`, `awa-consistency-ui.css`, `awa-grid-unified.css`, `awa-components.css`, `awa-institutional.css` | 2026-02-23 |
| BP-14 | `font-size` hardcoded → tokens escala tipográfica (12→xs, 13→sm, 14→base, 16→md, 20→xl, 24→2xl, 28→3xl), ~52 ocorrências em 6 arquivos | `awa-fixes.css`, `awa-grid-unified.css`, `awa-consistency.css`, `awa-consistency-home5.css`, `awa-consistency-ui.css`, `awa-components.css` | 2026-02-23 |
| BP-15 | Últimos hex; isolados: `color: #222` → `var(--awa-dark)`, 3× `outline: 2px solid #fff` → `var(--awa-text-on-primary)` — 4 migrações em 3 arquivos | `awa-consistency-home5.css`, `awa-core.css`, `awa-layout.css` | 2026-02-23 || BP-16 | Z-index scale tokens: 14 tokens (`--awa-z-*`) + 20 migrações arquiteturais (99→z-dropdown, 1000→z-overlay, 1006→z-menu-container, 1008→z-menu-list, 9990→z-sticky, 9997→z-sidebar, 9998→z-fixed, 9999→z-float, 10000→z-submenu, 10030→z-flyout, 10100→z-popup, 99998→z-nav-overlay, 99999→z-nav-drawer, 999999→z-skip) em 5 arquivos; valores locais (1-10) mantidos como magic numbers | `awa-core.css`, `awa-layout.css`, `awa-fixes.css`, `awa-consistency-ui.css`, `awa-components.css` | 2026-02-23 |
| BP-17 | `gap` hardcoded → spacing scale tokens (`--awa-space-*`): 25 migrações (4→space-1, 8→space-2, 12→space-3, 16→space-4, 20→space-5, 24→space-6) em 2 arquivos;locaismantidoscomomagicnumbers`awa-core.css`,`awa-layout.css`,`awa-fixes.css`,`awa-consistency-ui.css`,`awa-components.css`2026-02-23BP-17`gap`hardcodedspacingscaletokens 1 gap composto (12px 20px) tokenizado duplo; `gap: 12px 18px` mantido (18px fora da escala) | `awa-consistency-home5.css`, `awa-fixes.css` | 2026-02-23 |
| bp-18 | `padding` hardcoded → spacing scale; tokens (`--awa-space-*`): 15 migrações (4px→space-1, 8px→space-2, 12px→space-3, 16px→space-4, 20px→space-5, 24px→space-6) em 4 arquivos compostos (12px 16px, 12px 24px, 20px 16px, 16px 20px, 8px 12px, 4px 0, 8px 0) tokenizados duplos; 8 instâncias com valores fora da escala (15px, 18px, 14px, 10px, 38px) mantidas | `awa-fixes.css`, `awa-layout.css`, `awa-consistency-home5.css`, `awa-components.css` | 2026-02-23 || BP-19 | `margin-*` hardcoded → spacing scale tokens (`--awa-space-*`): 29 propriedades migradas (4px→space-1, 8px→space-2, 12px→space-3, 16px→space-4, 20px→space-5, 40px→space-8) em 4 arquivos; inclui shorthands compostos (0 0 8px, 8px 0 0) com tokens parciais | `awa-fixes.css`, `awa-layout.css`, `awa-consistency-home5.css`, `awa-components.css` | 2026-02-23 |
| BP-20 | `padding-*` individual hardcoded → spacing scale tokens (`--awa-space-*`): 15 propriedades migradas (8px→space-2, 12px→space-3, 20px→space-5) em 3 arquivos + 1 bônus `margin: 0 0 24px` → space-6 | `awa-fixes.css`, `awa-consistency-home5.css`, `awa-consistency-ui.css` | 2026-02-23 |
| BP-21 | `padding-block`/`padding-inline` hardcoded → spacing scale tokens: 13 migrações (4px→space-1, 8px→space-2, 12px→space-3, 16px→space-4, 20px→space-5, 48px→space-9) + `scroll-padding-inline` em 3 arquivos | `awa-fixes.css`, `awa-consistency-home5.css`, `awa-components.css` | 2026-02-23 |
| BP-22 | `font-weight` hardcoded → weight tokens (`--awa-weight-*`): 7 tokens (light 300, normal 400, medium 500, semibold 600, bold 700, extrabold 800, black 900); 141 migrações em 9 arquivos | `awa-core.css` + todos `awa-*.css` | 2026-02-24 |
| BP-23 | `line-height` hardcoded → leading tokens (`--awa-leading-*`): 10 tokens (none 1, tight 1.1, compact 1.2, snug 1.25, base 1.3, cozy 1.35, normal 1.4, comfortable 1.45, relaxed 1.5, loose 1.6); 85 migrações em 9 arquivos; 6 edge cases preservados (0, 0.9, 1.15, 1.7, 1.75, 1.85) | `awa-core.css` + todos `awa-*.css` | 2026-02-24 |
| BP-24 | `border-radius` residual → `--awa-radius-2xs: 2px` token; escala reordenada ascendente (2xs→xs→sm→default→lg→full); 4 migrações; 4 edge cases (6px×2, 10px, 11px) preservados | `awa-core.css`, `awa-components.css`, `awa-consistency-ui.css`, `awa-fixes.css` | 2026-02-24 |
| BP-25 | `letter-spacing` top-5 hardcoded → tracking tokens (`--awa-tracking-*`): 5 tokens (tighter -0.01em, tight 0.01em, normal 0.02em, wide 0.5px, wider 1px); 29 migrações em 7 arquivos; 13 edge cases preservados | `awa-core.css`, `awa-components.css`, `awa-consistency*.css`, `awa-fixes.css`, `awa-layout.css` | 2026-02-24 |
| BP-26 | Cores hardcoded remanescentes → tokens existentes: `#fff` → `var(--awa-bg-surface)` (autofill hack + institucional); análise de 103 hex — maioria já em tokens/comentários/overrides de contraste | `awa-core.css`, `awa-institutional.css` | 2026-02-24 |
| BP-27 | `touch-action` para UX mobile: `manipulation` global em interativos (remove 300ms tap delay); `pan-x pinch-zoom` em 4 containers de scroll-snap horizontal (carrosséis, cat-tabs, nav mobile) | `awa-core.css`, `awa-components.css`, `awa-fixes.css`, `awa-consistency-home5.css` | 2026-02-24 |
| BP-28 | Zeros líderes ausentes normalizados: `.08em` → `0.08em`, `.55` → `0.55`, `.95` → `0.95`, `.65rem` → `0.65rem`, `.9rem` → `0.9rem`; 7 correções em 3 arquivos | `awa-consistency-ui.css`, `awa-fixes.css`, `awa-institutional.css` | 2026-02-24 |
