<?php

// Injetar Schema.org via Hook JavaScript (temporário)
echo '
<!-- Schema.org Direct Injection Test -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    var schemaScript = document.createElement("script");
    schemaScript.type = "application/ld+json";
    schemaScript.innerHTML = JSON.stringify({
        "@context": "https://schema.org",
        "@type": "Organization", 
        "name": "Grupo Awamotos",
        "url": "https://srv1113343.hstgr.cloud/",
        "description": "Especialistas em peças e acessórios para motocicletas"
    });
    document.head.appendChild(schemaScript);
    console.log("Schema.org Organization injetado via JavaScript");
});
</script>
';

