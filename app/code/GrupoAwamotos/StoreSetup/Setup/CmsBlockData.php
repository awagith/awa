<?php

declare(strict_types=1);

namespace GrupoAwamotos\StoreSetup\Setup;

final class CmsBlockData
{
    /**
     * Conteúdo Schema.org para a homepage.
     *
     * Observação importante: usamos aspas simples dentro das diretivas ({{store ...}}/{{media ...}}/{{config ...}})
     * para não quebrar strings JSON que usam aspas duplas.
     */
    public static function schemaOrgHomepageContent(): string
    {
        return <<<HTML
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "Como confirmo se a peça serve na minha moto?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Use a busca por aplicação e confira a descrição do produto. Se ficar em dúvida, chame no WhatsApp para confirmar compatibilidade: https://wa.me/5516997367588"
      }
    },
    {
      "@type": "Question",
      "name": "Onde vejo prazo e valor do frete?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "O prazo e o valor são calculados no carrinho/checkout, conforme CEP e itens do pedido."
      }
    },
    {
      "@type": "Question",
      "name": "Como funcionam trocas e devoluções?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Trocas e devoluções seguem a política da loja. Veja detalhes na página de Ajuda/Atendimento."
      }
    },
    {
      "@type": "Question",
      "name": "Quero comprar para revenda (B2B). Como faço?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Faça seu cadastro B2B e, se preferir, envie uma solicitação de cotação."
      }
    }
  ]
}
</script>
HTML;
    }
}
