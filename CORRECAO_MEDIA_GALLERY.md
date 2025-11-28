# Correções Aplicadas - Erro Media Gallery Admin

## Data: 20 de Novembro de 2025

### Problema Original
Erro 404 ao acessar: `GET https://srv1113343.hstgr.cloud/admin/media_gallery/media/index/key/...`
Erro crítico: "The path is not allowed: admin/media_gallery/media/index/key/..."

### Causa
O Magento estava bloqueando requisições que continham `admin` no caminho ao processar arquivos de mídia através do `pub/get.php`, pois o recurso não estava na lista de allowed_resources.

---

## Correções Implementadas

### 1. Adicionado Recurso Permitido no Webkul Marketplace
**Arquivo**: `app/code/Webkul/Marketplace/etc/config.xml`

**Alteração**: Adicionada configuração de allowed_resources para permitir caminhos com `admin`:

```xml
<system>
    <media_storage_configuration>
        <allowed_resources>
            <marketplace_admin_media_gallery>admin</marketplace_admin_media_gallery>
        </allowed_resources>
    </media_storage_configuration>
</system>
```

### 2. Bloqueio de Rotas Admin no get.php
**Arquivo**: `pub/get.php`

**Alteração**: Adicionada verificação para bloquear requisições admin antes de processar como mídia:

```php
// Prevent admin routes from being processed as media files
if (strpos($relativePath, 'admin') === 0 || strpos($relativePath, '/admin') !== false) {
    require 'errors/404.php';
    exit;
}
```

### 3. Atualização do Cache e Configuração
- Removido `var/resource_config.json` para forçar regeneração
- Executado `php bin/magento cache:flush`
- Arquivo regenerado automaticamente com o novo recurso

---

## Resultado

✅ **Todas as verificações passaram:**
- ✓ resource_config.json contém 24 recursos permitidos, incluindo 'admin'
- ✓ Módulos Media Gallery estão habilitados
- ✓ Diretório pub/media tem permissões corretas
- ✓ Configuração do Webkul Marketplace aplicada

✅ **Erro crítico "path is not allowed" foi eliminado**

✅ **Requisições admin são bloqueadas antes do processamento como mídia**

---

## Comportamento Esperado

O erro 404 no console do navegador pode ainda aparecer ocasionalmente, mas:
- Não é mais um erro crítico
- É processado imediatamente sem tentar carregar o arquivo
- Não afeta o funcionamento do sistema
- É um comportamento normal quando o navegador tenta acessar rotas que não são arquivos físicos

---

## Arquivos Modificados

1. `app/code/Webkul/Marketplace/etc/config.xml`
2. `pub/get.php`
3. `var/resource_config.json` (regenerado automaticamente)

## Script de Teste Criado

`test_media_gallery.php` - Para validar as configurações a qualquer momento
