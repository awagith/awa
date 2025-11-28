## Objetivo
- Concluir a tradução e padronização em PT‑BR de todos os e‑mails transacionais, mantendo boas práticas (tema/override, `{{trans}}`, i18n CSV, header/footer por `config_path`).

## Ajustes de Configuração
- Revisar Admin: `Stores > Configuration > Sales > Sales Emails` para garantir uso dos templates do tema (ou selecionar templates criados no Admin quando aplicável).
- Confirmar variáveis de loja (telefone, e‑mail, horários) e Store Information para preencher placeholders.

## Overrides Core a Implementar
- Sales (variantes guest):
  - `Magento_Sales/email/order_update_guest.html`
  - `Magento_Sales/email/invoice_update_guest.html`
  - `Magento_Sales/email/shipment_update_guest.html`
  - `Magento_Sales/email/creditmemo_update_guest.html`
- Customer:
  - `Magento_Customer/email/account_new_*` (no_password, confirmation, confirmed)
  - `Magento_Customer/email/password_reset*.html`, `password_new.html`, `change_email*`
- Newsletter:
  - `Magento_Newsletter/email/subscr_confirm.html`, `subscr_success.html`, `unsub_success.html` (alinhar com estilo do tema AYO)
- Outros usados com frequência:
  - `Magento_Wishlist/email/share_notification.html`
  - `Magento_ProductAlert/email/price_alert.html`, `stock_alert.html`
  - `Magento_Contact/email/submitted_form.html` (se a loja usar o do core em vez do tema)

## Padrões de Implementação
- Assunto com `<!--@subject {{trans "..."}} @-->` e parâmetros (ex.: `%store_name`, `%increment_id`).
- Preservar `{{var ...}}`, `{{layout ...}}`, `{{depend ...}}`, `|raw` quando necessário.
- Incluir `{{template config_path="design/email/header_template"}}` e `.../footer_template` em todos os templates.
- Terminologia consistente com o tema AYO (ex.: “Dados de cobrança”, “Forma de envio”, “Qtd”).

## i18n (CSV)
- Tema: adicionar entradas pontuais no `app/design/frontend/ayo/ayo_default/i18n/pt_BR.csv` quando houver textos recorrentes novos.
- Webkul: já ajustado; apenas revisar se novas strings dos assuntos/corpos precisam inclusão.

## Validação
- Gatilhos de teste: gerar pedido guest, atualizar status de pedido/fatura/remessa/nota, acionar newsletter e wishlist, enviar formulário de contato.
- Checar: assunto em PT‑BR, corpo, botões/links, header/footer, placeholders válidos.
- Renderização: validação visual em clientes populares (Gmail, Outlook, Apple Mail), atenção a CSS inline.

## Entregáveis
- Novos arquivos `.html` em `app/design/frontend/ayo/ayo_default/<Module>/email/` para cada template listado.
- Atualizações pontuais em `pt_BR.csv` do tema se necessário.
- Relatório com lista de templates alterados e cobertura de testes/prints.

## Segurança e Manutenção
- Não inserir dados sensíveis nos templates.
- Após mudanças: `cache:flush`, `setup:static-content:deploy -f` (se necessário), e verificação de precedência Admin vs Tema.

## Próximo Passo
- Com aprovação, implemento os overrides acima, atualizo i18n conforme necessário e apresento validação com evidências (assunto/corpo e pré‑visualizações).