
<?php
// DEBUG: Log para saber se o script está sendo executado
$debug_log = __DIR__ . '/debug_generate_pix.log';
file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Script generate_pix.php iniciado\n", FILE_APPEND);

// Tratamento global para erros fatais e warnings
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => "Erro PHP [$errno]: $errstr em $errfile:$errline"
    ]);
    error_log("Erro PHP [$errno]: $errstr em $errfile:$errline");
    exit;
});

set_exception_handler(function($exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Exceção não capturada: ' . $exception->getMessage(),
        'trace' => $exception->getTraceAsString()
    ]);
    error_log('Exceção não capturada: ' . $exception->getMessage() . "\n" . $exception->getTraceAsString());
    exit;
});

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
$debug_log = __DIR__ . '/debug_generate_pix.log';
file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Script generate_pix.php iniciado\n", FILE_APPEND);

require_once '../../config.php';

// DEBUG: Log após require_once config.php
file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Passou do require_once config.php\n", FILE_APPEND);

use PixDinamico\PixManager;

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Dados inválidos');
    }
    
    $lancId = $input['lanc_id'] ?? 0;
    $gateway = $input['gateway'] ?? 'banco_inter';
    
    if (!$lancId) {
        throw new Exception('ID do lançamento é obrigatório');
    }
    
    // Verificar se addon está habilitado
    if (!isPixAddonEnabled()) {
        throw new Exception('Addon PIX não está habilitado');
    }
    
    // Verificar se gateway está configurado
    $gatewayConfig = getGatewayConfig($gateway);
    if (!$gatewayConfig || !$gatewayConfig['enabled']) {
        throw new Exception('Gateway não está habilitado: ' . $gateway);
    }
    
    $pixManager = new PixManager();
    // DEBUG: Log após criar PixManager
    file_put_contents($debug_log, date('Y-m-d H:i:s') . " - PixManager instanciado\n", FILE_APPEND);
    
    // Gerar PIX
    // DEBUG: Log antes de gerar PIX
    file_put_contents($debug_log, date('Y-m-d H:i:s') . " - Antes de chamar generatePix(lancId=$lancId, gateway=$gateway)\n", FILE_APPEND);
    $pixData = $pixManager->generatePix($lancId, $gateway);
    // DEBUG: Log após gerar PIX
    file_put_contents($debug_log, date('Y-m-d H:i:s') . " - generatePix executado com sucesso\n", FILE_APPEND);
    
    echo json_encode([
        'success' => true,
        'pix_data' => $pixData,
        'message' => 'PIX gerado com sucesso'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    
    // Log do erro
    if (function_exists('error_log')) {
        error_log('Erro ao gerar PIX: ' . $e->getMessage());
    }
}
?>