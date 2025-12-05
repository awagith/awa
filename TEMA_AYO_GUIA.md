# 🎨 Guia do Tema Ayo - Grupo Awamotos

## 📊 Status Atual
- **Tema Ativo**: `ayo/ayo_default` (ID: 20)
- **Módulos Rokanthemes**: 27 módulos habilitados
- **Temas Disponíveis**: 23 variações (ayo_default, ayo_home2-16, versões RTL)

---

## 🚀 Tarefas Principais (Ordem de Prioridade)

### 1. ✅ Importar Conteúdo CMS Base
**Status**: Pendente  
**Local**: Admin > Rokanthemes > Import and Export

**Ações**:
```bash
# Os blocos e páginas podem ser importados via interface administrativa
# Acessar: Rokanthemes > Import and Export
# Clicar em "Import Block" e "Import Page"
```

**Blocos CMS Principais**:
- `home_slider` - Slider principal da homepage
- `home_featured` - Produtos em destaque
- `footer_info` - Informações do rodapé
- `footer_menu` - Menu do rodapé
- `social_block` - Redes sociais
- `footer_payment` - Métodos de pagamento

### 2. 🎯 Configurar Homepage Padrão
**Status**: Pendente  
**Local**: Stores > Configuration > General > Web > Default Pages

**Comando Alternative**:
```bash
php bin/magento config:set web/default/cms_home_page home
php bin/magento cache:flush
```

### 3. 🖼️ Configurar Slider Principal
**Status**: Pendente  
**Local**: Rokanthemes > Manager Slider

**Opções Disponíveis**:
- Autoplay
- Navigation (botões next/prev)
- Stop On Hover
- Pagination
- Items (quantidade de itens)
- Velocidades (rewind, pagination, slide)
- Responsividade (Desktop, Tablet, Mobile)

**Adicionar Slides**:
- Rokanthemes > Manage Slider Items
- Upload de imagens
- Links dos banners
- Textos personalizados

### 4. 🎨 Personalizar Tema
**Status**: Pendente  
**Local**: Rokanthemes > Theme Settings

#### 4.1 Configurações Gerais
- Auto Render Style Less
- Page Width
- Copyright

#### 4.2 Fontes
- Custom Font: Yes/No
- Basic Font Size
- Basic Font Family (Google Fonts ou custom)

#### 4.3 Cores Personalizadas
- Text Color
- Link Color / Hover
- Button Colors / Hover

### 5. 🏷️ Configurar Logo e Favicon
**Status**: Pendente  
**Local**: Content > Design > Configuration

**Ações**:
1. Editar tema ativo
2. HTML Head > Upload Favicon
3. Header > Logo Image
4. Logo Attribute Width/Height

**Sticky Header**:
- Rokanthemes > Theme Settings > Sticky Header
- Upload Sticky Logo separado

### 6. 📋 Customizar Menus

#### 6.1 Menu Horizontal (CustomMenu)
**Local**: Rokanthemes > Custom Menu

**Configurações**:
- Enable: Yes
- Default Menu Type
- Visible Menu Depth (níveis de submenu)
- Static Block (before/after)
- Category Labels (hot, new, sale)

**Customizar Submenu**:
- Catalog > Categories
- Escolher categoria
- Custom Menu Options:
  - Menu Type (Classic, Full width, Static width)
  - Sub Category Columns
  - Float (left/right)
  - Icon Image ou Font Icon Class

#### 6.2 Menu Vertical
**Local**: Rokanthemes > Vertical Menu

**Configurações**:
- Limit show more Cat (quantidade inicial)
- Static Block (before/after)

### 7. 📦 Configurar Módulos de Produtos

#### 7.1 ProductTab
**Local**: Rokanthemes > Configuration > ProductTab

**Tipos Disponíveis**:
- New Products (com data "Set Product as New")
- Onsale Products (special price)
- Bestseller Products (automático)
- Mostviewed Products (automático)
- Featured Products (flag "Featured Product = Yes")
- Price Countdown (special price + datas + flag)

**Configurações por Tipo**:
- Enable/Disable
- Auto Carousel
- Title/Description
- Show Price/Add To Cart/Wishlist/Rating
- Qty Products
- Items (Default, Desktop, Tablet, Mobile)
- Navigation/Controls

#### 7.2 Category Tab
**Local**: Rokanthemes > Configuration > Category Tab

**Widget**:
- Content > Block
- Insert Widget > Category Tab
- Configurar Category IDs
- Price Range
- Colunas e responsividade

### 8. 🛒 One Page Checkout
**Status**: Pendente  
**Local**: Rokanthemes > One Page Checkout > Configuration

**Opções**:
- Enable Terms and Conditions
- Checkbox Text
- Checkbox Content
- Title/Content Warning

### 9. 🎯 Layered Ajax
**Status**: Pendente  
**Local**: Store > Configuration > Rokanthemes > Layered Ajax

**Configurações**:
- Enable/Disable
- Open All Tab
- Use Price Range Sliders

### 10. 💬 Testimonials
**Local**: Rokanthemes > Testimonials

**Ações**:
1. Settings: habilitar módulo, config carousel
2. Manage Testimonial: adicionar depoimentos

### 11. 📝 Blog Posts
**Local**: Rokanthemes > Blog

**Ações**:
1. Blog Settings: habilitar, title slider, sidebar
2. Posts: adicionar novos posts

### 12. 📰 Newsletter Popup
**Local**: Rokanthemes > Theme Settings > Newsletter Popup

**Opções**:
- Enable/Disable
- Width/Height
- Background Color ou Image
- Textos personalizados

