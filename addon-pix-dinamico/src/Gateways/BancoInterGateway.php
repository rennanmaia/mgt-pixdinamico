<?php
namespace PixDinamico\Gateways;

require_once __DIR__ . '/GatewayInterface.php';
use Exception;

/**
 * Gateway para integração com Banco Inter PIX
 */
class BancoInterGateway implements GatewayInterface {
    
    private $config;
    private $accessToken;
    private $baseUrl;
    
    public function __construct($config = null) {
        // Construtor minimalista para evitar qualquer problema
        $this->config = is_array($config) ? $config : [];
        $this->config = array_merge([
            'sandbox' => true,
            'api_url' => 'https://cdpj.partners.bancointer.com.br',
            'api_url_sandbox' => 'https://cdpj-sandbox.partners.uatinter.co',
            'scope' => 'pix-write pix-read'
        ], $this->config);
        $this->baseUrl = ($this->config['sandbox'] ?? true) ? 
            ($this->config['api_url_sandbox'] ?? 'https://cdpj-sandbox.partners.uatinter.co') : 
            ($this->config['api_url'] ?? 'https://cdpj.partners.bancointer.com.br');
        $this->accessToken = null;
    }
    
    /**
     * Cria um PIX dinâmico no Banco Inter
     */
    public function createPix(array $data): array {
        try {
            $this->authenticate();
            
            // Preparar payload conforme documentação oficial
            $pixData = [
                'chave' => $this->getPixKey(),
                'solicitacaoPagador' => $data['description'],
                'devedor' => [
                    'nome' => $this->sanitizeName($data['payer_name'])
                ],
                'valor' => [
                    'original' => number_format($data['amount'], 2, '.', ''),
                    'modalidadeAlteracao' => 1 // Permitir alteração
                ],
                'calendario' => [
                    'expiracao' => 86400 // 24 horas em segundos
                ]
            ];
            
            // Adicionar CPF ou CNPJ conforme o tipo
            $document = $this->sanitizeDocument($data['payer_document']);
            if (strlen($document) === 11) {
                $pixData['devedor']['cpf'] = $document;
            } else {
                $pixData['devedor']['cnpj'] = $document;
            }
            
            // Usar POST para criar PIX conforme documentação oficial
            $response = $this->makeRequest('/pix/v2/cob', 'POST', $pixData);
            
            // Verificar se a resposta é válida
            if (empty($response) || !is_array($response)) {
                throw new Exception('Resposta vazia ou inválida do servidor');
            }
            
            $resultTxid = $response['txid'] ?? null;
            
            if (!$resultTxid) {
                throw new Exception('txid não retornado pela API');
            }
            
            // Gera QR Code
            $qrResponse = $this->makeRequest("/pix/v2/cob/{$resultTxid}/qrcode", 'GET');
            
            return [
                'transaction_id' => $resultTxid,
                'pix_key' => $response['chave'] ?? $this->getPixKey(),
                'qr_code' => $qrResponse['imagemQrcode'] ?? '',
                'pix_code' => $qrResponse['qrcode'] ?? '',
                'status' => 'pending',
                'expires_at' => date('Y-m-d H:i:s', strtotime('+24 hours'))
            ];
            
        } catch (Exception $e) {
            throw new Exception('Erro ao criar PIX no Banco Inter: ' . $e->getMessage());
        }
    }
    
    /**
     * Consulta status do PIX
     */
    public function getPixStatus(string $transactionId): array {
        try {
            $this->authenticate();
            
            $response = $this->makeRequest("/pix/v2/cob/{$transactionId}", 'GET');
            
            $status = 'pending';
            if (isset($response['status'])) {
                switch ($response['status']) {
                    case 'CONCLUIDA':
                        $status = 'paid';
                        break;
                    case 'REMOVIDA_PELO_USUARIO_RECEBEDOR':
                    case 'REMOVIDA_PELO_PSP':
                        $status = 'cancelled';
                        break;
                    default:
                        $status = 'pending';
                }
            }
            
            return [
                'status' => $status,
                'paid_at' => $status === 'paid' ? ($response['pix'][0]['horario'] ?? null) : null,
                'amount' => $response['valor']['original'] ?? 0,
                'raw_response' => $response
            ];
            
        } catch (Exception $e) {
            throw new Exception('Erro ao consultar PIX no Banco Inter: ' . $e->getMessage());
        }
    }
    
    /**
     * Cancela PIX
     */
    public function cancelPix(string $transactionId): bool {
        try {
            $this->authenticate();
            
            $this->makeRequest("/pix/v2/cob/{$transactionId}", 'DELETE');
            
            return true;
            
        } catch (Exception $e) {
            throw new Exception('Erro ao cancelar PIX no Banco Inter: ' . $e->getMessage());
        }
    }
    
