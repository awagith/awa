# VLibras - Guia de Configuração

## 📋 O que é VLibras?
VLibras é o widget oficial do governo brasileiro que traduz automaticamente conteúdo em português para **Língua Brasileira de Sinais (Libras)** através de um avatar 3D.

- **Desenvolvedor**: Ministério da Gestão e da Inovação em Serviços Públicos
- **Website**: https://vlibras.gov.br
- **Licença**: Open Source (LGPLv3)
- **Custo**: Totalmente gratuito

---

## 🎯 Onde Encontrar no Front

O widget aparece no **canto inferior direito** de todas as páginas da loja como um ícone azul circular.

- **Desktop**: Posicionado em `bottom: 80px, right: 10px`
- **Mobile**: Posicionado em `bottom: 100px, right: 5px`
- **Carregamento**: Lazy loading (~2 segundos após carregamento da página OU na primeira interação do usuário)

### Como Usar
1. Acesse qualquer página da loja
2. Aguarde 2-3 segundos ou role a página
3. Procure o ícone azul no canto inferior direito
4. Clique para ativar o tradutor de Libras

---

## ⚙️ Configuração no Admin

### Acessar as Configurações
1. **Painel Admin** → `Stores` (Lojas)
2. → `Configuration` (Configuração)
3. → `General` (Geral)
4. → **VLibras** (nova seção)

### Opções Disponíveis
- **Ativar VLibras**: Sim / Não (padrão: **Sim**)
  - Quando desabilitado, o widget não aparece no front
  - Quando habilitado, o widget carrega em todas as páginas

### Como Alterar
1. Navegue até `Stores > Configuration > General > VLibras`
2. Altere "Ativar VLibras" para **Não** (para desabilitar)
3. Clique em **Save Config** (Salvar Configuração)
4. Limpe o cache (pode fazer via Admin ou CLI)

```bash
# Via Terminal (Opcional)
php bin/magento cache:flush
```

---

## 📁 Arquivos Técnicos

### Módulo Criado: `GrupoAwamotos_Vlibras`

Estrutura de pastas:
```
app/code/GrupoAwamotos/Vlibras/
├── etc/
│   ├── acl.xml                    # Permissões no Admin
│   ├── config.xml                 # Config padrão (habilitado)
│   ├── module.xml                 # Declaração do módulo
│   └── adminhtml/
│       └── system.xml             # Formulário de config no Admin
├── registration.php               # Registro do módulo
```

### Template do Widget
```
app/design/frontend/ayo/ayo_default/Magento_Theme/templates/html/vlibras.phtml
```

Contém:
- HTML do widget VLibras
- CSS de posicionamento (z-index, bottom, right)
- Animação fade-in
- JavaScript de lazy loading com nonce para CSP

### Layout
```
app/design/frontend/ayo/ayo_default/Magento_Theme/layout/default.xml
```

Bloco condicionado por:
```xml
ifconfig="grupoawamotos_vlibras/general/enabled"
```

### Whitelist CSP
```
app/design/frontend/ayo/ayo_default/etc/csp_whitelist.xml
```

Libera os domínios:
- `https://vlibras.gov.br`
- `https://cdn.jsdelivr.net`

---

## 🔧 Recursos Implementados

### ✅ Lazy Loading
- Script carrega 2 segundos após o DOM OU
- Na primeira interação do usuário (scroll, mouse, toque)
- **Benefício**: Não impacta o LCP (Largest Contentful Paint)

### ✅ Posicionamento Inteligente
- Desktop: `bottom: 80px, right: 10px`
- Mobile: `bottom: 100px, right: 5px` (não sobrepõe navegação mobile)
- Z-index: `9999` (fica sempre visível)

### ✅ Segurança (CSP - Content Security Policy)
- Script validado com **nonce**
- Domínios whitelistados no CSP do tema
- Compatível com políticas de segurança estritas

### ✅ Responsividade
- Animação suave de entrada (fade-in)
- Ajustes automáticos para mobile
- Tratamento de erros no console

---

## 📊 Monitoramento

Para verificar se está funcionando:

```bash
# 1. Verificar se o HTML está sendo renderizado
curl -s "https://srv1113343.hstgr.cloud/" | grep "vw-access-button"

# 2. Verificar CSP headers
curl -I "https://srv1113343.hstgr.cloud/" | grep -i "content-security-policy"

# 3. Verificar se o CDN do VLibras está online
curl -I "https://vlibras.gov.br/app/vlibras-plugin.js"
```

---

## 🚨 Possíveis Problemas & Soluções

### Widget não aparece
**Causa**: Configuração desabilitada no Admin
**Solução**:
```
Stores > Configuration > General > VLibras > Ativar VLibras = Sim
```

### Widget aparece mas não funciona
**Causa**: CSP bloqueando o script
**Solução**: Certifique-se que `vlibras.gov.br` está na whitelist CSP
```bash
php bin/magento cache:flush
```

### Erro no console: "Failed to load resource"
**Causa**: CDN do VLibras offline (raro)
**Solução**: Aguarde até que o CDN retorne online. VLibras é mantido pelo governo federal.

---

## 📞 Suporte

- **VLibras Oficial**: https://vlibras.gov.br
- **Documentação**: https://github.com/spbgovbr-vlibras
- **Repositório**: https://github.com/spbgovbr-vlibras/vlibras-plugin

---

## 📝 Changelog

| Data | Versão | Descrição |
|------|--------|-----------|
| 2025-12-05 | 1.0.0 | Criação inicial do módulo com suporte a toggle no Admin e lazy loading |

---

## ✨ Próximas Melhorias (Opcionais)

- [ ] Opção de posição (canto inferior esquerdo)
- [ ] Opção de ativar/desativar por store view
- [ ] Relatório de uso (estatísticas de cliques)
- [ ] Integração com Google Analytics