---

## 🔧 Comandos Úteis

### Após Modificações no Tema
```bash
cd /home/jessessh/htdocs/srv1113343.hstgr.cloud

# Modo Developer (para testes)
php bin/magento deploy:mode:set developer

# Limpar caches e conteúdo estático
rm -rf pub/static/frontend/* pub/static/_requirejs/*
rm -rf var/view_preprocessed/*
rm -rf generated/code/*

# Recompilar e reindexar
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy pt_BR -f --jobs=4
php bin/magento indexer:reindex
php bin/magento cache:flush

# Permissões
chmod -R 755 var/ pub/static/ pub/media/ generated/
```

### Voltar para Modo Produção
```bash
php bin/magento deploy:mode:set production
php bin/magento setup:static-content:deploy pt_BR -f --jobs=4
```

### Verificar Erros
```bash
tail -f var/log/system.log
tail -f var/log/exception.log
```

---

## 📁 Estrutura de Arquivos Importantes

### Templates do Tema
```
app/design/frontend/ayo/ayo_default/
├── Rokanthemes_Themeoption/
│   └── templates/
│       └── html/
│           ├── header.phtml  # Estrutura do header
│           └── footer.phtml  # Estrutura do footer
├── Magento_Theme/
├── Magento_Catalog/
└── web/
    └── css/
        └── source/
            └── _theme.less  # Estilos personalizados
```

### Patches Aplicados
```
app/patch_2.4.7/  # Patch já aplicado para Magento 2.4.7+
```

### Módulos Rokanthemes
```
app/code/Rokanthemes/
├── AjaxSuite/          # AJAX para carrinho/wishlist
├── BestsellerProduct/   # Produtos mais vendidos
├── Blog/                # Sistema de blog
├── Brand/               # Gerenciamento de marcas
├── Categorytab/         # Tabs de categorias
├── CustomMenu/          # Menu horizontal customizado
├── Faq/                 # FAQ
├── Featuredpro/         # Produtos em destaque
├── Instagram/           # Feed Instagram
├── LayeredAjax/         # Filtros AJAX
├── MostviewedProduct/   # Produtos mais vistos
├── Newproduct/          # Novos produtos
├── OnePageCheckout/     # Checkout simplificado
├── Onsaleproduct/       # Produtos em oferta
├── PriceCountdown/      # Contagem regressiva
├── ProductTab/          # Tabs de produtos
├── QuickView/           # Visualização rápida
├── RokanBase/           # Módulo base
├── SearchSuiteAutocomplete/ # Busca autocomplete
├── SearchbyCat/         # Busca por categoria
├── SlideBanner/         # Gerenciador de sliders
├── StoreLocator/        # Localizador de lojas
├── Superdeals/          # Super ofertas
├── Testimonials/        # Depoimentos
├── Themeoption/         # Opções do tema
├── Toprate/             # Produtos mais bem avaliados
└── VerticalMenu/        # Menu vertical
```

---

## 🎯 Tipos de Produtos Especiais

### New Products
```
Catalog > Products > Editar Produto
Special From Date: definir data
```

### Onsale Products
```
Catalog > Products > Editar Produto
Advanced Pricing > Special Price
```

### Price Countdown Products
```
Catalog > Products > Editar Produto
Advanced Pricing > Special Price
Special Price From Date
Special Price To Date
Show Price Count Down: Yes
```

### Featured Products
```
Catalog > Products > Editar Produto
Featured Product: Yes
```

### Bestseller & Mostviewed
Automático - baseado em vendas e visualizações

---

## 🔗 Links Úteis

- **Documentação Oficial**: https://ayo.nextsky.co/documentation/
- **Suporte**: https://support.nextsky.co/
- **Demo Homepages**: 16 variações disponíveis (ayo_home2 até ayo_home16)

---

## 📝 Notas de Integração Brasil

Este tema Ayo está integrado com:
- ✅ `GrupoAwamotos_StoreSetup` - CMS blocks e páginas
- ✅ `GrupoAwamotos_Fitment` - Sistema de busca por aplicação
- ✅ Localização `pt_BR`
- ✅ Timezone `America/Sao_Paulo`
- ✅ Moeda `BRL`

### Comando Personalizado
```bash
# Recriar CMS blocks e páginas do tema Ayo adaptadas ao Brasil
php bin/magento grupoawamotos:store:setup

# Este comando já cria os blocos necessários:
# - home_slider
# - home_featured
# - footer_info
# - footer_menu
# E configura opções do Rokanthemes
```

---

## ⚠️ Importante

1. **Sempre faça backup** antes de modificações grandes
2. **Use modo developer** para testes, production em produção
3. **Limpe caches** após cada modificação no tema
4. **Redeploy static content** após editar CSS/JS/LESS
5. **Teste responsividade** em todos os breakpoints

---

## 🎨 Próximos Passos Recomendados

1. [ ] Importar blocos CMS via Rokanthemes > Import/Export
2. [ ] Configurar slider principal com imagens do catálogo
3. [ ] Personalizar cores e fontes em Theme Settings
4. [ ] Upload logo e favicon
5. [ ] Configurar menu horizontal e vertical
6. [ ] Habilitar ProductTab para produtos em destaque
7. [ ] Configurar One Page Checkout
8. [ ] Ativar Newsletter Popup
9. [ ] Testar responsividade e performance
10. [ ] Deploy final em modo production

---

**Gerado em**: 28/11/2025  
**Base**: Documentação oficial Ayo Theme + Setup GrupoAwamotos
