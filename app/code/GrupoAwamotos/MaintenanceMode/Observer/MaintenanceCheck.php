<?php
/**
 * GrupoAwamotos_MaintenanceMode - Observer de Verificação
 * Versão Premium Gratuita - Todas as funcionalidades
 */
declare(strict_types=1);

namespace GrupoAwamotos\MaintenanceMode\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class MaintenanceCheck implements ObserverInterface
{
    private const COOKIE_NAME = 'awa_maintenance_access';
    private const CONFIG_PATH = 'grupoawamotos_maintenance/';

    private ScopeConfigInterface $scopeConfig;
    private RemoteAddress $remoteAddress;
    private ResponseInterface $response;
    private RequestInterface $request;
    private CookieManagerInterface $cookieManager;
    private CookieMetadataFactory $cookieMetadataFactory;
    private StoreManagerInterface $storeManager;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        RemoteAddress $remoteAddress,
        ResponseInterface $response,
        RequestInterface $request,
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->remoteAddress = $remoteAddress;
        $this->response = $response;
        $this->request = $request;
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->storeManager = $storeManager;
    }

    public function execute(Observer $observer): void
    {
        // Não executar se não estiver habilitado
        if (!$this->isEnabled()) {
            return;
        }

        // Verificar código secreto na URL (?preview=CODIGO)
        if ($this->checkSecretKey()) {
            $this->setAccessCookie();
            return;
        }

        // Verificar cookie de acesso válido
        if ($this->hasAccessCookie()) {
            return;
        }

        // Verificar se IP está na whitelist
        if ($this->isWhitelisted()) {
            return;
        }

        // Verificar se é rota permitida (admin, newsletter, etc.)
        if ($this->isAllowedRoute()) {
            return;
        }

        // Verificar se é página CMS permitida
        if ($this->isAllowedCmsPage()) {
            return;
        }

        // Mostrar página de manutenção/em breve
        $this->showMaintenancePage();
    }

    private function getConfig(string $path, $default = null)
    {
        $value = $this->scopeConfig->getValue(
            self::CONFIG_PATH . $path,
            ScopeInterface::SCOPE_STORE
        );
        return $value ?? $default;
    }

    private function isEnabled(): bool
    {
        return (bool) $this->getConfig('general/enabled');
    }

    private function checkSecretKey(): bool
    {
        $previewParam = $this->request->getParam('preview');
        if (empty($previewParam)) {
            return false;
        }
        $secretKey = $this->getConfig('general/secret_key');
        return !empty($secretKey) && $previewParam === $secretKey;
    }

    private function setAccessCookie(): void
    {
        $duration = (int) $this->getConfig('general/cookie_duration', 72);
        $metadata = $this->cookieMetadataFactory
            ->createPublicCookieMetadata()
            ->setDuration($duration * 3600)
            ->setPath('/')
            ->setHttpOnly(true);

        $this->cookieManager->setPublicCookie(
            self::COOKIE_NAME,
            hash('sha256', $this->getConfig('general/secret_key') . '_awamotos_access'),
            $metadata
        );
    }

    private function hasAccessCookie(): bool
    {
        $cookie = $this->cookieManager->getCookie(self::COOKIE_NAME);
        if (empty($cookie)) {
            return false;
        }
        $secretKey = $this->getConfig('general/secret_key');
        return $cookie === hash('sha256', $secretKey . '_awamotos_access');
    }

    private function isWhitelisted(): bool
    {
        $clientIp = $this->remoteAddress->getRemoteAddress();
        $whitelist = $this->getConfig('general/whitelist_ips');
        if (empty($whitelist)) {
            return false;
        }
        $whitelistArray = array_map('trim', explode("\n", $whitelist));
        return in_array($clientIp, array_filter($whitelistArray), true);
    }

    private function isAllowedRoute(): bool
    {
        $currentRoute = $this->request->getModuleName();
        $currentAction = $this->request->getFullActionName();
        
        // Rotas de sistema sempre permitidas
        $systemRoutes = ['admin', 'adminhtml', 'maintenance'];
        if (in_array($currentRoute, $systemRoutes)) {
            return true;
        }

        // Permitir controllers deste módulo
        if (strpos($currentAction, 'maintenance_') === 0) {
            return true;
        }

        // Rotas configuradas pelo admin
        $allowedRoutes = $this->getConfig('general/allowed_routes', '');
        if (empty($allowedRoutes)) {
            return false;
        }

        $routesArray = array_map('trim', explode(',', $allowedRoutes));
        return in_array($currentRoute, $routesArray);
    }

    private function isAllowedCmsPage(): bool
    {
        $currentPath = trim($this->request->getPathInfo(), '/');
        $allowedPages = $this->getConfig('general/allowed_cms_pages', '');
        
        if (empty($allowedPages)) {
            return false;
        }

        $pagesArray = array_map('trim', explode(',', $allowedPages));
        return in_array($currentPath, $pagesArray);
    }

    private function showMaintenancePage(): void
    {
        // Detectar modo (manutenção ou em breve)
        $mode = $this->getConfig('general/mode', 'maintenance');
        $isComingSoon = ($mode === 'coming_soon');
        $configPath = $isComingSoon ? 'coming_soon' : 'maintenance';

        // Configurações de conteúdo
        $title = $this->getConfig($configPath . '/title', 'Estamos em Manutenção');
        $message = $this->getConfig($configPath . '/message', '<p>Voltamos em breve!</p>');
        $showCountdown = (bool) $this->getConfig($configPath . '/show_countdown');
        $countdownDate = $this->getConfig($configPath . '/countdown_date');

        // Design
        $bgType = $this->getConfig('design/background_type', 'gradient');
        $bgColor = $this->getConfig('design/background_color', '#1a237e');
        $bgGradient = $this->getConfig('design/background_gradient', '#1a237e,#000428');
        $bgImage = $this->getConfig('design/background_image');
        $bgVideo = $this->getConfig('design/background_video');
        $textColor = $this->getConfig('design/text_color', '#ffffff');
        $customCss = $this->getConfig('design/custom_css', '');
        $logo = $this->getConfig('design/logo');

        // Newsletter
        $showNewsletter = (bool) $this->getConfig('newsletter/enabled');
        $newsletterTitle = $this->getConfig('newsletter/title', 'Seja avisado!');
        $newsletterButton = $this->getConfig('newsletter/button_text', 'Cadastrar');
        $newsletterSuccess = $this->getConfig('newsletter/success_message', 'Obrigado!');

        // Social
        $showSocial = (bool) $this->getConfig('social/enabled');
        $facebook = $this->getConfig('social/facebook');
        $instagram = $this->getConfig('social/instagram');
        $youtube = $this->getConfig('social/youtube');
        $whatsappSocial = $this->getConfig('social/whatsapp');

        // Contato
        $showContact = (bool) $this->getConfig('contact/show_info');
        $phone = $this->getConfig('contact/phone');
        $whatsappContact = $this->getConfig('contact/whatsapp');
        $email = $this->getConfig('contact/email');

        // URLs
        $baseUrl = $this->storeManager->getStore()->getBaseUrl();
        $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        
        // Build CSS de fundo
        $backgroundCss = $this->buildBackgroundCss($bgType, $bgColor, $bgGradient, $bgImage, $mediaUrl);
        
        // URLs de mídia
        $logoUrl = $logo ? $mediaUrl . 'maintenance/' . $logo : '';

        // Build HTML sections
        $logoHtml = $logoUrl ? '<div class="logo"><img src="' . htmlspecialchars($logoUrl) . '" alt="AWA Motos"></div>' : '';
        $countdownHtml = ($showCountdown && $countdownDate) ? '<div id="countdown" class="countdown"></div>' : '';
        $countdownScript = ($showCountdown && $countdownDate) ? $this->getCountdownScript($countdownDate) : '';
        $newsletterHtml = $showNewsletter ? $this->getNewsletterHtml($baseUrl, $newsletterTitle, $newsletterButton, $newsletterSuccess) : '';
        $socialHtml = $showSocial ? $this->getSocialHtml($facebook, $instagram, $youtube, $whatsappSocial) : '';
        $contactHtml = $showContact ? $this->getContactHtml($phone, $whatsappContact, $email) : '';
        $videoHtml = ($bgType === 'video' && $bgVideo) ? $this->getVideoBackground($bgVideo) : '';
        $secretCodeHtml = $this->getSecretCodeFormHtml($baseUrl);

        $icon = $isComingSoon ? '🚀' : '🔧';
        $httpCode = $isComingSoon ? 200 : 503;

        $html = <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>{$title} | AWA Motos</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
            {$backgroundCss}
            color: {$textColor};
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            text-align: center;
            position: relative;
            overflow-x: hidden;
        }
        .video-bg {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            object-fit: cover;
            z-index: -1;
        }
        .overlay {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 0;
        }
        .container { 
            max-width: 700px; 
            width: 100%;
            animation: fadeIn 1s ease-out; 
            position: relative; 
            z-index: 1; 
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .logo { margin-bottom: 30px; }
        .logo img { max-width: 250px; height: auto; }
        .icon { font-size: 80px; margin-bottom: 20px; animation: pulse 2s infinite; }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        .content h1 { font-size: 2.5rem; font-weight: 700; margin-bottom: 20px; line-height: 1.2; }
        .content p { font-size: 1.1rem; line-height: 1.8; margin-bottom: 15px; opacity: 0.9; }
        a { color: #4fc3f7; text-decoration: none; font-weight: 600; transition: color 0.3s; }
        a:hover { color: #81d4fa; text-decoration: underline; }
        
        /* Countdown */
        .countdown {
            display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;
            margin: 30px 0; padding: 25px;
            background: rgba(255,255,255,0.1); border-radius: 16px;
            backdrop-filter: blur(10px);
        }
        .countdown-item { display: flex; flex-direction: column; align-items: center; min-width: 80px; }
        .countdown-value { font-size: 3rem; font-weight: 700; line-height: 1; }
        .countdown-label { font-size: 0.75rem; text-transform: uppercase; opacity: 0.7; margin-top: 5px; }
        
        /* Newsletter */
        .newsletter { 
            margin: 30px 0; padding: 25px; 
            background: rgba(255,255,255,0.1); 
            border-radius: 16px; 
            backdrop-filter: blur(10px); 
        }
        .newsletter h3 { margin-bottom: 15px; font-size: 1.2rem; }
        .newsletter-form { display: flex; gap: 10px; flex-wrap: wrap; justify-content: center; }
        .newsletter-form input[type="email"] {
            flex: 1; min-width: 250px; padding: 15px 20px; 
            border: none; border-radius: 50px;
            font-size: 1rem; outline: none;
            background: #fff; color: #333;
        }
        .newsletter-form button {
            padding: 15px 30px; 
            background: #4fc3f7; 
            color: #000; 
            border: none;
            border-radius: 50px; 
            font-size: 1rem; 
            font-weight: 600; 
            cursor: pointer;
            transition: all 0.3s;
        }
        .newsletter-form button:hover { background: #81d4fa; transform: scale(1.05); }
        .newsletter-message { 
            margin-top: 15px; 
            padding: 10px; 
            border-radius: 8px; 
            display: none;
        }
        .newsletter-message.success { background: rgba(129,199,132,0.3); color: #c8e6c9; display: block; }
        .newsletter-message.error { background: rgba(239,83,80,0.3); color: #ffcdd2; display: block; }
        
        /* Secret Code Form */
        .secret-access {
            margin-top: 30px;
            padding: 20px;
            background: rgba(255,255,255,0.05);
            border-radius: 12px;
            border: 1px dashed rgba(255,255,255,0.2);
        }
        .secret-access summary {
            cursor: pointer;
            font-size: 0.9rem;
            opacity: 0.7;
            list-style: none;
        }
        .secret-access summary::-webkit-details-marker { display: none; }
        .secret-access[open] summary { margin-bottom: 15px; }
        .secret-form { display: flex; gap: 10px; flex-wrap: wrap; justify-content: center; }
        .secret-form input[type="text"] {
            flex: 1;
            min-width: 200px;
            padding: 12px 18px;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 50px;
            background: rgba(255,255,255,0.1);
            color: #fff;
            font-size: 1rem;
            outline: none;
        }
        .secret-form input::placeholder { color: rgba(255,255,255,0.5); }
        .secret-form button {
            padding: 12px 25px;
            background: rgba(255,255,255,0.2);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .secret-form button:hover { background: rgba(255,255,255,0.3); }
        .secret-message { margin-top: 10px; font-size: 0.9rem; display: none; }
        
        /* Social */
        .social-links { margin-top: 30px; display: flex; justify-content: center; gap: 15px; flex-wrap: wrap; }
        .social-links a {
            display: inline-flex; align-items: center; justify-content: center;
            width: 55px; height: 55px; 
            background: rgba(255,255,255,0.15);
            border-radius: 50%; 
            transition: all 0.3s; 
            font-size: 26px;
            text-decoration: none;
        }
        .social-links a:hover { 
            background: rgba(255,255,255,0.3); 
            transform: translateY(-5px) scale(1.1); 
        }
        
        /* Contact */
        .contact-info {
            margin-top: 40px; 
            padding-top: 30px;
            border-top: 1px solid rgba(255,255,255,0.2);
        }
        .contact-info p { margin: 5px 0; }
        
        /* Loading spinner */
        .spinner {
            display: inline-block;
            width: 16px; height: 16px;
            border: 2px solid transparent;
            border-top-color: currentColor;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-left: 8px;
            vertical-align: middle;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        @media (max-width: 600px) {
            .content h1 { font-size: 1.8rem; }
            .countdown { gap: 10px; padding: 15px; }
            .countdown-value { font-size: 2rem; }
            .countdown-item { min-width: 60px; }
            .newsletter-form input[type="email"] { min-width: 100%; }
            .secret-form input[type="text"] { min-width: 100%; }
        }
        {$customCss}
    </style>
</head>
<body>
    {$videoHtml}
    <div class="container">
        {$logoHtml}
        <div class="icon">{$icon}</div>
        <div class="content">{$message}</div>
        {$countdownHtml}
        {$newsletterHtml}
        {$secretCodeHtml}
        {$socialHtml}
        {$contactHtml}
    </div>
    {$countdownScript}
</body>
</html>
HTML;

        $this->response->setHttpResponseCode($httpCode);
        if (!$isComingSoon) {
            $this->response->setHeader('Retry-After', '3600');
        }
        $this->response->setBody($html);
        $this->response->sendResponse();
        exit;
    }

    private function buildBackgroundCss(string $type, string $color, string $gradient, ?string $image, string $mediaUrl): string
    {
        switch ($type) {
            case 'color':
                return "background: {$color};";
            case 'gradient':
                $colors = explode(',', $gradient);
                $color1 = trim($colors[0] ?? '#1a237e');
                $color2 = trim($colors[1] ?? '#000428');
                return "background: linear-gradient(135deg, {$color1} 0%, {$color2} 100%);";
            case 'image':
                if ($image) {
                    $imageUrl = $mediaUrl . 'maintenance/' . $image;
                    return "background: url('{$imageUrl}') center/cover no-repeat fixed; background-color: #000;";
                }
                return "background: #000;";
            case 'video':
                return "background: #000;";
            default:
                return "background: linear-gradient(135deg, #1a237e 0%, #000428 100%);";
        }
    }

    private function getVideoBackground(string $videoUrl): string
    {
        // Detectar YouTube
        if (strpos($videoUrl, 'youtube') !== false || strpos($videoUrl, 'youtu.be') !== false) {
            preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $videoUrl, $matches);
            $videoId = $matches[1] ?? '';
            if ($videoId) {
                return '<div class="overlay"></div><iframe class="video-bg" src="https://www.youtube.com/embed/' . htmlspecialchars($videoId) . '?autoplay=1&mute=1&loop=1&playlist=' . htmlspecialchars($videoId) . '&controls=0&showinfo=0" frameborder="0" allow="autoplay" allowfullscreen></iframe>';
            }
        }
        // Vídeo MP4 direto
        return '<div class="overlay"></div><video class="video-bg" autoplay muted loop playsinline><source src="' . htmlspecialchars($videoUrl) . '" type="video/mp4"></video>';
    }

    private function getNewsletterHtml(string $baseUrl, string $title, string $button, string $successMsg): string
    {
        $actionUrl = $baseUrl . 'maintenance/newsletter/subscribe';
        return <<<HTML
<div class="newsletter">
    <h3>{$title}</h3>
    <form class="newsletter-form" id="maintenance-newsletter">
        <input type="email" name="email" id="newsletter-email" placeholder="Seu melhor e-mail" required>
        <button type="submit" id="newsletter-btn">{$button}</button>
    </form>
    <div class="newsletter-message" id="newsletter-msg"></div>
</div>
<script>
document.getElementById('maintenance-newsletter').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('newsletter-btn');
    const msg = document.getElementById('newsletter-msg');
    const email = document.getElementById('newsletter-email').value;
    
    btn.disabled = true;
    btn.innerHTML += '<span class="spinner"></span>';
    msg.className = 'newsletter-message';
    msg.style.display = 'none';
    
    fetch('{$actionUrl}', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'email=' + encodeURIComponent(email)
    })
    .then(r => r.json())
    .then(data => {
        msg.textContent = data.message || (data.success ? '✅ {$successMsg}' : '❌ Erro ao cadastrar.');
        msg.className = 'newsletter-message ' + (data.success ? 'success' : 'error');
        msg.style.display = 'block';
        if (data.success) document.getElementById('newsletter-email').value = '';
    })
    .catch(() => {
        msg.textContent = '❌ Erro de conexão. Tente novamente.';
        msg.className = 'newsletter-message error';
        msg.style.display = 'block';
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = '{$button}';
    });
});
</script>
HTML;
    }

    private function getSecretCodeFormHtml(string $baseUrl): string
    {
        $actionUrl = $baseUrl . 'maintenance/access/validate';
        return <<<HTML
<details class="secret-access">
    <summary>🔐 Possui código de acesso?</summary>
    <form class="secret-form" id="secret-access-form">
        <input type="text" name="code" id="access-code" placeholder="Digite o código" required>
        <button type="submit" id="access-btn">Entrar</button>
    </form>
    <div class="secret-message" id="access-msg"></div>
</details>
<script>
document.getElementById('secret-access-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('access-btn');
    const msg = document.getElementById('access-msg');
    const code = document.getElementById('access-code').value;
    
    btn.disabled = true;
    btn.innerHTML = 'Verificando<span class="spinner"></span>';
    msg.style.display = 'none';
    
    fetch('{$actionUrl}', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'code=' + encodeURIComponent(code)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success && data.redirect) {
            msg.textContent = '✅ ' + data.message;
            msg.style.color = '#81c784';
            msg.style.display = 'block';
            setTimeout(() => window.location.href = data.redirect, 1000);
        } else {
            msg.textContent = '❌ ' + (data.message || 'Código inválido');
            msg.style.color = '#ef5350';
            msg.style.display = 'block';
            btn.disabled = false;
            btn.textContent = 'Entrar';
        }
    })
    .catch(() => {
        msg.textContent = '❌ Erro de conexão';
        msg.style.color = '#ef5350';
        msg.style.display = 'block';
        btn.disabled = false;
        btn.textContent = 'Entrar';
    });
});
</script>
HTML;
    }

    private function getSocialHtml(?string $facebook, ?string $instagram, ?string $youtube, ?string $whatsapp): string
    {
        $links = '';
        if ($whatsapp) {
            $links .= '<a href="https://wa.me/' . htmlspecialchars($whatsapp) . '" title="WhatsApp" target="_blank" rel="noopener">📱</a>';
        }
        if ($facebook) {
            $links .= '<a href="' . htmlspecialchars($facebook) . '" title="Facebook" target="_blank" rel="noopener">📘</a>';
        }
        if ($instagram) {
            $links .= '<a href="' . htmlspecialchars($instagram) . '" title="Instagram" target="_blank" rel="noopener">📷</a>';
        }
        if ($youtube) {
            $links .= '<a href="' . htmlspecialchars($youtube) . '" title="YouTube" target="_blank" rel="noopener">🎬</a>';
        }
        return $links ? '<div class="social-links">' . $links . '</div>' : '';
    }

    private function getContactHtml(?string $phone, ?string $whatsapp, ?string $email): string
    {
        $html = '<div class="contact-info"><p><strong>AWA Motos</strong> - Peças e Acessórios para Motos</p>';
        $contacts = [];
        if ($phone) {
            $contacts[] = '📞 ' . htmlspecialchars($phone);
        }
        if ($whatsapp) {
            $contacts[] = '💬 ' . htmlspecialchars($whatsapp);
        }
        if ($email) {
            $contacts[] = '✉️ <a href="mailto:' . htmlspecialchars($email) . '">' . htmlspecialchars($email) . '</a>';
        }
        if ($contacts) {
            $html .= '<p>' . implode(' | ', $contacts) . '</p>';
        }
        return $html . '</div>';
    }

    private function getCountdownScript(string $targetDate): string
    {
        return <<<SCRIPT
<script>
(function() {
    const targetDate = new Date('{$targetDate}'.replace(' ', 'T')).getTime();
    const countdown = document.getElementById('countdown');
    if (!countdown) return;
    
    function updateCountdown() {
        const now = new Date().getTime();
        const distance = targetDate - now;
        if (distance < 0) { 
            countdown.innerHTML = '<p style="font-size:1.5rem;">🎉 Estamos quase prontos!</p>'; 
            return; 
        }
        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        countdown.innerHTML = 
            '<div class="countdown-item"><span class="countdown-value">' + days + '</span><span class="countdown-label">Dias</span></div>' +
            '<div class="countdown-item"><span class="countdown-value">' + String(hours).padStart(2,'0') + '</span><span class="countdown-label">Horas</span></div>' +
            '<div class="countdown-item"><span class="countdown-value">' + String(minutes).padStart(2,'0') + '</span><span class="countdown-label">Min</span></div>' +
            '<div class="countdown-item"><span class="countdown-value">' + String(seconds).padStart(2,'0') + '</span><span class="countdown-label">Seg</span></div>';
    }
    updateCountdown();
    setInterval(updateCountdown, 1000);
})();
</script>
SCRIPT;
    }
}
