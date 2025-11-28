# ✅ Configuração de Email - STATUS FUNCIONANDO

> **Data:** 27/11/2025  
> **Status:** ✅ **EMAILS FUNCIONANDO**  
> **Testes realizados:** `php test_email.php` enviando para `grupoawamotos@gmail.com` (manhã e tarde)

---

## 📧 Configuração Atual (PHP mail)

### Método de Envio
- **Transport:** PHP mail() padrão (sendmail/Postfix)
- **Servidor:** Postfix local (`ubuntu-cloudpanel.localhost`)
- **Porta:** 25
- **SSL/TLS:** Desabilitado (localhost)
- **Autenticação:** Não requerida
- **MagePal Gmail SMTP:** Desativado (`system/gmailsmtpapp/active = 0`)

### Comandos Aplicados

```bash
cd /home/jessessh/htdocs/srv1113343.hstgr.cloud
php bin/magento config:set system/gmailsmtpapp/active 0
php bin/magento config:set system/smtp/disable 1
php bin/magento config:set system/smtp/transport mail
php bin/magento config:set system/smtp/host localhost
php bin/magento config:set system/smtp/port 25
php bin/magento config:set system/smtp/ssl none
php bin/magento config:set system/smtp/username ""
php bin/magento config:set system/smtp/password ""
php bin/magento config:set sales_email/general/async_sending 0
php bin/magento cache:flush config
```

> Resultado atual via `core_config_data`:
> - `system/smtp/disable = 1`
> - `system/smtp/ssl = none`
> - `sales_email/general/async_sending = 0`

---

## 📬 Remetentes Configurados (Stores > Configuration > General > Store Email Addresses)

```
Geral:     Loja Brasil <j@jessestain.com.br>
Vendas:    Vendas - Loja Brasil <j@jessestain.com.br>
Suporte:   Suporte - Loja Brasil <j@jessestain.com.br>
Contato:   Contato <j@jessestain.com.br>
```

---

## 🧪 Como Testar Email

1. **Script Magento (recomendado)**
   ```bash
   php test_email.php [destinatario@dominio.com]
   ```
   - Usa Bootstrap do Magento para buscar remetente/config do store.
   - Envia via PHP mail() e imprime instruções pós-envio.

2. **Script PHP puro**
   ```bash
   php test_php_mail.php [destinatario@dominio.com]
   ```
   - Envia mensagem mínima para validar Postfix/PHP.

3. **Admin Panel**
   - `Marketing > Communications > Email Templates > Preview > Send Email`.

4. **Fluxo real**
   - Criar pedido no frontend ou disparar email de recuperação.

---

## ⚠️ Histórico do Problema

```
Transport error while sending email: Unable to connect with STARTTLS:
stream_socket_enable_crypto(): Peer certificate CN=`ubuntu-cloudpanel.localhost'
did not match expected CN=`localhost'
```

**Causa:** Magento estava forçando SMTP com SSL/STARTTLS em `localhost:25`, mas o Postfix local não requer SSL e usa certificado autoassinado.

**Solução atual:** Desabilitar SMTP customizado e utilizar PHP mail() diretamente.

---

## 🚀 Produção (Recomendado)

Quando sair do ambiente local, configure SMTP externo (SES, SendGrid, Gmail, etc.), aplique SPF/DKIM/DMARC e reative envio assíncrono.

Exemplo Amazon SES:
```bash
php bin/magento config:set system/smtp/disable 0
php bin/magento config:set system/smtp/transport smtp
php bin/magento config:set system/smtp/host email-smtp.us-east-1.amazonaws.com
php bin/magento config:set system/smtp/port 587
php bin/magento config:set system/smtp/ssl tls
php bin/magento config:set system/smtp/username "SES_SMTP_USERNAME"
php bin/magento config:set system/smtp/password "SES_SMTP_PASSWORD"
php bin/magento config:set sales_email/general/async_sending 1
php bin/magento cache:flush config
```

Não esqueça de habilitar o cron/consumidor `async.operations.all`.

---

## 📊 Verificações Úteis

```bash
php bin/magento config:show | grep -i smtp
php bin/magento config:show | grep -i trans_email

tail -50 var/log/system.log | grep -i mail
tail -50 /var/log/mail.log

mailq            # Fila Postfix
postqueue -p     # Detalhes da fila
```

---

## 📝 Checklist

- [x] Postfix ativo
- [x] PHP mail() habilitado
- [x] Magento configurado para transporte padrão
- [x] Envio assíncrono desabilitado para testes
- [x] Scripts `test_email.php` e `test_php_mail.php` criados
- [ ] SPF/DKIM/DMARC ajustados (pendente produção)
- [ ] SMTP externo configurado (pendente produção)
- [ ] Envio assíncrono reativado (quando for para produção)

---

## ✅ Resumo

- Ambiente restaurado e reconfigurado.
- Emails enviados com sucesso em 27/11/2025 usando `php test_email.php`.
- Documentação e scripts recriados para repetir o processo rapidamente.
