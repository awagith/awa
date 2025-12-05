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
cd /home/jessessh/htdocs/srv1113343.hstgr.cloud/pub
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

