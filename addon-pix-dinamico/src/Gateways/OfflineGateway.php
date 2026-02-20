<?php
/**
 * Gateway PIX Modo Offline - Para desenvolvimento sem conectividade
 * Use quando o servidor não conseguir acessar APIs externas
 */
namespace PixDinamico\Gateways;

use Exception;

/**
 * Gateway offline para desenvolvimento e testes
 */
class OfflineGateway implements GatewayInterface {
    
    private $config;
    
    public function __construct() {
        $this->config = getGatewayConfig('banco_inter');
    }
    
    /**
     * Simula criação de PIX dinâmico (modo offline)
     */
    public function createPix(array $data): array {
        // Simular delay de API
        usleep(500000); // 0.5 segundos
        
        // Gerar dados simulados realistas
        $txid = 'OFFLINE' . strtoupper(uniqid());
        $qrCode = $this->generateMockQRCode($data);
        $pixCode = $this->generateMockPixCode($data);
        
        // Log da operação simulada
        $this->logOperation('CREATE_PIX', [
            'txid' => $txid,
            'amount' => $data['amount'],
            'payer' => $data['payer_name'],
            'status' => 'SIMULADO'
        ]);
        
        return [
            'transaction_id' => $txid,
            'pix_key' => $this->getPixKey(),
            'qr_code' => $qrCode,
            'pix_code' => $pixCode,
            'status' => 'pending',
            'expires_at' => date('Y-m-d H:i:s', strtotime('+24 hours')),
            'offline_mode' => true, // Identificar como simulação
            'message' => 'PIX criado em modo OFFLINE para desenvolvimento'
        ];
    }
    
    /**
     * Simula consulta de status (modo offline)
     */
    public function getPixStatus(string $transactionId): array {
        usleep(200000); // 0.2 segundos
        
        // Simular diferentes status baseado no ID
        $status = 'pending';
        if (strpos($transactionId, 'PAID') !== false) {
            $status = 'paid';
        } elseif (strpos($transactionId, 'EXPIRED') !== false) {
            $status = 'expired';
        }
        
        $this->logOperation('GET_STATUS', [
            'txid' => $transactionId,
            'status' => $status
        ]);
        
        return [
            'transaction_id' => $transactionId,
            'status' => $status,
            'paid_at' => $status === 'paid' ? date('Y-m-d H:i:s') : null,
            'offline_mode' => true
        ];
    }
    
    /**
     * Simula cancelamento (modo offline)
     */
    public function cancelPix(string $transactionId): bool {
        usleep(300000); // 0.3 segundos
        
        $this->logOperation('CANCEL_PIX', [
            'txid' => $transactionId,
            'status' => 'cancelled'
        ]);
        
        return true;
    }
    
    /**
     * Simula webhook (modo offline)
     */
    public function processWebhook(array $webhookData): array {
        $txid = $webhookData['txid'] ?? 'UNKNOWN';
        
        $this->logOperation('WEBHOOK', [
            'txid' => $txid,
            'data' => $webhookData
        ]);
        
        return [
            'transaction_id' => $txid,
            'status' => 'paid',
            'amount' => $webhookData['valor'] ?? 0,
            'paid_at' => date('Y-m-d H:i:s'),
            'offline_mode' => true
        ];
    }
    
    /**
     * Verifica se está configurado (sempre true para offline)
     */
    public function isConfigured(): bool {
        return true;
    }
    
    /**
     * Gera QR Code simulado
     */
    private function generateMockQRCode(array $data): string {
        $pixData = [
            'amount' => number_format($data['amount'], 2, '.', ''),
            'description' => $data['description'],
            'payer' => $data['payer_name']
        ];
        
        // Gerar string base64 simulada para QR Code
        $qrString = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
        
        return $qrString;
    }
    
    /**
     * Gera código PIX simulado
     */
    private function generateMockPixCode(array $data): string {
        // Formato simplificado de PIX Code
        return '00020126580014BR.GOV.BCB.PIX0136' . uniqid() . '5204000053039865802BR5925' . 
               substr($data['payer_name'], 0, 25) . '6009SAO PAULO62070503***6304';
    }
    
    /**
     * Obtém chave PIX configurada ou simulada
     */
    private function getPixKey(): string {
        return $this->config['pix_key'] ?? 'offline@exemplo.com';
    }
    
    /**
     * Log de operações offline
     */
    private function logOperation(string $operation, array $data): void {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'operation' => $operation,
            'data' => $data,
            'mode' => 'OFFLINE'
        ];
        
        $logFile = ADDON_PIX_DIR . '/logs/offline.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
    }
}
?>