#!/bin/bash
# SEO Setup - Link Building & Indexação
# Fase 4 - Dia 20 do ROADMAP

PROJECT_ROOT="/home/user/htdocs/srv1113343.hstgr.cloud"
cd "$PROJECT_ROOT" || exit 1

echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║          SEO SETUP - LINK BUILDING & INDEXAÇÃO               ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo ""

# 1. Gerar/Atualizar Sitemap XML
echo "📄 [1/5] Gerando sitemap.xml..."
bin/magento-www config:set sitemap/generate/enabled 1
bin/magento-www config:set sitemap/generate/frequency D
bin/magento-www sitemap:generate

if [ -f "pub/sitemap.xml" ]; then
    echo "   ✅ Sitemap gerado: pub/sitemap.xml"
    echo "   📊 Quantidade de URLs:"
    grep -c '<loc>' pub/sitemap.xml || echo "   0"
else
    echo "   ⚠️  Sitemap não encontrado"
fi
echo ""

# 2. Configurar robots.txt
echo "🤖 [2/5] Configurando robots.txt..."
cat > pub/robots.txt << 'EOF'
# Grupo Awamotos - Magento 2.4.8
User-agent: *
Disallow: /admin/
Disallow: /checkout/
Disallow: /customer/
Disallow: /catalogsearch/
Disallow: /wishlist/
Disallow: /pub/static/
Disallow: /var/

# Sitemaps
Sitemap: https://srv1113343.hstgr.cloud/sitemap.xml
Sitemap: https://srv1113343.hstgr.cloud/media_sitemap.xml
EOF
echo "   ✅ robots.txt configurado"
echo ""

# 3. Gerar instruções Google Search Console
echo "🔍 [3/5] Instruções Google Search Console..."
cat > relatorios/GOOGLE_SEARCH_CONSOLE_SETUP.md << 'EOF'
# Google Search Console - Setup

## 1. Adicionar Propriedade
1. Acesse: https://search.google.com/search-console
2. Clique "Adicionar propriedade"
3. Escolha tipo: **Prefixo do URL**
4. Digite: `https://srv1113343.hstgr.cloud`

## 2. Verificar Propriedade

### Método 1: Arquivo HTML (Recomendado)
```bash
# Baixar arquivo de verificação do GSC
cd /home/user/htdocs/srv1113343.hstgr.cloud/pub
wget "URL_DO_ARQUIVO_GOOGLE" -O google123abc.html
# Clicar "Verificar" no GSC
```

### Método 2: Meta Tag
Adicionar ao `<head>` da homepage:
```html
<meta name="google-site-verification" content="SEU_CODIGO_AQUI" />
```

### Método 3: DNS (TXT Record)
Adicionar no painel de DNS:
```
Nome: @
Tipo: TXT
Valor: google-site-verification=SEU_CODIGO
```

## 3. Submeter Sitemaps
1. No GSC, ir em: **Sitemaps** (menu lateral)
2. Adicionar:
   - `https://srv1113343.hstgr.cloud/sitemap.xml`
   - `https://srv1113343.hstgr.cloud/media_sitemap.xml`
3. Clicar "Enviar"

## 4. Solicitar Indexação (Páginas Principais)
1. Ir em: **Inspeção de URL**
2. Colar URLs:
   - Homepage: `https://srv1113343.hstgr.cloud/`
   - Categorias principais (5-10 URLs)
   - Artigos blog (5 URLs)
3. Clicar "Solicitar indexação" em cada

## 5. Monitoramento Semanal
- Cobertura de índice (páginas indexadas)
- Desempenho (cliques, impressões, CTR)
- Erros de rastreamento
- Dados estruturados (schema.org)

EOF
echo "   ✅ Guia criado: relatorios/GOOGLE_SEARCH_CONSOLE_SETUP.md"
echo ""

# 4. Gerar lista de backlinks white-hat
echo "🔗 [4/5] Gerando lista de oportunidades de backlinks..."
cat > relatorios/BACKLINKS_OPORTUNIDADES.md << 'EOF'
# Oportunidades de Backlinks - White Hat

## 🇧🇷 Diretórios Locais (Brasil)

### Gratuitos
- [ ] **Ache Aqui** - https://www.acheaqui.com/
- [ ] **Guia Mais** - https://www.guiamais.com.br/
- [ ] **Lista Online** - https://www.listaonline.com.br/
- [ ] **Achei Aqui** - https://www.acheiaqui.com.br/
- [ ] **Telelistas** - https://www.telelistas.net/
- [ ] **Yandex Business** - https://yandex.com/business/

