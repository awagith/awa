# Resolução de Erro 404 com Hash

## 📋 Problema Identificado

Erro 404 para requisição de recurso com hash de 64 caracteres:
```
5ca312d115530745fa8fbfb0d8a48561d951fb7b82b60fa2ebb8a06566ca8f29/:1  
Failed to load resource: the server responded with a status of 404
```

## 🔍 Diagnóstico Realizado

### Análises Executadas
1. ✅ Verificado HTML do checkout - hash não presente
2. ✅ Verificado arquivos estáticos em `pub/static` - hash não encontrado
3. ✅ Verificado `sri-hashes.json` - hash não está listado
4. ✅ Varredura em código fonte - sem referências ao hash
5. ✅ Verificado atributos `integrity` - não utilizados no HTML

### Conclusão
O erro **NÃO** é causado pelo código servidor. A requisição está sendo gerada por:
- **Service Worker antigo/obsoleto** (PWA ou módulo de performance)
- **Extensão de navegador** (testes de integridade/segurança)
- **Cache/Script de pré-carregamento** injetado dinamicamente no cliente

## 🛠️ Soluções

### 1. Verificar Logs do Servidor

Execute o script de monitoramento:

```bash
cd /home/jessessh/htdocs/srv1113343.hstgr.cloud
chmod +x scripts/check_hash_404_errors.sh
./scripts/check_hash_404_errors.sh
```

Se os logs estiverem em outro local:
```bash
./scripts/check_hash_404_errors.sh /caminho/do/access.log
```

### 2. Limpeza no Cliente (Navegador)

#### Opção A: Página Automatizada
1. Acesse: `https://srv1113343.hstgr.cloud/clear-sw.html`
2. Clique em **"Limpar Tudo"**
3. Recarregue a página

#### Opção B: DevTools Manual

**Chrome/Edge:**
1. Abra DevTools (F12)
2. Vá em `Application` > `Service Workers`
3. Clique em "Unregister" em cada worker
4. Em `Storage` > "Clear site data"

**Firefox:**
1. Digite `about:debugging` na barra
2. Clique em "This Firefox"
3. Unregister todos os Service Workers
4. F12 > Storage > Clear All

#### Opção C: Console do Navegador
Cole no Console (F12):
```javascript
// Remover Service Workers
navigator.serviceWorker.getRegistrations().then(registrations => {
    registrations.forEach(reg => reg.unregister());
    console.log('Service Workers removidos');
});

// Limpar Caches
caches.keys().then(keys => {
    keys.forEach(k => caches.delete(k));
    console.log('Caches limpos');
});
```

### 3. Redeploy de Estáticos Magento (Preventivo)

Se suspeitar de resquícios de build antigo:

```bash
cd /home/jessessh/htdocs/srv1113343.hstgr.cloud

# Limpar cache
bin/magento cache:flush

# Remover estáticos antigos
rm -rf pub/static/frontend pub/static/adminhtml var/view_preprocessed/*

# Regenerar estáticos
bin/magento setup:static-content:deploy -f pt_BR en_US

# Limpar cache novamente
bin/magento cache:flush
```

**Tempo estimado:** 5-15 minutos dependendo do tamanho da loja.

### 4. Teste em Modo Anônimo

```
1. Abra navegador em modo anônimo/privado
2. Acesse a loja
3. Verifique se o erro persiste
```

Se o erro **NÃO** aparecer em modo anônimo → confirma que é cache/extensão local.

## 📊 Monitoramento Contínuo

### Ver Requisições Recentes
```bash
# Últimos 50 acessos com 404
tail -50 /var/log/nginx/access.log | grep ' 404 '

# Procurar hash específico
grep "5ca312d115530745fa8fbfb0d8a48561d951fb7b82b60fa2ebb8a06566ca8f29" /var/log/nginx/access.log
```

### Monitorar em Tempo Real
```bash
# Acompanhar log em tempo real
tail -f /var/log/nginx/access.log | grep --line-buffered ' 404 '
```

## 🎯 Checklist de Verificação

- [ ] Executou script `check_hash_404_errors.sh`
- [ ] Testou em modo anônimo sem extensões
- [ ] Limpou Service Workers via `clear-sw.html` ou DevTools
- [ ] Verificou se erro persiste após limpeza
- [ ] (Opcional) Executou redeploy de estáticos
- [ ] Confirmou se erro continua em outro navegador/dispositivo

## 🔧 Quando Executar Redeploy

Execute o redeploy de estáticos **somente se**:
1. O erro persiste após limpeza de SW/cache
2. Recentemente houve alteração em tema/módulos estáticos
3. Múltiplos usuários reportam o problema
4. Logs mostram padrão consistente de 404 para hashes

## 📞 Próximos Passos se Persistir

Se após todas as soluções o erro continuar:

1. **Capture HAR do navegador:**
   - DevTools > Network > Right-click > Save all as HAR
   - Analise o "Initiator" da requisição 404

2. **Verifique módulos PWA:**
   ```bash
   bin/magento module:status | grep -i pwa
   ```

3. **Verifique Cloudflare/CDN:**
   - Regras de rewrite
   - Workers configurados
   - Purge cache completo

4. **Desabilite temporariamente static signing:**
   ```bash
   bin/magento config:set dev/static/sign 0
   bin/magento cache:flush
   # Testar e depois reativar:
   bin/magento config:set dev/static/sign 1
   ```

## 📚 Arquivos de Referência

- Script de verificação: `scripts/check_hash_404_errors.sh`
- Página de limpeza SW: `pub/clear-sw.html`
- Documentação: `RESOLUCAO_HASH_404.md` (este arquivo)

## ✅ Resolução Típica

**Caso mais comum:** Service Worker obsoleto  
**Tempo de resolução:** 2-5 minutos  
**Procedimento:** Acessar `clear-sw.html` → Limpar Tudo → Recarregar

---

**Data da análise:** 21/11/2025  
**Status:** Documentado e pronto para uso