    /**
     * Processa webhook do Banco Inter
     */
    public function processWebhook(array $webhookData): array {
        try {
            // Valida webhook
            if (!$this->validateWebhook($webhookData)) {
                throw new Exception('Webhook inválido');
            }
            
            $txid = $webhookData['txid'] ?? null;
            if (!$txid) {
                throw new Exception('txid não encontrado no webhook');
            }
            
            // Busca dados completos da transação
            $pixData = $this->getPixStatus($txid);
            
            return [
                'transaction_id' => $txid,
                'status' => $pixData['status'],
                'paid_at' => $pixData['paid_at'],
                'amount' => $pixData['amount'],
                'webhook_data' => $webhookData
            ];
            
        } catch (Exception $e) {
            throw new Exception('Erro ao processar webhook: ' . $e->getMessage());
        }
    }
    
    /**
     * Valida configurações
     */
    public function validateConfig(): bool {
        $required = ['client_id', 'client_secret', 'certificate_path', 'private_key_path'];
        
        foreach ($required as $key) {
            if (empty($this->config[$key])) {
                return false;
            }
        }
        
        // Verifica se certificados existem
        if (!file_exists($this->config['certificate_path']) || 
            !file_exists($this->config['private_key_path'])) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Autentica na API do Banco Inter
     */
    private function authenticate() {
        if ($this->accessToken && !$this->isTokenExpired()) {
            return;
        }
        
        try {
            // Validar configurações essenciais
            if (empty($this->config['client_id'])) {
                throw new Exception('Client ID não configurado');
            }
            
            if (empty($this->config['client_secret'])) {
                throw new Exception('Client Secret não configurado');
            }
            
            if (empty($this->baseUrl)) {
                throw new Exception('URL base não configurada');
            }
            
            // Autenticação OAuth2 do Banco Inter conforme exemplos de cURL:
            // enviar client_id, client_secret, scope e grant_type no corpo
            $data = [
                'client_id' => $this->config['client_id'],
                'client_secret' => $this->config['client_secret'],
                'scope' => $this->config['scope'] ?? 'pix-write pix-read',
                'grant_type' => 'client_credentials'
            ];
            
            $response = $this->makeRequest('/oauth/v2/token', 'POST', $data, true);
            
            if (!isset($response['access_token'])) {
                throw new Exception('Token de acesso não recebido');
            }
            
            $this->accessToken = $response['access_token'];
            
            // Cache do token (simplificado)
            file_put_contents(
                ADDON_PIX_DIR . '/temp/inter_token.json',
                json_encode([
                    'token' => $this->accessToken,
                    'expires_at' => time() + ($response['expires_in'] - 60) // 60s de margem
                ])
            );
            
        } catch (Exception $e) {
            throw new Exception('Erro na autenticação: ' . $e->getMessage());
        }
    }
    
    /**
     * Verifica se token expirou
     */
    private function isTokenExpired(): bool {
        $tokenFile = ADDON_PIX_DIR . '/temp/inter_token.json';
        
        if (!file_exists($tokenFile)) {
            return true;
        }
        
        $tokenData = json_decode(file_get_contents($tokenFile), true);
        
        return time() >= $tokenData['expires_at'];
    }
    
    /**
     * Faz requisição para API
     */
    private function makeRequest(string $endpoint, string $method = 'GET', array $data = [], bool $isAuth = false) {
        // Validar baseUrl
        if (empty($this->baseUrl)) {
            throw new Exception('Base URL não configurada');
        }
        
        $url = $this->baseUrl . $endpoint;
        
        // Validar URL construída
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception('URL inválida: ' . $url);
        }
        
        $ch = curl_init();
        
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        if (!$isAuth && $this->accessToken) {
            $headers[] = 'Authorization: Bearer ' . $this->accessToken;
        }
        
        // Adicionar header x-conta-corrente se configurado
        if (!empty($this->config['conta_corrente'])) {
            $headers[] = 'x-conta-corrente: ' . $this->config['conta_corrente'];
        }
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => !$this->config['sandbox'],
            CURLOPT_SSL_VERIFYHOST => !$this->config['sandbox'] ? 2 : 0,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_USERAGENT => 'PIX-Dinamico/1.0',
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4, // Força IPv4 para melhor compatibilidade
            CURLOPT_DNS_CACHE_TIMEOUT => 300, // Cache DNS por 5 minutos
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1 // Força HTTP/1.1 para compatibilidade
        ]);
        
        // Configurações adicionais para resolver problemas de conectividade
        if (defined('CURLOPT_DNS_SERVERS')) {
            curl_setopt($ch, CURLOPT_DNS_SERVERS, '8.8.8.8,8.8.4.4,1.1.1.1'); // DNS alternativos
        }
        
        // Se estiver em sandbox, desabilitar verificações SSL completamente
        if ($this->config['sandbox'] ?? true) {
            curl_setopt_array($ch, [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]);
        }
        
        // Certificados SSL (Produção e Sandbox) se configurados
        if (!empty($this->config['certificate_path']) && !empty($this->config['private_key_path']) &&
            file_exists($this->config['certificate_path']) && file_exists($this->config['private_key_path'])) {
            curl_setopt_array($ch, [
                CURLOPT_SSLCERT => $this->config['certificate_path'],
                CURLOPT_SSLKEY => $this->config['private_key_path']
            ]);
        }
        
        if (!empty($data)) {
            if ($isAuth) {
                // Autenticação: enviar dados como x-www-form-urlencoded, sem logar segredos
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                $headers[0] = 'Content-Type: application/x-www-form-urlencoded';
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        
        // Log da requisição para debug (arquivo dedicado, sem segredos)
        $logFile = ADDON_PIX_DIR . '/logs/inter_http.log';
        $logData = date('Y-m-d H:i:s') . "\n";
        $logData .= "METHOD: {$method}\nURL: {$url}\nHTTP: {$httpCode}\nContent-Type: " . ($contentType ?: 'N/A') . "\n";
        if ($isAuth) {
            // Não logar client_secret
            $safeData = $data;
            if (isset($safeData['client_secret'])) {
                $safeData['client_secret'] = '***';
            }
            $logData .= "AUTH DATA: " . json_encode($safeData) . "\n";
        }
        $logData .= "RESPONSE (primeiros 500 bytes):\n" . substr((string)$response, 0, 500) . "\n--------------------------\n";
        file_put_contents($logFile, $logData, FILE_APPEND);
        
        if ($error) {
            throw new Exception('Erro cURL: ' . $error);
        }
        
        if ($httpCode >= 400) {
            // Tentar extrair mensagem de erro mais amigável do Banco Inter
            $errorMsg = "Erro HTTP {$httpCode}";
            if (!empty($response)) {
                $trimmed = trim($response);
                $json = json_decode($trimmed, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                    $parts = [];
                    if (!empty($json['error'])) {
                        $parts[] = $json['error'];
                    }
                    if (!empty($json['error_description'])) {
                        $parts[] = $json['error_description'];
                    }
                    if ($parts) {
                        $errorMsg .= ': ' . implode(' - ', $parts);
                    } else {
                        $errorMsg .= ': ' . substr($trimmed, 0, 200);
                    }
                } else {
                    $errorMsg .= ': ' . substr($trimmed, 0, 200);
                }
            }
            throw new Exception($errorMsg);
        }
        
        // Verificar se a resposta não está vazia
        if (empty($response) || trim($response) === '') {
            // Sempre retorna JSON de erro para o frontend
            return [
                'success' => false,
                'error' => 'Resposta vazia do servidor',
                'endpoint' => $endpoint,
                'http_code' => $httpCode
            ];
        }
        
        // Verificar se o Content-Type é JSON
        if ($contentType && strpos($contentType, 'application/json') === false) {
            throw new Exception('Resposta não é JSON. Content-Type: ' . $contentType);
        }
        
        $decodedResponse = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Resposta JSON inválida: ' . json_last_error_msg() . '. Response: ' . substr($response, 0, 200));
        }
        
        return $decodedResponse;
    }
    
    /**
     * Obtém chave PIX configurada
     */
    private function getPixKey() {
        // Busca chave PIX configurada no sistema
        $pixKey = $this->config['pix_key'] ?? '';
        
        if (empty($pixKey)) {
            throw new Exception('Chave PIX não configurada no gateway Banco Inter');
        }
        
        return $pixKey;
    }
    
    /**
     * Sanitiza nome para API
     */
    private function sanitizeName(string $name): string {
        return substr(trim($name), 0, 200);
    }
    
    /**
     * Sanitiza documento (CPF/CNPJ)
     */
    private function sanitizeDocument(string $document): string {
        return preg_replace('/[^0-9]/', '', $document);
    }
    
    /**
     * Valida webhook (implementação básica)
     */
    private function validateWebhook(array $data): bool {
        // Implementar validação de assinatura conforme documentação do Banco Inter
        // Por enquanto, validação básica
        return isset($data['txid']);
    }
    
    /**
     * Gera txid único para PIX imediato
     */
    private function generateTxid(): string {
        // Txid deve ter 25-35 caracteres alfanuméricos
        // Formato: timestamp + random para garantir unicidade
        $timestamp = date('YmdHis'); // 14 caracteres
        $random = strtoupper(bin2hex(random_bytes(8))); // 16 caracteres
        
        // Combinar e limitar a 35 caracteres
        $txid = substr($timestamp . $random, 0, 35);
        
        return $txid;
    }
}
?>