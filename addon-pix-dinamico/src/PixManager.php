<?php
namespace PixDinamico;

use PDO;
use Exception;

/**
 * Classe principal para gerenciamento de PIX dinâmico
 */
class PixManager {
    
    private $db;
    private $gateway;
    
    public function __construct($database = null, $createTables = true) {
        $this->db = $database ?: $this->connectDatabase();
        
        if ($createTables) {
            $this->initializeTables();
        }
    }
    
    /**
     * Conecta ao banco de dados do MK-AUTH
     */
    private function connectDatabase() {
        try {
            // Configurações herdadas do MK-AUTH
            global $db_host, $db_name, $db_user, $db_pass;
            
            $dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
            $pdo = new PDO($dsn, $db_user, $db_pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            return $pdo;
        } catch (Exception $e) {
            throw new Exception("Erro na conexão com banco: " . $e->getMessage());
        }
    }
    
    /**
     * Inicializa as tabelas necessárias para o addon
     */
    private function initializeTables() {
        // Criar tabelas uma por vez, sem foreign keys primeiro
        $this->createConfigTable();
        $this->createTransactionsTable();
        $this->createLogsTable();
    }
    
    /**
     * Cria tabela de configurações
     */
    private function createConfigTable() {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS `addon_pix_config` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `gateway` varchar(50) NOT NULL,
                `config_key` varchar(100) NOT NULL,
                `config_value` text,
                `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `gateway_key` (`gateway`, `config_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1";
            
            $this->db->exec($sql);
            
        } catch (Exception $e) {
            error_log("Erro ao criar tabela addon_pix_config: " . $e->getMessage());
            throw new Exception("Erro ao criar tabela de configurações: " . $e->getMessage());
        }
    }
    
