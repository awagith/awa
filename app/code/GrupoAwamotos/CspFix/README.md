# GrupoAwamotos_CspFix

## Objetivo

Mitigar incidentes de CSP/SRI em produção relacionados a:

- `Unable to unserialize value. Error: Syntax error`
- Escrita/leitura concorrente do arquivo `pub/static/{context}/sri-hashes.json`

## Como funciona

Este módulo aplica um plugin em `Magento\Csp\Model\SubresourceIntegrity\Storage\File` para:

1. **Escrita atômica** do `sri-hashes.json` (escreve em arquivo temporário e faz rename).
2. **Leitura tolerante**: se o conteúdo lido não for JSON válido (ex.: leitura parcial), retorna `null` para o repositório tratar como "sem dados" ao invés de lançar exception.

## Observações

- O comportamento padrão do Magento é preservado como fallback em caso de erro no plugin.
- Não altera configurações (merge/minify) — apenas endurece o armazenamento do SRI.

