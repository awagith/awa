## Objetivo
- Garantir que todos os templates de e‑mail estejam 100% em PT‑BR: assunto, corpo, botões/links, rodapés e variáveis/placeholders preservados.
- Assegurar consistência terminológica e formatação adequada, com ajustes de layout quando necessário.

## Status Atual (Resumo)
- Tema AYO já possui traduções PT‑BR para templates principais de vendas e conta:
  - `app/design/frontend/ayo/ayo_default/Magento_Sales/email/order_new.html:1`
  - `app/design/frontend/ayo/ayo_default/Magento_Sales/email/invoice_new.html:1`
  - `app/design/frontend/ayo/ayo_default/Magento_Sales/email/shipment_new.html:1`
  - `app/design/frontend/ayo/ayo_default/Magento_Sales/email/creditmemo_new.html:1`
  - `app/design/frontend/ayo/ayo_default/Magento_Customer/email/account_new.html:1`
  - `app/design/frontend/ayo/ayo_default/Magento_Customer/email/password_reset_confirmation.html:1`
  - `app/design/frontend/ayo/ayo_default/Magento_Newsletter/email/subscription_confirm*.html`
  - `app/design/frontend/ayo/ayo_default/Magento_Contact/email/contact_form.html`
- Rodapé padrão ainda exibe inglês pois falta a tradução da frase:
  - `vendor/magento/module-email/view/frontend/email/footer.html:17` → `{{trans "Thank you, %store_name"}}`
  - Não há mapeamento correspondente em `app/design/frontend/ayo/ayo_default/i18n/pt_BR.csv`.
- Muitos templates core (guest/update/alert/etc.) seguem em inglês via vendor:
  - `vendor/magento/module-sales/.../email/*` (order_update, order_new_guest, invoice_update, shipment_update, etc.)
  - `vendor/magento/module-customer/.../email/*`
  - `vendor/magento/module-newsletter/.../email/*`
  - `vendor/magento/module-wishlist/.../email/share_notification.html`
  - `vendor/magento/module-product-alert/.../email/*`
- Webkul Marketplace: conteúdo usa `{{trans}}`, mas assuntos estão em inglês e algumas chaves não possuem tradução em `pt_BR.csv`:
  - Assuntos em inglês (sem `{{trans}}`):
    - `app/code/Webkul/Marketplace/view/frontend/email/order_place.html:12`
    - `.../order_invoiced.html:12`
    - `.../new_product.html:12`
    - `.../edit_product.html:12`
    - `.../product_approve.html:12`, `.../product_deny.html:12`, `.../product_unapprove.html:12`
    - `.../seller_approve.html:12`, `.../seller_disapprove.html:12`, `.../seller_deny.html:12`, `.../seller_process.html:12`
    - `.../become_seller_request.html:12`, `.../withdrawal_request.html:12`, `.../seller_transaction.html:12`
  - Chaves faltantes em `pt_BR.csv` (exemplos identificados):
    - `"Category"` (usado em `new_product.html:39`)
    - `"Qty"` (usado em itens de pedido, p.ex. `order_place.html:70`)
    - Assuntos como "Order Placed Notification To Seller" etc., caso sejam traduzidos via i18n.
    - Já existentes: `"Payment Method"`, `"Shipping Method"`, `"Item"`, `"Sku"`, várias mensagens comuns.

## Escopo e Critérios de Tradução
- Idioma alvo: PT‑BR.
- Preservar placeholders e variáveis: `{{var ...}}`, `{{trans ...}}`, `{{layout ...}}` e links gerados (`this.getUrl(...)`).
- Consistência terminológica com `app/design/frontend/ayo/ayo_default/i18n/pt_BR.csv` e `app/code/Webkul/Marketplace/i18n/pt_BR.csv`.
- Formatação segura para e‑mail (tabular, inline CSS do core) e ajustes mínimos para evitar quebras.

## Ações por Grupo de Templates
1. Rodapé padrão
- Adicionar tradução em `app/design/frontend/ayo/ayo_default/i18n/pt_BR.csv` para `"Thank you, %store_name" → "Obrigado, %store_name"`.
- Opcional: sobrescrever `footer.html` no tema se necessário, mantendo `{{trans}}` e variáveis.

2. Magento Core (Sales/Customer/Newsletter/Contact/etc.)
- Priorizar overrides no tema para templates que o cliente recebe com frequência e ainda estão em inglês:
  - `order_update*.html`, `order_new_guest.html`, `invoice_update*.html`, `shipment_update*.html` (Sales)
  - `account_new_*`, `password_reset*`, `change_email*` (Customer) quando aplicável
  - `subscr_confirm*.html`, `unsub_success.html` (Newsletter) já tratados no tema; revisar variantes guest/admin
  - `share_notification.html` (Wishlist), `price_alert.html`/`stock_alert.html` (Product Alert), `submitted_form.html` (Contact)
- Conteúdo: replicar estrutura do vendor e traduzir textos via `{{trans}}`, garantindo terminologia do tema.
- Assuntos: usar `{{trans "..."}}` no bloco `<!--@subject ... @-->` dos overrides.

3. Webkul Marketplace
- Converter assuntos para `{{trans}}` em todos os templates listados.
- Completar `app/code/Webkul/Marketplace/i18n/pt_BR.csv` com chaves faltantes:
  - `"Category" → "Categoria"`, `"Qty" → "Qtd"`, e demais labels citados nos templates.
  - Incluir mapeamentos para strings de assunto (ex.: "Order Placed Notification To Seller" → "Notificação de pedido ao vendedor").
- Revisar corpo para consistência (ex.: datas, increment_id, instruções) e manter variáveis intactas.

## Placeholders e Variáveis
- Não alterar nomes de variáveis nem a semântica de condicionais `{{depend ...}}`.
- Em links/botões, manter `|raw` apenas quando presente no original.

## Ajustes de Layout
- Revisar `th`/`td` com textos mais longos (PT‑BR) e ajustar rótulos para manter a largura das tabelas e evitar quebra desnecessária (ex.: usar "Envio" em vez de "Forma de envio" quando necessário).
- Evitar alterar CSS global; preferir labels mais concisos.

## Validação
- Pré‑visualização dos templates renderizados com dados fictícios (increment_id, nomes, endereços) para checar:
  - Assunto em PT‑BR
  - Corpo, botões e links funcionais
  - Rodapé traduzido
  - Placeholders preservados e sem HTML quebrado
- Revisão terminológica cruzada com `pt_BR.csv` e templates do tema.

## Entregáveis
- Templates core sobrescritos em `app/design/frontend/ayo/ayo_default/.../email/*.html` quando aplicável.
- Atualizações em `pt_BR.csv` do tema e do módulo Webkul.
- Relatório de pendências remanescentes (se houver) com caminhos de arquivos e pontos específicos.

## Pendências já identificadas para tradução/revisão
- Rodapé: `vendor/magento/module-email/view/frontend/email/footer.html:17` (falta mapeamento PT‑BR no tema).
- Core: múltiplos `vendor/magento/module-sales/.../email/*` (variantes update/guest) em inglês.
- Webkul: assuntos em inglês em todos os templates `app/code/Webkul/Marketplace/view/frontend/email/*.html:12` e chaves faltantes como `"Category"` e `"Qty"`.

## Próximo Passo
- Com sua confirmação, implemento os overrides/traduções e entrego validação com pré‑visualizações e relatório final.