#!/bin/bash
# Script para criar artigo demo no blog
# Artigo: "Guia Completo: Como Escolher o Capacete Ideal"

PROJECT_ROOT="/home/jessessh/htdocs/srv1113343.hstgr.cloud"
cd "$PROJECT_ROOT" || exit 1

echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║     CRIANDO ARTIGO DEMO - BLOG ROKANTHEMES                   ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo ""

# Criar arquivo com conteúdo do artigo
cat > var/tmp/artigo_capacete.html << 'ARTIGO'
<h2>Escolher o Capacete Ideal: Segurança em Primeiro Lugar</h2>

<p>O capacete é o equipamento de segurança mais importante para qualquer motociclista. Escolher o modelo correto pode literalmente salvar sua vida em caso de acidente. Neste guia completo, vamos te ajudar a entender todos os aspectos importantes na hora de comprar seu capacete.</p>

<h3>1. Tipos de Capacete</h3>

<h4>Capacete Fechado (Integral)</h4>
<p>O capacete fechado oferece a <strong>máxima proteção</strong>, cobrindo toda a cabeça e o queixo. É o tipo mais recomendado para uso em estradas e velocidades altas.</p>

<ul>
<li>✅ Proteção completa (cabeça + queixo)</li>
<li>✅ Melhor aerodinâmica</li>
<li>✅ Menor ruído do vento</li>
<li>❌ Pode ser mais quente no verão</li>
</ul>

<h4>Capacete Aberto (Jet)</h4>
<p>Popular entre motociclistas urbanos e scooters, o capacete aberto oferece mais conforto e ventilação, mas menos proteção.</p>

<ul>
<li>✅ Maior ventilação</li>
<li>✅ Melhor campo de visão</li>
<li>❌ Sem proteção no queixo</li>
<li>❌ Menos seguro em quedas</li>
</ul>

<h4>Capacete Escamoteável (Modular)</h4>
<p>Combina características dos dois tipos anteriores, permitindo levantar a parte frontal.</p>

<ul>
<li>✅ Versatilidade (aberto + fechado)</li>
<li>✅ Facilidade para colocar/retirar</li>
<li>❌ Geralmente mais pesado</li>
<li>❌ Custo mais elevado</li>
</ul>

<h3>2. Certificações de Segurança</h3>

<p>Nunca compre um capacete sem certificação! No Brasil, procure por estas homologações:</p>

<ul>
<li><strong>INMETRO</strong> - Certificação obrigatória nacional</li>
<li><strong>DOT</strong> - Padrão americano (Department of Transportation)</li>
<li><strong>ECE 22.05/22.06</strong> - Padrão europeu (o mais rigoroso)</li>
<li><strong>SHARP</strong> - Sistema de classificação britânico (5 estrelas)</li>
</ul>

<div class="alert alert-warning">
<strong>⚠️ Atenção:</strong> Capacetes sem certificação podem parecer bonitos e baratos, mas não oferecem proteção real. Sua vida vale mais!
</div>

<h3>3. Como Medir o Tamanho Correto</h3>

<p>Um capacete apertado causa dores de cabeça; um folgado pode sair em caso de acidente. Siga este passo a passo:</p>

<ol>
<li><strong>Meça sua cabeça:</strong> Use uma fita métrica 2cm acima das sobrancelhas</li>
<li><strong>Consulte a tabela do fabricante:</strong> Cada marca tem medidas próprias</li>
<li><strong>Teste antes de comprar:</strong>
   <ul>
   <li>O capacete deve entrar com certa dificuldade</li>
   <li>Não deve apertar pontos específicos</li>
   <li>Não deve girar na cabeça quando você mexe</li>
   <li>Use por 15-20 minutos para sentir o conforto</li>
   </ul>
</li>
</ol>

<table class="table table-bordered">
<thead>
<tr>
<th>Medida (cm)</th>
<th>Tamanho</th>
</tr>
</thead>
<tbody>
<tr><td>53-54</td><td>XS (PP)</td></tr>
<tr><td>55-56</td><td>S (P)</td></tr>
<tr><td>57-58</td><td>M (M)</td></tr>
<tr><td>59-60</td><td>L (G)</td></tr>
<tr><td>61-62</td><td>XL (GG)</td></tr>
<tr><td>63-64</td><td>XXL (XG)</td></tr>
</tbody>
</table>

<h3>4. Materiais da Calota</h3>

<p>A calota externa é responsável por dissipar o impacto:</p>

<ul>
<li><strong>Policarbonato/Thermoplastic:</strong> Mais barato, boa proteção, pesado</li>
<li><strong>Fibra de Vidro:</strong> Equilíbrio preço/peso/proteção</li>
<li><strong>Fibra de Carbono:</strong> Ultra leve, resistente, caro</li>
<li><strong>Kevlar/Aramida:</strong> Top de linha, usado em competições</li>
</ul>

