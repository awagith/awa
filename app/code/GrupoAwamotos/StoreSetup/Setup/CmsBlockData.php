
    /**
     * Schema.org Homepage Block
     */
    public static function schemaOrgHomepageContent()
    {
        return '
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "Grupo Awamotos",
  "url": "https://srv1113343.hstgr.cloud/",
  "logo": "https://srv1113343.hstgr.cloud/media/logo/default/logo.png",
  "description": "Especialistas em peças e acessórios para motocicletas. Capacetes, baús, luvas, escapamentos e muito mais.",
  "contactPoint": {
    "@type": "ContactPoint",
    "telephone": "+55-11-99999-9999",
    "contactType": "Customer Service",
    "areaServed": "BR"
  },
  "address": {
    "@type": "PostalAddress",
    "addressLocality": "São Paulo",
    "addressRegion": "SP",
    "addressCountry": "BR"
  },
  "sameAs": [
    "https://facebook.com/grupoawamotos",
    "https://instagram.com/grupoawamotos"
  ]
}
</script>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "name": "Grupo Awamotos",
  "url": "https://srv1113343.hstgr.cloud/",
  "potentialAction": {
    "@type": "SearchAction",
    "target": "https://srv1113343.hstgr.cloud/catalogsearch/result/?q={search_term_string}",
    "query-input": "required name=search_term_string"
  }
}
</script>';
    }
