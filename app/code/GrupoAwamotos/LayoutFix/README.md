# GrupoAwamotos_LayoutFix

## Descrição

Módulo que corrige o erro de referência de layout no painel administrativo do Magento.

## Problema Resolvido

O Magento estava apresentando o seguinte erro:

```
Broken reference: the 'notification.messages' tries to reorder itself towards 'user', 
but their parents are different: 'header.inner.right' and 'header' respectively.
```

Esse erro ocorria porque o bloco `notification.messages` no layout padrão do módulo `Magento_AdminNotification` tinha o atributo `before="user"`, mas o bloco `user` estava em um container pai diferente, causando conflito na ordenação dos blocos.

## Solução

Este módulo sobrescreve o layout `default.xml` do módulo `Magento_AdminNotification` removendo o atributo `before="user"` do bloco `notification.messages`, permitindo que ele seja renderizado normalmente sem tentar se reordenar em relação a um bloco em contexto diferente.

## Estrutura

```
app/code/GrupoAwamotos/LayoutFix/
├── etc/
│   └── module.xml
├── view/
│   └── adminhtml/
│       └── layout/
│           └── default.xml
├── registration.php
└── README.md
```

## Instalação

O módulo é habilitado automaticamente através do comando:

```bash
php bin/magento module:enable GrupoAwamotos_LayoutFix
php bin/magento setup:upgrade
php bin/magento cache:flush
```

## Dependências

- `Magento_AdminNotification` (declarado na sequência do `module.xml`)

## Compatibilidade

- Magento 2.4.x
- PHP 8.2+

## Autor

GrupoAwamotos
