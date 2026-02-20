<?php
/**
 * Webhook do Banco Inter para receber confirmações de pagamento PIX
 */

require_once '../config.php';

use PixDinamico\PixManager;
use PixDinamico\Gateways\BancoInterGateway;

// Log da requisição recebida
$inputData = file_get_contents('php://input');
$headers = getallheaders();

try {
    $pixManager = new PixManager();
    
    // Log da requisição
    $pixManager->log('info', 'Webhook recebido do Banco Inter', [
        'headers' => $headers,
        'body' => $inputData,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    
    // Verificar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido');
    }
    
    // Decodificar dados
    $webhookData = json_decode($inputData, true);
    if (!$webhookData) {
        throw new Exception('Dados inválidos');
    }
    
    // Verificar se é notificação de PIX
    if (!isset($webhookData['txid'])) {
        throw new Exception('txid não encontrado');
    }
    
    $txid = $webhookData['txid'];
    
    // Buscar transação no banco
    $pixTransaction = $pixManager->getPixByTransactionId($txid);
    if (!$pixTransaction) {
        throw new Exception("Transação não encontrada: {$txid}");
    }
    
    // Processar webhook usando as mesmas configurações do gateway (arquivo + banco)
    $gatewayConfig = getGatewayConfig('banco_inter') ?: [];
    try {
        $dbConfig = $pixManager->getConfig('banco_inter');
        if (is_array($dbConfig) && !empty($dbConfig)) {
            foreach ($dbConfig as $key => $value) {
                if ($value !== '' && $value !== null) {
                    $gatewayConfig[$key] = $value;
                }
            }
        }
    } catch (Exception $e) {
        // Em caso de erro ao carregar config do banco, continuar apenas com config.php
    }

    $gateway = new BancoInterGateway($gatewayConfig);
    $processedData = $gateway->processWebhook($webhookData);
    
    // Atualizar status da transação
    $success = $pixManager->updatePixStatus(
        $pixTransaction['uuid'],
        $processedData['status'],
        $processedData['paid_at'],
        $processedData['webhook_data']
    );
    
    if ($success) {
        $pixManager->log('info', 'Webhook processado com sucesso', [
            'uuid' => $pixTransaction['uuid'],
            'txid' => $txid,
            'status' => $processedData['status']
        ]);
        
        // Se pagamento confirmado, realizar ações adicionais
        if ($processedData['status'] === 'paid') {
            $pixManager->log('info', 'Pagamento PIX confirmado via webhook', [
                'uuid' => $pixTransaction['uuid'],
                'txid' => $txid,
                'valor' => $processedData['amount'],
                'cliente' => $pixTransaction['cliente_nome']
            ]);
        }
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Webhook processado com sucesso',
            'uuid' => $pixTransaction['uuid'],
            'status' => $processedData['status']
        ]);
    } else {
        throw new Exception('Erro ao atualizar status da transação');
    }
    
} catch (Exception $e) {
    if (isset($pixManager)) {
        $pixManager->log('error', 'Erro no webhook: ' . $e->getMessage(), [
            'input_data' => $inputData,
            'error_trace' => $e->getTraceAsString()
        ]);
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>