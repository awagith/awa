---
applyTo: "**/Model/**/*.php,**/Api/**/*.php,**/Helper/**/*.php"
---

# Regras para Models, APIs e Helpers (Magento 2)

## Estrutura de Classe PHP
```php
<?php
declare(strict_types=1);

namespace GrupoAwamotos\ModuleName\Model;

use Psr\Log\LoggerInterface;

class ServiceName
{
    public function __construct(
        private readonly LoggerInterface $logger,
        // outras dependências via DI
    ) {}
}
```

## Padrões Obrigatórios

### Dependency Injection
- TODAS as dependências via construtor (NUNCA ObjectManager)
- Usar `private readonly` para propriedades injetadas (PHP 8.4)
- Interfaces no construtor, não implementações concretas
- Declarar preferências em `etc/di.xml`

### Error Handling
```php
try {
    $result = $this->repository->getById($id);
} catch (NoSuchEntityException $e) {
    $this->logger->error('Entity not found', ['id' => $id, 'error' => $e->getMessage()]);
    throw $e;
} catch (\Exception $e) {
    $this->logger->critical('Unexpected error', ['error' => $e->getMessage()]);
    throw new LocalizedException(__('An error occurred. Please try again.'));
}
```

### Service Contracts
- Interfaces em `Api/` para todo service público
- Data interfaces em `Api/Data/` para DTOs
- Repository interfaces com `getById()`, `save()`, `delete()`, `getList()`

### Tipagem
- `declare(strict_types=1)` em TODOS os arquivos
- Type hints em todos os parâmetros e retornos
- NUNCA usar `mixed` sem necessidade real
- DocBlocks para tipos complexos ou arrays tipados

## NUNCA
- ObjectManager direto
- Queries SQL raw (use Repository/Collection)
- Catch vazio sem log
- Hardcodar URLs — usar variáveis de ambiente
- Ignorar rate limiting
- Retornar response raw da API (transforme para seu formato interno)
- Misturar lógica de UI com chamadas de API