    /**
     * Cria tabela de transações PIX
     */
    private function createTransactionsTable() {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS `addon_pix_transactions` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `uuid` varchar(36) NOT NULL,
                `lanc_id` int(11) NOT NULL,
                `cliente_id` int(11) NOT NULL,
                `gateway` varchar(50) NOT NULL,
                `transaction_id` varchar(100),
                `pix_key` varchar(100),
                `qr_code` longtext,
                `pix_code` longtext,
                `amount` decimal(10,2) NOT NULL,
                `status` enum('pending','paid','expired','cancelled') DEFAULT 'pending',
                `expires_at` datetime NOT NULL,
                `paid_at` datetime NULL,
                `public_url` varchar(500),
                `webhook_data` longtext,
                `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uuid` (`uuid`),
                KEY `lanc_id` (`lanc_id`),
                KEY `cliente_id` (`cliente_id`),
                KEY `status` (`status`),
                KEY `expires_at` (`expires_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1";
            
            $this->db->exec($sql);
            
        } catch (Exception $e) {
            error_log("Erro ao criar tabela addon_pix_transactions: " . $e->getMessage());
            throw new Exception("Erro ao criar tabela de transações: " . $e->getMessage());
        }
    }
    
    /**
     * Cria tabela de logs
     */
    private function createLogsTable() {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS `addon_pix_logs` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `transaction_uuid` varchar(36),
                `level` enum('info','warning','error','debug') DEFAULT 'info',
                `message` text NOT NULL,
                `context` longtext,
                `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `transaction_uuid` (`transaction_uuid`),
                KEY `level` (`level`),
                KEY `created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1";
            
            $this->db->exec($sql);
            
        } catch (Exception $e) {
            error_log("Erro ao criar tabela addon_pix_logs: " . $e->getMessage());
            throw new Exception("Erro ao criar tabela de logs: " . $e->getMessage());
        }
    }
    
    /**
     * Gera um PIX dinâmico para um lançamento
     */
    public function generatePix($lancId, $gateway = 'banco_inter') {
        try {
            // DEBUG: Log início generatePix
            $debug_log = __DIR__ . '/../admin/api/debug_generate_pix.log';
            file_put_contents($debug_log, date('Y-m-d H:i:s') . " - [generatePix] Início para lancId=$lancId, gateway=$gateway\n", FILE_APPEND);

            // Busca dados do lançamento e cliente
            $lancamento = $this->getLancamento($lancId);
            file_put_contents($debug_log, date('Y-m-d H:i:s') . " - [generatePix] getLancamento OK\n", FILE_APPEND);
            if (!$lancamento) {
                file_put_contents($debug_log, date('Y-m-d H:i:s') . " - [generatePix] Lançamento não encontrado\n", FILE_APPEND);
                throw new Exception("Lançamento não encontrado: {$lancId}");
            }

            $cliente = $this->getCliente($lancamento['login']);
            file_put_contents($debug_log, date('Y-m-d H:i:s') . " - [generatePix] getCliente OK\n", FILE_APPEND);
            if (!$cliente) {
                file_put_contents($debug_log, date('Y-m-d H:i:s') . " - [generatePix] Cliente não encontrado\n", FILE_APPEND);
                throw new Exception("Cliente não encontrado: {$lancamento['login']}");
            }

            // Verifica se já existe PIX ativo para este lançamento
            $existingPix = $this->getActivePixByLanc($lancId);
            file_put_contents($debug_log, date('Y-m-d H:i:s') . " - [generatePix] getActivePixByLanc OK\n", FILE_APPEND);
            if ($existingPix) {
                file_put_contents($debug_log, date('Y-m-d H:i:s') . " - [generatePix] PIX já existente, retornando\n", FILE_APPEND);
                return $this->formatPixResponse($existingPix);
            }

            // Inicializa gateway

            // Inicializa gateway
            $this->gateway = $this->getGatewayInstance($gateway);
            file_put_contents($debug_log, date('Y-m-d H:i:s') . " - [generatePix] getGatewayInstance OK\n", FILE_APPEND);

            // Gera UUID único
            $uuid = $this->generateUUID();
            file_put_contents($debug_log, date('Y-m-d H:i:s') . " - [generatePix] generateUUID OK\n", FILE_APPEND);

            // Calcula valor e data de expiração
            $amount = floatval(str_replace(',', '.', $lancamento['valor']));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+' . getPixConfig('expiration_minutes') . ' minutes'));
            file_put_contents($debug_log, date('Y-m-d H:i:s') . " - [generatePix] amount/expiration OK\n", FILE_APPEND);

            // Cria transação PIX no gateway
            $pixData = $this->gateway->createPix([
                'uuid' => $uuid,
                'amount' => $amount,
                'description' => "Pagamento - {$lancamento['recibo']}",
                'payer_name' => $cliente['nome'],
                'payer_document' => $cliente['cpf_cnpj'],
                'expires_at' => $expiresAt
            ]);
            file_put_contents($debug_log, date('Y-m-d H:i:s') . " - [generatePix] createPix OK\n", FILE_APPEND);

            // Salva no banco
            $pixTransaction = [
                'uuid' => $uuid,
                'lanc_id' => $lancId,
                'cliente_id' => $cliente['id'],
                'gateway' => $gateway,
                'transaction_id' => $pixData['transaction_id'],
                'pix_key' => $pixData['pix_key'],
                'qr_code' => $pixData['qr_code'],
                'pix_code' => $pixData['pix_code'],
                'amount' => $amount,
                'expires_at' => $expiresAt,
                'public_url' => getPixConfig('public_url_base') . 'view.php?uuid=' . $uuid
            ];
            
            $this->savePixTransaction($pixTransaction);
            
            $this->log('info', "PIX gerado com sucesso", [
                'uuid' => $uuid,
                'lanc_id' => $lancId,
                'amount' => $amount
            ]);
            
            return $this->formatPixResponse($pixTransaction);
            
        } catch (Exception $e) {
            $this->log('error', 'Erro ao gerar PIX: ' . $e->getMessage(), [
                'lanc_id' => $lancId,
                'gateway' => $gateway
            ]);
            throw $e;
        }
    }
    
    /**
     * Busca dados do lançamento
     */
    public function getLancamento($lancId) {
        $stmt = $this->db->prepare("
            SELECT l.*, c.nome as cliente_nome, c.cpf_cnpj, c.email
            FROM sis_lanc l
            LEFT JOIN sis_cliente c ON l.login = c.login
            WHERE l.id = ?
        ");
        $stmt->execute([$lancId]);
        return $stmt->fetch();
    }
    
    /**
     * Busca dados do cliente
     */
    public function getCliente($login) {
        $stmt = $this->db->prepare("SELECT * FROM sis_cliente WHERE login = ?");
        $stmt->execute([$login]);
        return $stmt->fetch();
    }
    
    /**
     * Busca PIX ativo por lançamento
     */
    public function getActivePixByLanc($lancId) {
        $stmt = $this->db->prepare("
            SELECT * FROM addon_pix_transactions 
            WHERE lanc_id = ? 
            AND status IN ('pending') 
            AND expires_at > NOW()
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$lancId]);
        return $stmt->fetch();
    }
    
    /**
     * Busca PIX por UUID
     */
    public function getPixByUuid($uuid) {
        $stmt = $this->db->prepare("
            SELECT pt.*, l.recibo, l.valor, l.datavenc, l.obs,
                   c.nome as cliente_nome, c.cpf_cnpj, c.email
            FROM addon_pix_transactions pt
            LEFT JOIN sis_lanc l ON pt.lanc_id = l.id
            LEFT JOIN sis_cliente c ON pt.cliente_id = c.id
            WHERE pt.uuid = ?
        ");
        $stmt->execute([$uuid]);
        return $stmt->fetch();
    }
    
    /**
     * Busca PIX por ID da transação do gateway
     */
    public function getPixByTransactionId($transactionId) {
        $stmt = $this->db->prepare("
            SELECT pt.*, l.recibo, l.valor, l.datavenc, l.obs,
                   c.nome as cliente_nome, c.cpf_cnpj, c.email
            FROM addon_pix_transactions pt
            LEFT JOIN sis_lanc l ON pt.lanc_id = l.id
            LEFT JOIN sis_cliente c ON pt.cliente_id = c.id
            WHERE pt.transaction_id = ?
        ");
        $stmt->execute([$transactionId]);
        return $stmt->fetch();
    }
    
    /**
     * Retorna instância do banco de dados (para uso em webhooks)
     */
    public function getDatabase() {
        return $this->db;
    }
    
    /**
     * Salva transação PIX
     */
    private function savePixTransaction($data) {
        $stmt = $this->db->prepare("
            INSERT INTO addon_pix_transactions 
            (uuid, lanc_id, cliente_id, gateway, transaction_id, pix_key, 
             qr_code, pix_code, amount, expires_at, public_url)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $data['uuid'],
            $data['lanc_id'],
            $data['cliente_id'],
            $data['gateway'],
            $data['transaction_id'],
            $data['pix_key'],
            $data['qr_code'],
            $data['pix_code'],
            $data['amount'],
            $data['expires_at'],
            $data['public_url']
        ]);
    }
    
    /**
     * Atualiza status do PIX
     */
    public function updatePixStatus($uuid, $status, $paidAt = null, $webhookData = null) {
        $stmt = $this->db->prepare("
            UPDATE addon_pix_transactions 
            SET status = ?, paid_at = ?, webhook_data = ?, updated_at = NOW()
            WHERE uuid = ?
        ");
        
        $result = $stmt->execute([
            $status,
            $paidAt,
            $webhookData ? json_encode($webhookData) : null,
            $uuid
        ]);
        
        // Se o pagamento foi confirmado, processar baixa automática
        if ($result && $status === 'paid') {
            $this->processarPagamentoConfirmado($uuid, $paidAt, $webhookData);
        }
        
        return $result;
    }
    
    /**
     * Processa pagamento confirmado - baixa no lançamento e registro no caixa
     */
    private function processarPagamentoConfirmado($uuid, $paidAt, $webhookData) {
        try {
            // Buscar dados da transação PIX
            $pixData = $this->getPixByUuid($uuid);
            if (!$pixData) {
                throw new Exception("Transação PIX não encontrada: {$uuid}");
            }
            
            $this->db->beginTransaction();
            
            // 1. Dar baixa no lançamento se configurado
            if (getPixConfig('auto_baixa_lancamento')) {
                $this->darBaixaLancamento($pixData['lanc_id'], $paidAt, $pixData['amount']);
            }
            
            // 2. Registrar no caixa se configurado
            if (getPixConfig('auto_registro_caixa')) {
                $this->registrarMovimentoCaixa($pixData, $paidAt);
            }
            
            $this->db->commit();
            
            $this->log('info', 'Pagamento processado com sucesso', [
                'uuid' => $uuid,
                'lanc_id' => $pixData['lanc_id'],
                'valor' => $pixData['amount']
            ]);
            
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->log('error', 'Erro ao processar pagamento confirmado: ' . $e->getMessage(), [
                'uuid' => $uuid,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Dar baixa no lançamento
     */
    private function darBaixaLancamento($lancId, $paidAt, $valor) {
        $dataPagamento = $paidAt ? date('Y-m-d H:i:s', strtotime($paidAt)) : date('Y-m-d H:i:s');
        
        $stmt = $this->db->prepare("
            UPDATE sis_lanc 
            SET status = 'pago', 
                datapag = ?, 
                valorpag = ?,
                obs = CONCAT(COALESCE(obs, ''), ' | Pago via PIX em ', ?)
            WHERE id = ?
        ");
        
        $result = $stmt->execute([
            $dataPagamento,
            $valor,
            $dataPagamento,
            $lancId
        ]);
        
        if (!$result) {
            throw new Exception("Erro ao dar baixa no lançamento ID: {$lancId}");
        }
        
        $this->log('info', "Baixa realizada no lançamento {$lancId}", [
            'lanc_id' => $lancId,
            'valor_pago' => $valor,
            'data_pagamento' => $dataPagamento
        ]);
    }
    
    /**
     * Registrar movimento no caixa (tabela sis_caixa)
     */
    private function registrarMovimentoCaixa($pixData, $paidAt) {
        // Gerar UUID para o registro do caixa
        $uuidCaixa = $this->generateUUID();
        $dataPagamento = $paidAt ? date('Y-m-d H:i:s', strtotime($paidAt)) : date('Y-m-d H:i:s');
        
        // Montar histórico e complemento
        $historico = "Recebimento PIX - Recibo: " . ($pixData['recibo'] ?: 'N/A');
        $complemento = json_encode([
            'pix_uuid' => $pixData['uuid'],
            'lanc_id' => $pixData['lanc_id'],
            'cliente' => $pixData['cliente_nome'],
            'gateway' => $pixData['gateway'],
            'transaction_id' => $pixData['transaction_id'],
            'origem' => 'addon_pix_dinamico'
        ]);
        
        $stmt = $this->db->prepare("
            INSERT INTO sis_caixa 
            (uuid_caixa, usuario, data, historico, complemento, entrada, saida, tipomov, planodecontas)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $uuidCaixa,
            'sistema_pix', // Usuário do sistema
            $dataPagamento,
            $historico,
            $complemento,
            $pixData['amount'], // Entrada
            null, // Saída
            'aut', // Movimento automático
            'Recebimentos PIX' // Plano de contas
        ]);
        
        if (!$result) {
            throw new Exception("Erro ao registrar movimento no caixa");
        }
        
        $this->log('info', "Movimento registrado no caixa", [
            'uuid_caixa' => $uuidCaixa,
            'valor' => $pixData['amount'],
            'lanc_id' => $pixData['lanc_id']
        ]);
    }
    
    /**
     * Obtém instância do gateway
     */
    private function getGatewayInstance($gateway) {
        // Detectar problemas de conectividade
        $hasConnectivity = $this->checkInternetConnectivity();
        
        if (!$hasConnectivity && $gateway === 'banco_inter') {
            // Usar gateway offline se não houver conectividade
            $this->log('warning', 'Conectividade indisponível. Usando modo offline.', [
                'gateway_requested' => $gateway,
                'gateway_used' => 'offline'
            ]);
            
            require_once __DIR__ . '/Gateways/OfflineGateway.php';
            return new \PixDinamico\Gateways\OfflineGateway();
        }
        
        $className = 'PixDinamico\\Gateways\\' . ucfirst(str_replace('_', '', ucwords($gateway, '_'))) . 'Gateway';
        
        $debug_log = __DIR__ . '/../admin/api/debug_generate_pix.log';
        file_put_contents($debug_log, date('Y-m-d H:i:s') . " - [getGatewayInstance] className=$className\n", FILE_APPEND);
        
        // Carregar arquivo do gateway manualmente
        $gatewayFile = __DIR__ . '/Gateways/' . ucfirst(str_replace('_', '', ucwords($gateway, '_'))) . 'Gateway.php';
        file_put_contents($debug_log, date('Y-m-d H:i:s') . " - [getGatewayInstance] gatewayFile=$gatewayFile\n", FILE_APPEND);
        
        if (file_exists($gatewayFile)) {
            file_put_contents($debug_log, date('Y-m-d H:i:s') . " - [getGatewayInstance] Arquivo existe, requerendo...\n", FILE_APPEND);
            require_once $gatewayFile;
            file_put_contents($debug_log, date('Y-m-d H:i:s') . " - [getGatewayInstance] Arquivo requerido\n", FILE_APPEND);
        } else {
            file_put_contents($debug_log, date('Y-m-d H:i:s') . " - [getGatewayInstance] Arquivo NÃO existe!\n", FILE_APPEND);
        }
        
        if (!class_exists($className)) {
            file_put_contents($debug_log, date('Y-m-d H:i:s') . " - [getGatewayInstance] Classe NÃO existe!\n", FILE_APPEND);
            throw new Exception("Gateway não encontrado: {$gateway}");
        }
        file_put_contents($debug_log, date('Y-m-d H:i:s') . " - [getGatewayInstance] Classe existe\n", FILE_APPEND);
        
        try {
            // Carregar configurações do gateway (arquivo config.php)
            file_put_contents($debug_log, date('Y-m-d H:i:s') . " - [getGatewayInstance] Antes de instanciar $className\n", FILE_APPEND);
            $gatewayConfig = getGatewayConfig($gateway) ?: [];
            file_put_contents($debug_log, date('Y-m-d H:i:s') . " - [getGatewayInstance] getGatewayConfig (arquivo) obtido\n", FILE_APPEND);

            // Mesclar configurações salvas no banco (addon_pix_config) sobre as do arquivo,
            // mas apenas quando o valor do banco NÃO for vazio, para não apagar valores válidos do arquivo.
            try {
                $dbConfig = $this->getConfig($gateway);
                if (is_array($dbConfig) && !empty($dbConfig)) {
                    file_put_contents($debug_log, date('Y-m-d H:i:s') . " - [getGatewayInstance] dbConfig (banco) obtido: " . print_r($dbConfig, true) . "\n", FILE_APPEND);
                    foreach ($dbConfig as $key => $value) {
                        if ($value !== '' && $value !== null) {
                            $gatewayConfig[$key] = $value;
                        }
                    }
                } else {
                    file_put_contents($debug_log, date('Y-m-d H:i:s') . " - [getGatewayInstance] Nenhuma config específica no banco para $gateway\n", FILE_APPEND);
                }
            } catch (Exception $e) {
                file_put_contents($debug_log, date('Y-m-d H:i:s') . " - [getGatewayInstance] Erro ao carregar dbConfig: " . $e->getMessage() . "\n", FILE_APPEND);
            }

            // Log detalhado do config final que será enviado ao gateway
            file_put_contents($debug_log, date('Y-m-d H:i:s') . " - [getGatewayInstance] gatewayConfig FINAL: " . print_r($gatewayConfig, true) . "\n", FILE_APPEND);
            
            // Tentar instanciar
            file_put_contents($debug_log, date('Y-m-d H:i:s') . " - [getGatewayInstance] >>>>>> CHAMANDO NEW\n", FILE_APPEND);
            try {
                $instance = new $className($gatewayConfig);
                file_put_contents($debug_log, date('Y-m-d H:i:s') . " - [getGatewayInstance] <<<<<< NEW SUCESSO\n", FILE_APPEND);
            } catch (\Throwable $ex) {
                file_put_contents($debug_log, date('Y-m-d H:i:s') . " - [getGatewayInstance] <<<<<< ERRO ao instanciar: " . $ex->getMessage() . " | Trace: " . $ex->getTraceAsString() . "\n", FILE_APPEND);
                throw $ex;
            }
            
            file_put_contents($debug_log, date('Y-m-d H:i:s') . " - [getGatewayInstance] Antes de testar conectividade\n", FILE_APPEND);
            
            // Testar se o gateway funciona
            if ($gateway === 'banco_inter' && !$this->testGatewayConnectivity($instance)) {
                file_put_contents($debug_log, date('Y-m-d H:i:s') . " - [getGatewayInstance] Conectividade falhou\n", FILE_APPEND);
                $this->log('warning', 'Gateway principal falhou. Usando modo offline.', [
                    'gateway_requested' => $gateway,
                    'gateway_used' => 'offline'
                ]);
                
                file_put_contents($debug_log, date('Y-m-d H:i:s') . " - [getGatewayInstance] Carregando OfflineGateway\n", FILE_APPEND);
                try {
                    $offlinePath = __DIR__ . '/Gateways/OfflineGateway.php';
                    file_put_contents($debug_log, date('Y-m-d H:i:s') . " - [getGatewayInstance] offlinePath=$offlinePath\n", FILE_APPEND);
                    if (!file_exists($offlinePath)) {
                        file_put_contents($debug_log, date('Y-m-d H:i:s') . " - [getGatewayInstance] OfflineGateway NÃO existe!\n", FILE_APPEND);
                        throw new Exception("OfflineGateway não encontrado");
                    }
                    require_once $offlinePath;
                    file_put_contents($debug_log, date('Y-m-d H:i:s') . " - [getGatewayInstance] OfflineGateway requerido\n", FILE_APPEND);
                    $offlineInstance = new \PixDinamico\Gateways\OfflineGateway();
                    file_put_contents($debug_log, date('Y-m-d H:i:s') . " - [getGatewayInstance] OfflineGateway instanciado\n", FILE_APPEND);
                    return $offlineInstance;
                } catch (\Throwable $ex) {
                    file_put_contents($debug_log, date('Y-m-d H:i:s') . " - [getGatewayInstance] ERRO ao carregar OfflineGateway: " . $ex->getMessage() . "\n", FILE_APPEND);
                    throw $ex;
                }
            }
            
            file_put_contents($debug_log, date('Y-m-d H:i:s') . " - [getGatewayInstance] Retornando instância principal\n", FILE_APPEND);
            return $instance;
            
        } catch (Exception $e) {
            // Se falhar ao criar o gateway, usar offline
            $this->log('error', 'Erro ao criar gateway. Usando modo offline.', [
                'gateway_requested' => $gateway,
                'error' => $e->getMessage()
            ]);
            
            require_once __DIR__ . '/Gateways/OfflineGateway.php';
            return new \PixDinamico\Gateways\OfflineGateway();
        }
    }
    
    /**
     * Verifica conectividade com internet
     */
    private function checkInternetConnectivity(): bool {
        // Teste rápido com DNS
        $hosts = ['8.8.8.8', 'google.com', 'cdpj-sandbox.partners.bancointer.com.br'];
        
        foreach ($hosts as $host) {
            if ($this->pingHost($host)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Testa conectividade com um host
     */
    private function pingHost(string $host): bool {
        // Teste de resolução DNS
        $ip = gethostbyname($host);
        if ($ip === $host && !filter_var($host, FILTER_VALIDATE_IP)) {
            return false; // DNS falhou
        }
        
        // Teste de conectividade HTTP rápido
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => is_numeric(str_replace('.', '', $host)) ? 'http://' . $host : 'https://' . $host,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 3,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_NOBODY => true // HEAD request apenas
        ]);
        
        $result = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        return empty($error);
    }
    
    /**
     * Testa conectividade específica do gateway
     */
    private function testGatewayConnectivity($gateway): bool {
        try {
            // Usar reflexão para testar método interno
            $reflection = new \ReflectionClass($gateway);
            
            if (!$reflection->hasProperty('baseUrl')) {
                return false;
            }
            
            $baseUrlProp = $reflection->getProperty('baseUrl');
            $baseUrlProp->setAccessible(true);
            $baseUrl = $baseUrlProp->getValue($gateway);
            
            if (empty($baseUrl)) {
                return false;
            }
            
            // Teste rápido na URL base
            return $this->pingHost(parse_url($baseUrl, PHP_URL_HOST));
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Gera UUID v4
     */
    private function generateUUID() {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
    
    /**
     * Formata resposta do PIX
     */
    private function formatPixResponse($pixData) {
        return [
            'uuid' => $pixData['uuid'],
            'qr_code' => $pixData['qr_code'],
            'pix_code' => $pixData['pix_code'],
            'amount' => $pixData['amount'],
            'expires_at' => $pixData['expires_at'],
            'public_url' => $pixData['public_url'],
            'status' => $pixData['status'] ?? 'pending'
        ];
    }
    
    /**
     * Salva configuração no banco
     */
    public function saveConfig($gateway, $key, $value) {
        $stmt = $this->db->prepare("
            INSERT INTO addon_pix_config (gateway, config_key, config_value) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE config_value = ?, updated_at = NOW()
        ");
        
        return $stmt->execute([$gateway, $key, $value, $value]);
    }
    
    /**
     * Busca configuração do banco
     */
    public function getConfig($gateway, $key = null) {
        if ($key) {
            $stmt = $this->db->prepare("
                SELECT config_value FROM addon_pix_config 
                WHERE gateway = ? AND config_key = ?
            ");
            $stmt->execute([$gateway, $key]);
            $result = $stmt->fetch();
            return $result ? $result['config_value'] : null;
        } else {
            $stmt = $this->db->prepare("
                SELECT config_key, config_value FROM addon_pix_config 
                WHERE gateway = ?
            ");
            $stmt->execute([$gateway]);
            $results = $stmt->fetchAll();
            
            $config = [];
            foreach ($results as $row) {
                $config[$row['config_key']] = $row['config_value'];
            }
            return $config;
        }
    }
    
    /**
     * Salva múltiplas configurações
     */
    public function saveMultipleConfigs($configs) {
        $this->db->beginTransaction();
        
        try {
            foreach ($configs as $gateway => $gatewayConfigs) {
                foreach ($gatewayConfigs as $key => $value) {
                    $this->saveConfig($gateway, $key, $value);
                }
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Registra log
     */
    public function log($level, $message, $context = []) {
        try {
            // Verificar se a tabela existe antes de tentar inserir
            $tableExists = $this->checkTableExists('addon_pix_logs');
            
            if ($tableExists) {
                $stmt = $this->db->prepare("
                    INSERT INTO addon_pix_logs (transaction_uuid, level, message, context)
                    VALUES (?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $context['uuid'] ?? null,
                    $level,
                    $message,
                    is_array($context) ? json_encode($context) : $context
                ]);
            }
            
            // Log em arquivo sempre (independente da tabela)
            $logFile = getPixConfig('log_file');
            if ($logFile && is_dir(dirname($logFile))) {
                $logMessage = sprintf(
                    "[%s] %s: %s %s\n",
                    date('Y-m-d H:i:s'),
                    strtoupper($level),
                    $message,
                    $context ? (is_array($context) ? json_encode($context) : $context) : ''
                );
                file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
            }
        } catch (Exception $e) {
            // Falha silenciosa no log para não quebrar o fluxo principal
            error_log("Erro no log PIX: " . $e->getMessage());
        }
    }
    
    /**
     * Verifica se uma tabela existe
     */
    private function checkTableExists($tableName) {
        try {
            $stmt = $this->db->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$tableName]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Força a criação das tabelas (método público para instalação manual)
     */
    public function createTables() {
        return $this->initializeTables();
    }
    
    /**
     * Verifica se todas as tabelas necessárias existem
     */
    public function checkAllTables() {
        $tables = ['addon_pix_config', 'addon_pix_transactions', 'addon_pix_logs'];
        $results = [];
        
        foreach ($tables as $table) {
            $results[$table] = $this->checkTableExists($table);
        }
        
        return $results;
    }
}
?>