---
applyTo: "**/etc/db_schema.xml,**/Setup/**/*.php,**/Model/ResourceModel/**/*.php"
---

# Regras para Banco de Dados (Magento 2)

## Declarative Schema (db_schema.xml)
- Nomes de tabelas: `vendor_module_entity` (snake_case com prefixo)
- Colunas: snake_case (`entity_id`, `created_at`, `updated_at`)
- Sempre incluir `entity_id` (PK auto-increment), `created_at`, `updated_at`
- Usar `identity="true"` para auto-increment
- Foreign keys com `referenceTable` e `onDelete`
- Índices em colunas de WHERE, JOIN, ORDER BY

## Padrão de Tabela
```xml
<schema xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:Setup/Declaration/Schema/etc/schema.xsd">
    <table name="grupoawamotos_module_entity" resource="default" engine="innodb" comment="Entity Table">
        <column xsi:type="int" name="entity_id" unsigned="true" nullable="false" identity="true" comment="Entity ID"/>
        <column xsi:type="varchar" name="name" nullable="false" length="255" comment="Name"/>
        <column xsi:type="timestamp" name="created_at" on_update="false" nullable="false" default="CURRENT_TIMESTAMP" comment="Created At"/>
        <column xsi:type="timestamp" name="updated_at" on_update="true" nullable="false" default="CURRENT_TIMESTAMP" comment="Updated At"/>
        <constraint xsi:type="primary" referenceId="PRIMARY">
            <column name="entity_id"/>
        </constraint>
    </table>
</schema>
```

## Acesso a Dados
- Repository Pattern para CRUD (`Api/RepositoryInterface`)
- Collections para queries complexas
- `SearchCriteriaBuilder` para filtros dinâmicos
- NUNCA queries SQL diretas — usar Resource Model
- Paginação obrigatória: `setPageSize()` + `setCurPage()`

## Migrations / Schema Changes
- Usar `db_schema.xml` (Declarative Schema)
- Gerar whitelist: `php bin/magento setup:db-declaration:generate-whitelist`
- NUNCA editar `db_schema_whitelist.json` manualmente
- Rodar `php bin/magento setup:upgrade` após mudanças (avisar antes)
