<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config.php';

use PixDinamico\PixManager;

try {
    $uuid = $_GET['uuid'] ?? '';
    
    if (!$uuid) {
        throw new Exception('UUID não fornecido');
    }
    
    $pixManager = new PixManager();
    $pixData = $pixManager->getPixByUuid($uuid);
    
    if (!$pixData) {
        throw new Exception('PIX não encontrado');
    }
    
    // Verificar se precisa consultar status no gateway
    if ($pixData['status'] === 'pending' && strtotime($pixData['expires_at']) > time()) {
        try {
            $gateway = $pixManager->getGatewayInstance($pixData['gateway']);
            $statusData = $gateway->getPixStatus($pixData['transaction_id']);
            
            // Atualizar status se mudou
            if ($statusData['status'] !== $pixData['status']) {
                $pixManager->updatePixStatus(
                    $uuid, 
                    $statusData['status'],
                    $statusData['paid_at'],
                    $statusData['raw_response'] ?? null
                );
                $pixData['status'] = $statusData['status'];
                $pixData['paid_at'] = $statusData['paid_at'];
            }
        } catch (Exception $e) {
            // Log erro mas não falha a requisição
            $pixManager->log('warning', 'Erro ao consultar status: ' . $e->getMessage(), [
                'uuid' => $uuid
            ]);
        }
    }
    
    echo json_encode([
        'success' => true,
        'status' => $pixData['status'],
        'paid_at' => $pixData['paid_at'],
        'expires_at' => $pixData['expires_at']
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>