### Nicho Motos
- [ ] **Motos Blog** - Contato para guest post
- [ ] **WebMotors** - Anunciar peças (backlink)
- [ ] **OLX Autos** - Perfil loja
- [ ] **iCarros** - Cadastro parceiro

## 📱 Redes Sociais (Social Signals)

### Perfis Necessários
- [ ] **Facebook Business** - https://business.facebook.com/
  - Criar página empresa
  - Adicionar link site
  - Posts semanais
  
- [ ] **Instagram Business** - https://business.instagram.com/
  - Link na bio
  - Stories com produtos
  - Reels técnicos
  
- [ ] **YouTube** - Canal "Grupo Awamotos"
  - Unboxing produtos
  - Reviews técnicos
  - Instalação acessórios
  
- [ ] **Pinterest** - Boards organizados
  - Board: "Capacetes 2025"
  - Board: "Acessórios Moto"
  - Board: "Customização"

- [ ] **LinkedIn** - Página empresa
  - Artigos técnicos
  - Novidades mercado

## 🤝 Parcerias & Guest Posts

### Blogs de Motos (Guest Post)
1. **Moto Adventure** - contato@motoadventure.com.br
   - Proposta: "Top 10 Acessórios para Viagem Longa"
   
2. **Duas Rodas** - redacao@duasrodas.com.br
   - Proposta: "Segurança: Como Escolher Equipamentos"
   
3. **WebMotors Blog** - Formulário contato
   - Proposta: "Manutenção Preventiva de Motos"

### Fornecedores/Parceiros
- [ ] **Shark Helmets** - Solicitar link em "Revendedores"
- [ ] **X11 Brasil** - Badge "Revendedor Autorizado"
- [ ] **GIVI** - Cadastro parceiro oficial
- [ ] **Shad** - Link em distribuidores

## 🎓 Fóruns & Comunidades

### Participação Ativa (Link em Assinatura)
- [ ] **Moto.com.br** - Fórum
- [ ] **Clube do Motociclista** - Membro premium
- [ ] **Reddit** - r/motoca, r/moto_br
- [ ] **Quora** - Responder perguntas sobre motos

## 📊 Monitoramento

### Ferramentas
- **Ahrefs** - Backlinks competitors
- **SEMrush** - Link gap analysis
- **Google Search Console** - Links recebidos

### Métricas
- Domain Authority (DA)
- Trust Flow (TF)
- Backlinks dofollow vs nofollow
- Anchor text diversity

## ⚠️ Evitar
- ❌ Compra de links
- ❌ Diretórios spam
- ❌ Comentários blog irrelevantes
- ❌ Link farms
- ❌ PBNs (Private Blog Networks)

EOF
echo "   ✅ Guia criado: relatorios/BACKLINKS_OPORTUNIDADES.md"
echo ""

# 5. Configurações finais SEO
echo "⚙️  [5/5] Aplicando configurações SEO finais..."

# Meta robots
bin/magento-www config:set design/head/default_robots "INDEX,FOLLOW"

# Canonical URLs
bin/magento-www config:set catalog/seo/product_canonical_tag 1
bin/magento-www config:set catalog/seo/category_canonical_tag 1

# URL rewrites
bin/magento-www config:set catalog/seo/save_rewrites_history 1

# Open Graph
bin/magento-www config:set web/seo/use_rewrites 1

echo "   ✅ Configurações SEO aplicadas"
echo ""

# Resumo
echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║                       RESUMO - SEO SETUP                      ║"
echo "╠═══════════════════════════════════════════════════════════════╣"
echo "║ ✅ Sitemap XML gerado e configurado                           ║"
echo "║ ✅ robots.txt otimizado                                        ║"
echo "║ ✅ Guia Google Search Console criado                          ║"
echo "║ ✅ Lista backlinks white-hat gerada                           ║"
echo "║ ✅ Configurações SEO aplicadas                                ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo ""
echo "📋 PRÓXIMOS PASSOS:"
echo "   1. Seguir guia: relatorios/GOOGLE_SEARCH_CONSOLE_SETUP.md"
echo "   2. Executar backlinks: relatorios/BACKLINKS_OPORTUNIDADES.md"
echo "   3. Criar Google Business Profile"
echo "   4. Configurar redes sociais (Facebook, Instagram, YouTube)"
echo "   5. Agendar posts semanais (blog + redes)"
echo ""
echo "⏱️  Tempo estimado: 4 horas"
echo "🎯 Meta: 15+ backlinks em 30 dias"
echo ""
