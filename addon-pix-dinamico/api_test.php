<?php
/**
 * Teste de API - Endpoint simples para debug
 * Testa se o problema é frontend ou backend
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Se for OPTIONS (preflight), retornar OK
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'test';

try {
    require_once 'config.php';
    
    switch ($action) {
        case 'test':
            echo json_encode([
                'status' => 'success',
                'message' => 'API funcionando',
                'timestamp' => date('c'),
                'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
            ]);
            break;
            
        case 'config':
            $gatewayConfig = getGatewayConfig('banco_inter');
            echo json_encode([
                'status' => 'success',
                'config' => [
                    'sandbox_url' => $gatewayConfig['api_url_sandbox'],
                    'has_client_id' => !empty($gatewayConfig['client_id']),
                    'has_client_secret' => !empty($gatewayConfig['client_secret']),
                    'has_pix_key' => !empty($gatewayConfig['pix_key'])
                ]
            ]);
            break;
            
        case 'connectivity':
            $testUrl = 'https://cdpj-sandbox.partners.uatinter.co';
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $testUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_NOBODY => true,
                CURLOPT_SSL_VERIFYPEER => false
            ]);
            
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            echo json_encode([
                'status' => $error ? 'error' : 'success',
                'url' => $testUrl,
                'http_code' => $httpCode,
                'error' => $error ?: null,
                'reachable' => !$error
            ]);
            break;
            
        case 'gateway_test':
            require_once 'src/Gateways/GatewayInterface.php';
            require_once 'src/Gateways/BancoInterGateway.php';
            
            $gateway = new PixDinamico\Gateways\BancoInterGateway();
            
            // Usar reflexão para verificar configurações
            $reflection = new ReflectionClass($gateway);
            $baseUrlProp = $reflection->getProperty('baseUrl');
            $baseUrlProp->setAccessible(true);
            $baseUrl = $baseUrlProp->getValue($gateway);
            
            $configProp = $reflection->getProperty('config');
            $configProp->setAccessible(true);
            $config = $configProp->getValue($gateway);
            
            echo json_encode([
                'status' => 'success',
                'gateway' => [
                    'base_url' => $baseUrl,
                    'config_loaded' => is_array($config),
                    'sandbox' => $config['sandbox'] ?? null
                ]
            ]);
            break;
            
        default:
            throw new Exception('Ação inválida');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'timestamp' => date('c')
    ]);
}
?>