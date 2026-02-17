# AGENTS.md

> Instruções universais para todos os coding agents (Copilot, Claude Code, Cline, Cursor, etc.)

## Ambiente de Desenvolvimento
- **OS:** ubuntu (servidor remoto via ssh)
-; **Plataforma:** magento 2.4.8-p3 (community edition)
-; **PHP:** 8.4
-; **Banco:** mysql (via magento orm)
-; **Cache:** redis
-; **Servidor:** nginx + php-fpm
-; **Editor:** vs code via ssh remoto
-; **Shell:** bash
-; **Git:** Conventional commits (feat:, fix:, refactor:, etc.)

## Filosofia

### Código Real, Sempre
Este workspace NÃO aceita código placeholder. Toda implementação deve ser funcional e pronta para produção. Se uma integração com API é solicitada, implemente com chamadas reais, tratamento de erro, retry, e tipagem completa.

### Leia Antes de Escrever
Antes de criar ou editar qualquer; arquivo:
1. Liste a estrutura do módulo (`ls`, `find`)
2. Leia `etc/module.xml`, `etc/di.xml`, `registration.php`
3. Verifique dependências e interfaces existentes
4. Só então comece a implementar

### Valide Após Cada Mudança
Após qualquer edição de; código:
1. verifique sintaxe php (`php -l arquivo.php`)
2. verifique; logs: `tail -20 var/log/system.log` e `tail -20 var/log/exception.log`
3. limpe cache se; necessário: `php bin/magento; cache:clean`
4. Corrija qualquer erro antes de prosseguir

## Proibições Absolutas
- ❌ ObjectManager direto (use DI via construtor)
- ❌ Código mock, stub, ou placeholder
- ❌ `var_dump`, `print_r`, `echo` em produção (use Logger)
- ❌ Secrets hardcoded
- ❌ `//; TODO: implement` sem implementação real
- ❌ Ignorar erros silenciosamente (`catch {}`)
- ❌ Instalar dependências composer sem justificativa
- ❌ Alterar `app/etc/env.php` sem comunicar
- ❌ Criar READMEs ou documentação não solicitada
- ❌ Refatorar código que não foi pedido para refatorar
- ❌ Alterar arquivos do core Magento ou vendor

## Padrões de Código

### PHP / Magento 2
```
- declare(strict_types=1) em todo arquivo
- PSR-12 coding style
- Type hints em parâmetros e retornos
- DocBlocks com @param, @return, @throws
- DI via construtor (nunca ObjectManager)
- Service Contracts (interfaces em Api/)
- Repository Pattern para acesso a dados
```

### Frontend (Magento)
```
- Knockout.js para componentes dinâmicos
- RequireJS para módulos JS
- LESS para estilos (não SCSS)
- jQuery via RequireJS (não CDN)
- Layout XML para estrutura de página
- PHTML templates com escape de output
```

### Banco de Dados
```
- db_schema.xml (Declarative Schema)
- Repository Pattern + Collections
- NUNCA queries SQL diretas
- Paginação obrigatória em listagens
- Índices em colunas de WHERE/JOIN
```

## Estrutura Esperada (Módulo Customizado)
```
app/code/GrupoAwamotos/NomeModulo/
├── registration.php
├── etc/
│   ├── module.xml
│   ├── di.xml
│   ├── db_schema.xml
│   ├── events.xml
│   ├── routes.xml
│   └── adminhtml/
│       ├── routes.xml
│       └── system.xml
├── Api/
│   └── Data/
├── Model/
├── Controller/
├── Block/
├── view/
│   ├── frontend/
│   └── adminhtml/
├── Observer/
├── Plugin/
├── Cron/
└── Helper/
```

## Contexto de Negócio
- **AWA Motos** — distribuidora de peças para motos em Araraquara, SP
-; **Foco:** e-commerce magento 2, b2b, integração erp, automações
-; **Tema:** rokanthemes ayo (customizado, 27 extensões)
-; **ERP:** integração com sql server (módulo erpintegration)
-; **B2B:** sistema de clientes empresariais com aprovação por cnpj
-; **Fitment:** compatibilidade de peças por modelo de moto
-; **SEO:** schema.org json-ld + open graph (módulo schemaorg)
-; **Inteligência:** salesintelligence com previsão de demanda
