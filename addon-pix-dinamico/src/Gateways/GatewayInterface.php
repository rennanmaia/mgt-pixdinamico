<?php
namespace PixDinamico\Gateways;

/**
 * Interface base para gateways de pagamento PIX
 */
interface GatewayInterface {
    
    /**
     * Cria um PIX dinâmico
     * 
     * @param array $data Dados do PIX (amount, description, payer_name, etc.)
     * @return array Retorna dados do PIX criado (transaction_id, qr_code, pix_code, etc.)
     */
    public function createPix(array $data): array;
    
    /**
     * Consulta status de um PIX
     * 
     * @param string $transactionId ID da transação
     * @return array Status e dados da transação
     */
    public function getPixStatus(string $transactionId): array;
    
    /**
     * Cancela um PIX
     * 
     * @param string $transactionId ID da transação
     * @return bool Success/failure
     */
    public function cancelPix(string $transactionId): bool;
    
    /**
     * Processa webhook do gateway
     * 
     * @param array $webhookData Dados recebidos do webhook
     * @return array Dados processados
     */
    public function processWebhook(array $webhookData): array;
    
    /**
     * Valida configurações do gateway
     * 
     * @return bool True se configurações são válidas
     */
    public function validateConfig(): bool;
}
?>