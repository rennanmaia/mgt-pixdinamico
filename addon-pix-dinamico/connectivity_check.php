<?php
/**
 * Endpoint de Conectividade - PIX Dinâmico
 * Usado pela interface para detectar problemas de rede
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$result = [
    'timestamp' => date('c'),
    'connectivity' => false,
    'tests' => []
];

// Teste 1: DNS Resolution
$hosts = [
    'cdpj-sandbox.partners.uatinter.co', // URL correta da sandbox
    'google.com',
    '8.8.8.8'
];

foreach ($hosts as $host) {
    $ip = gethostbyname($host);
    $resolved = ($ip !== $host || filter_var($host, FILTER_VALIDATE_IP));
    
    $result['tests'][] = [
        'type' => 'dns',
        'host' => $host,
        'resolved' => $resolved,
        'ip' => $resolved ? $ip : null
    ];
    
    if ($resolved) {
        $result['connectivity'] = true;
    }
}

// Teste 2: HTTP Connectivity (rápido)
if ($result['connectivity']) {
    $testUrls = [
        'https://cdpj-sandbox.partners.uatinter.co', // URL correta da sandbox
        'https://google.com'
    ];
    
    foreach ($testUrls as $url) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 3,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_NOBODY => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        $result['tests'][] = [
            'type' => 'http',
            'url' => $url,
            'success' => empty($error),
            'http_code' => $httpCode,
            'error' => $error ?: null
        ];
    }
}

// Teste 3: Gateway Compatibility
try {
    require_once 'config.php';
    require_once 'src/Gateways/GatewayInterface.php';
    require_once 'src/Gateways/BancoInterGateway.php';
    
    $gateway = new PixDinamico\Gateways\BancoInterGateway();
    $result['gateway_loaded'] = true;
    
    // Verificar se baseUrl está configurada
    $reflection = new ReflectionClass($gateway);
    $baseUrlProp = $reflection->getProperty('baseUrl');
    $baseUrlProp->setAccessible(true);
    $baseUrl = $baseUrlProp->getValue($gateway);
    
    $result['gateway_url'] = $baseUrl;
    $result['gateway_configured'] = !empty($baseUrl);
    
} catch (Exception $e) {
    $result['gateway_loaded'] = false;
    $result['gateway_error'] = $e->getMessage();
}

// Status final
$result['offline_mode_recommended'] = !$result['connectivity'] || !($result['gateway_configured'] ?? false);
$result['status'] = $result['offline_mode_recommended'] ? 'offline' : 'online';

echo json_encode($result, JSON_PRETTY_PRINT);
?>