<h3>5. Viseira e Ventilação</h3>

<h4>Viseira</h4>
<ul>
<li><strong>Anti-embaçante (Pinlock):</strong> Essencial para dias frios/chuva</li>
<li><strong>Fotocromática:</strong> Escurece automaticamente no sol</li>
<li><strong>Viseira interna solar:</strong> Prática para trocar de luminosidade</li>
</ul>

<h4>Ventilação</h4>
<p>Procure por capacetes com múltiplas entradas e saídas de ar. Boa ventilação = conforto em viagens longas.</p>

<h3>6. Faixas de Preço e Recomendações</h3>

<h4>Entrada (R$ 200 - R$ 500)</h4>
<ul>
<li>EBF E08</li>
<li>Pro Tork G8</li>
<li>Helt 990</li>
</ul>

<h4>Intermediário (R$ 500 - R$ 1.500)</h4>
<ul>
<li>X11 Revo</li>
<li>LS2 FF323 Arrow</li>
<li>MT Blade 2</li>
</ul>

<h4>Avançado (R$ 1.500+)</h4>
<ul>
<li>Shark S700</li>
<li>Shoei NXR</li>
<li>Arai Quantum-X</li>
<li>AGV K3 SV</li>
</ul>

<h3>7. Manutenção e Troca</h3>

<p><strong>Troque seu capacete:</strong></p>
<ul>
<li>A cada <strong>5 anos</strong> (degradação natural dos materiais)</li>
<li>Imediatamente após qualquer <strong>queda com impacto</strong></li>
<li>Se houver <strong>rachaduras ou danos visíveis</strong></li>
</ul>

<p><strong>Limpeza:</strong></p>
<ul>
<li>Forro interno: Remova e lave com sabão neutro mensalmente</li>
<li>Calota externa: Pano úmido + detergente suave</li>
<li>Viseira: Produtos específicos ou água + sabão (nunca álcool!)</li>
</ul>

<h3>8. Dicas Extras</h3>

<ol>
<li><strong>Nunca empreste seu capacete:</strong> Ele molda na sua cabeça com o tempo</li>
<li><strong>Guarde em local seco:</strong> Evite umidade e calor excessivo</li>
<li><strong>Use touca ninja:</strong> Ajuda no ajuste e higiene</li>
<li><strong>Teste com seus óculos:</strong> Se usa óculos de grau, verifique se cabem</li>
<li><strong>Prefira cores claras:</strong> Refletem calor e são mais visíveis no trânsito</li>
</ol>

<h3>Conclusão</h3>

<p>Escolher o capacete ideal é um investimento na sua segurança. Não economize na proteção! Um bom capacete pode custar caro, mas a sua vida não tem preço. Visite nossa loja e experimente diversos modelos com a ajuda de nossos especialistas.</p>

<div class="cta-box" style="background: #b73337; color: #fff; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center;">
<h4 style="color: #fff; margin-bottom: 10px;">🛒 Encontre o Capacete Perfeito para Você!</h4>
<p>Temos mais de 200 modelos de capacetes com certificação INMETRO e melhor preço do mercado.</p>
<a href="/capacetes" class="btn btn-light" style="margin-top: 10px;">Ver Capacetes</a>
</div>

<p><em>📅 Publicado em: Dezembro 2025 | ⏱️ Tempo de leitura: 8 minutos</em></p>
ARTIGO

echo "✅ Artigo criado em: var/tmp/artigo_capacete.html"
echo ""
echo "📋 PRÓXIMOS PASSOS:"
echo "   1. Acessar: Admin > Content > Blog > Posts"
echo "   2. Clicar 'Add New Post'"
echo "   3. Preencher:"
echo "      - Title: Guia Completo: Como Escolher o Capacete Ideal"
echo "      - URL Key: guia-completo-capacete-ideal"
echo "      - Author: Equipe Grupo Awamotos"
echo "      - Categories: Segurança, Equipamentos"
echo "      - Tags: capacete, segurança, equipamentos, guia"
echo "      - Content: Copiar de var/tmp/artigo_capacete.html"
echo "      - Meta Title: Guia Completo: Como Escolher o Capacete Ideal | Grupo Awamotos"
echo "      - Meta Description: Aprenda a escolher o capacete ideal para sua moto. Tipos, certificações, tamanhos e melhores marcas. Guia completo 2025."
echo "   4. Status: Enabled"
echo "   5. Save Post"
echo ""
echo "🔗 URL final: https://srv1113343.hstgr.cloud/blog/guia-completo-capacete-ideal"
echo ""
