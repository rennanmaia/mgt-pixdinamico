<?php
/**
 * Addon PIX Dinâmico para MK-AUTH
 * Configurações principais do sistema
 * 
 * @version 1.0.0
 * @author Sistema PIX Dinâmico
 */

// Configurações gerais do addon
define('ADDON_PIX_VERSION', '1.0.0');
define('ADDON_PIX_NAME', 'PIX Dinâmico');
define('ADDON_PIX_DIR', __DIR__);
define('ADDON_PIX_URL', '/mgt-pixdinamico');

// Configurações de banco de dados
$db_host = '127.0.0.1';
$db_name = 'mkradius';
$db_user = 'root';
$db_pass = 'vertrigo';

// Função para obter URL base dinamicamente
function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = dirname($_SERVER['REQUEST_URI'] ?? '');
    
    // Remove o diretório do addon para obter a URL base
    $addonPath = '/addon-pix-dinamico';
    if (strpos($path, $addonPath) !== false) {
        $path = str_replace($addonPath, '', $path);
    }
    
    return rtrim($protocol . $host . $path, '/');
}

// Função para obter URL completa do addon
function getAddonUrl() {
    return getBaseUrl() . ADDON_PIX_URL;
}

// Configurações específicas do PIX
$pixConfig = [
    'enabled' => true,
    'default_gateway' => 'banco_inter',
    'qr_code_size' => 256,
    'expiration_minutes' => 1440, // 24 horas
    'public_url_base' => getAddonUrl() . '/public/',
    'webhook_secret' => 'sua_chave_secreta_webhook_aqui',
    'log_enabled' => true,
    'log_file' => ADDON_PIX_DIR . '/logs/pix.log',
    'auto_baixa_lancamento' => true, // Dar baixa automática no lançamento
    'auto_registro_caixa' => true,   // Registrar automaticamente no caixa
];

// Configurações dos gateways de pagamento
$gatewayConfig = [
    'banco_inter' => [
        'name' => 'Banco Inter',
        'enabled' => true,
        'sandbox' => true, // true para ambiente de teste
        'api_url' => 'https://cdpj.partners.bancointer.com.br',
        'api_url_sandbox' => 'https://cdpj-sandbox.partners.uatinter.co',
        'client_id' => 'efc7dd92-0e4c-4efc-b7bc-9ce38d49ad12', // Configurar nas configurações do sistema
        'client_secret' => '334e2e56-fd9d-41b3-8071-def4d5cd532c', // Configurar nas configurações do sistema
        'pix_key' => 'cloudicombr@gmail.com', // Chave PIX configurável
        'certificate_path' => ADDON_PIX_DIR . '/certificates/inter_cert.crt',
        'private_key_path' => ADDON_PIX_DIR . '/certificates/inter_key.key',
        'scope' => 'pix-write pix-read',
        'webhook_url' => getAddonUrl() . '/webhook/banco_inter.php'
    ],
    // Placeholder para futuras integrações
    'picpay' => [
        'name' => 'PicPay',
        'enabled' => false,
        'webhook_url' => getAddonUrl() . '/webhook/picpay.php'
    ],
    'pagseguro' => [
        'name' => 'PagSeguro',
        'enabled' => false,
        'webhook_url' => getAddonUrl() . '/webhook/pagseguro.php'
    ],
    'mercado_pago' => [
        'name' => 'Mercado Pago',
        'enabled' => false,
        'webhook_url' => getAddonUrl() . '/webhook/mercado_pago.php'
    ]
];

// Função para obter configuração
function getPixConfig($key = null) {
    global $pixConfig;
    return $key ? ($pixConfig[$key] ?? null) : $pixConfig;
}

// Função para obter configuração de gateway
function getGatewayConfig($gateway = null, $key = null) {
    global $gatewayConfig;
    
    if (!$gateway) {
        return $gatewayConfig;
    }
    
    if (!isset($gatewayConfig[$gateway])) {
        return null;
    }
    
    return $key ? ($gatewayConfig[$gateway][$key] ?? null) : $gatewayConfig[$gateway];
}

// Função para verificar se o addon está habilitado
function isPixAddonEnabled() {
    return getPixConfig('enabled') === true;
}

// Autoloader para classes do addon
spl_autoload_register(function ($className) {
    $prefix = 'PixDinamico\\';
    $baseDir = ADDON_PIX_DIR . '/src/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $className, $len) !== 0) {
        return;
    }
    
    $relativeClass = substr($className, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Inicialização de diretórios necessários
$requiredDirs = [
    ADDON_PIX_DIR . '/logs',
    ADDON_PIX_DIR . '/certificates',
    ADDON_PIX_DIR . '/temp'
];

foreach ($requiredDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Configuração de timezone
date_default_timezone_set('America/Sao_Paulo');
?>