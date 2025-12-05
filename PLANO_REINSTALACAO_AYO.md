# Plano de Reinstalação e Otimização do Tema Ayo

## 1. Análise Inicial e Estudo do Tema

### Estrutura do Tema
O tema Ayo é composto por um pacote base (`base_package_2.3.x`) e patches de atualização para versões mais recentes do Magento (`patch_2.4.x` e `patch_2.4.7`).
A estrutura de diretórios segue o padrão Magento 2:
- `app/code/Rokanthemes`: Módulos funcionais (Slider, Menu, Ajax, etc.).
- `app/design/frontend/ayo`: Arquivos de template (PHTML), layout (XML) e estilos (LESS/CSS).
- `lib/web/rokanthemes`: Scripts JS globais do tema.

### Módulos Identificados
Os principais módulos que compõem o ecossistema do tema são:
- **Layout & Design**: `Themeoption`, `SlideBanner`, `VerticalMenu`, `CustomMenu`.
- **Funcionalidades de Catálogo**: `Categorytab`, `ProductTab`, `Featuredpro`, `Newproduct`, `Onsaleproduct`, `BestsellerProduct`, `MostviewedProduct`.
- **Experiência do Usuário**: `AjaxSuite`, `OnePageCheckout`, `QuickView`, `LayeredAjax`, `SearchSuiteAutocomplete`.
- **Conteúdo**: `Blog`, `Testimonials`, `Faq`.

### Diagnóstico de Instalação
A instalação atual deve ser atualizada para garantir compatibilidade total com Magento 2.4.8-p3 (usando os arquivos do patch 2.4.7 como base mais próxima).
A estratégia de "Reinstalação Limpa" consiste em sobrepor os arquivos do sistema com os originais da biblioteca, garantindo que modificações incorretas sejam descartadas e a base do código esteja íntegra.

## 2. Plano de Implementação por Fases

### Fase 1: Preparação e Backup (Já realizado pelo usuário/sistema)
- Backup de arquivos e banco de dados.
- Extração dos pacotes originais para diretório temporário (`temp_theme_install`).

### Fase 2: Reinstalação Sistemática
1. **Base**: Restaurar arquivos do `base_package_2.3.x`.
2. **Patch 2.4.x**: Aplicar correções intermediárias.
3. **Patch 2.4.7**: Aplicar correções mais recentes (crítico para PHP 8.2/8.3).
4. **Limpeza**: Remover arquivos temporários.
5. **Setup**: Executar `setup:upgrade`, `di:compile` e `static-content:deploy`.

### Fase 3: Revisão e Otimização (Solicitações Específicas)

#### 3.1 Revisão de DIVs e HTML
- **Ação**: Analisar `header.phtml` e `footer.phtml` para garantir semântica HTML5 correta.
- **Otimização**: Verificar uso excessivo de `div` wrappers desnecessários e simplificar onde possível.

#### 3.2 Responsividade
- **Ação**: Verificar breakpoints em `_responsive.less`.
- **Ajuste**: Garantir que menus e grids de produtos se comportem corretamente em mobile (320px-480px) e tablet (768px).

#### 3.3 Atualização de JavaScript
- **Ação**: Revisar `theme.js` e `requirejs-config.js`.
- **Melhoria**: Garantir que scripts não bloqueiem a renderização (uso de `defer` ou carregamento via RequireJS correto).

#### 3.4 Funcionalidades
- **Teste**: Verificar funcionamento do `OnePageCheckout` e `AjaxSuite` (Adicionar ao carrinho).

#### 3.5 Aspecto Visual
- **Ação**: Validar alinhamentos e espaçamentos via CSS.

## 3. Execução da Reinstalação

A seguir, executaremos os comandos para copiar os arquivos e regenerar o ambiente.
