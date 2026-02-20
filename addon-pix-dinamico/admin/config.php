<?php
/**
 * Interface de configuração web para o addon PIX Dinâmico
 */

require_once '../config.php';

use PixDinamico\PixManager;

// Verificar se é POST (salvando configurações)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pixManager = new PixManager();
        
        // Processar configurações enviadas
        $configs = $_POST['config'] ?? [];
        
        // Debug: salvar dados recebidos no log (remover em produção)
        $pixManager->log('debug', 'Dados de configuração recebidos', [
            'post_data' => $configs,
            'user_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        // Salvar configurações no banco
        $pixManager->saveMultipleConfigs($configs);
        
        // Log da ação
        $pixManager->log('info', 'Configurações atualizadas via interface web', [
            'configs_updated' => array_keys($configs),
            'user_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        $successMessage = "Configurações salvas com sucesso!";
        
    } catch (Exception $e) {
        $errorMessage = "Erro ao salvar: " . $e->getMessage();
        
        // Log do erro
        if (isset($pixManager)) {
            $pixManager->log('error', 'Erro ao salvar configurações: ' . $e->getMessage(), [
                'configs_attempted' => $configs ?? [],
                'error_trace' => $e->getTraceAsString()
            ]);
        }
    }
}

// Carregar configurações existentes do banco
try {
    if (!isset($pixManager)) {
        $pixManager = new PixManager();
    }
    
    // Carregar configurações salvas do banco
    $savedConfigs = [
        'general' => $pixManager->getConfig('general'),
        'banco_inter' => $pixManager->getConfig('banco_inter')
    ];
    
    // Mesclar com configurações padrão do config.php
    $currentConfigs = [
        'general' => array_merge(getPixConfig(), $savedConfigs['general']),
        'banco_inter' => array_merge(getGatewayConfig('banco_inter'), $savedConfigs['banco_inter'])
    ];
    
} catch (Exception $e) {
    // Se houver erro, usar configurações padrão
    $currentConfigs = [
        'general' => getPixConfig(),
        'banco_inter' => getGatewayConfig('banco_inter')
    ];
}

?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações PIX Dinâmico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .config-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
        }
        .gateway-card {
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 1rem;
        }
        .gateway-header {
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="config-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="fas fa-cogs"></i> Configurações PIX</h1>
                    <p class="mb-0">Configure os gateways de pagamento</p>
                </div>
                <a href="index.html" class="btn btn-light">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <?php if (isset($successMessage)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?= $successMessage ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($errorMessage)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i> <?= $errorMessage ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <form method="POST">
            <!-- Configurações Gerais -->
            <div class="card gateway-card">
                <div class="card-header gateway-header">
                    <h5><i class="fas fa-sliders-h"></i> Configurações Gerais</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="default_gateway" class="form-label">Gateway Padrão</label>
                            <select class="form-select" name="config[general][default_gateway]" id="default_gateway">
                                <option value="banco_inter" <?= ($currentConfigs['general']['default_gateway'] ?? 'banco_inter') === 'banco_inter' ? 'selected' : '' ?>>
                                    Banco Inter
                                </option>
                                <option value="picpay" disabled>PicPay (Em breve)</option>
                                <option value="pagseguro" disabled>PagSeguro (Em breve)</option>
                                <option value="mercado_pago" disabled>Mercado Pago (Em breve)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="expiration_minutes" class="form-label">Tempo de Expiração (minutos)</label>
                            <input 
                                type="number" 
                                class="form-control" 
                                name="config[general][expiration_minutes]" 
                                id="expiration_minutes"
                                value="<?= $currentConfigs['general']['expiration_minutes'] ?? 1440 ?>"
                                min="60" 
                                max="2880"
                            >
                            <div class="form-text">Entre 1 hora (60 min) e 48 horas (2880 min)</div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label for="qr_code_size" class="form-label">Tamanho do QR Code (pixels)</label>
                            <input 
                                type="number" 
                                class="form-control" 
                                name="config[general][qr_code_size]" 
                                id="qr_code_size"
                                value="<?= $currentConfigs['general']['qr_code_size'] ?? 256 ?>"
                                min="128" 
                                max="512"
                            >
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch mt-4">
                                <input 
                                    class="form-check-input" 
                                    type="checkbox" 
                                    name="config[general][log_enabled]" 
                                    id="log_enabled"
                                    value="1"
                                    <?= ($currentConfigs['general']['log_enabled'] ?? true) ? 'checked' : '' ?>
                                >
                                <label class="form-check-label" for="log_enabled">
                                    Habilitar Logs Detalhados
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Banco Inter -->
            <?php $interConfig = $currentConfigs['banco_inter']; ?>
            <div class="card gateway-card">
                <div class="card-header gateway-header d-flex justify-content-between align-items-center">
                    <h5><i class="fas fa-university"></i> Banco Inter</h5>
                    <div class="form-check form-switch">
                        <input 
                            class="form-check-input" 
                            type="checkbox" 
                            name="config[banco_inter][enabled]" 
                            id="inter_enabled"
                            value="1"
                            <?= ($interConfig['enabled'] ?? true) ? 'checked' : '' ?>
                        >
                        <label class="form-check-label" for="inter_enabled">Habilitado</label>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="inter_client_id" class="form-label">
                                <i class="fas fa-key"></i> Client ID
                            </label>
                            <input 
                                type="text" 
                                class="form-control" 
                                name="config[banco_inter][client_id]" 
                                id="inter_client_id"
                                value="<?= htmlspecialchars($interConfig['client_id'] ?? '') ?>"
                                placeholder="Seu Client ID do Banco Inter"
                                autocomplete="off"
                            >
                        </div>
                        <div class="col-md-6">
                            <label for="inter_client_secret" class="form-label">
                                <i class="fas fa-lock"></i> Client Secret
                            </label>
                            <input 
                                type="password" 
                                class="form-control" 
                                name="config[banco_inter][client_secret]" 
                                id="inter_client_secret"
                                value="<?= htmlspecialchars($interConfig['client_secret'] ?? '') ?>"
                                placeholder="Seu Client Secret do Banco Inter"
                                autocomplete="new-password"
                            >
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label for="inter_pix_key" class="form-label">
                                <i class="fab fa-pix"></i> Chave PIX
                            </label>
                            <input 
                                type="text" 
                                class="form-control" 
                                name="config[banco_inter][pix_key]" 
                                id="inter_pix_key"
                                value="<?= htmlspecialchars($interConfig['pix_key'] ?? '') ?>"
                                placeholder="CPF, CNPJ, email ou chave aleatória"
                                autocomplete="off"
                            >
                            <div class="form-text">Chave PIX cadastrada no Banco Inter</div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch mt-4">
                                <input 
                                    class="form-check-input" 
                                    type="checkbox" 
                                    name="config[banco_inter][sandbox]" 
                                    id="inter_sandbox"
                                    value="1"
                                    <?= ($interConfig['sandbox'] ?? true) ? 'checked' : '' ?>
                                >
                                <label class="form-check-label" for="inter_sandbox">
                                    Modo Sandbox (Teste)
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Status dos Certificados -->
                    <div class="mt-4">
                        <h6><i class="fas fa-certificate"></i> Status dos Certificados</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <?php 
                                    $certPath = $interConfig['certificate_path'] ?? (ADDON_PIX_DIR . '/certificates/inter_cert.crt');
                                    if (file_exists($certPath)): ?>
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <span class="text-success">Certificado encontrado</span>
                                    <?php else: ?>
                                        <i class="fas fa-times-circle text-danger me-2"></i>
                                        <span class="text-danger">Certificado não encontrado</span>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted"><?= $certPath ?></small>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <?php 
                                    $keyPath = $interConfig['private_key_path'] ?? (ADDON_PIX_DIR . '/certificates/inter_key.key');
                                    if (file_exists($keyPath)): ?>
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <span class="text-success">Chave privada encontrada</span>
                                    <?php else: ?>
                                        <i class="fas fa-times-circle text-danger me-2"></i>
                                        <span class="text-danger">Chave privada não encontrada</span>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted"><?= $keyPath ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Outros Gateways (Placeholder) -->
            <div class="card gateway-card">
                <div class="card-header gateway-header">
                    <h5><i class="fas fa-credit-card"></i> Outros Gateways</h5>
                </div>
                <div class="card-body">
                    <div class="text-center py-4">
                        <i class="fas fa-tools fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">Em Desenvolvimento</h6>
                        <p class="text-muted">
                            Suporte para PicPay, PagSeguro e Mercado Pago<br>
                            será adicionado em futuras versões.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Botões de Ação -->
            <div class="d-flex justify-content-between mb-4">
                <div>
                    <button type="button" class="btn btn-secondary" onclick="testConnection()">
                        <i class="fas fa-plug"></i> Testar Conexão
                    </button>
                    <button type="button" class="btn btn-info text-white" onclick="viewLogs()">
                        <i class="fas fa-file-alt"></i> Ver Logs
                    </button>
                    <a href="teste_config.php" class="btn btn-warning" target="_blank">
                        <i class="fas fa-vial"></i> Teste de Salvamento
                    </a>
                </div>
                <div>
                    <button type="reset" class="btn btn-outline-secondary">
                        <i class="fas fa-undo"></i> Resetar
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Salvar Configurações
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function testConnection() {
            // Implementar teste de conexão com o gateway selecionado
            alert('Funcionalidade de teste será implementada em breve');
        }

        function viewLogs() {
            // Abrir visualizador de logs
            window.open('logs.php', '_blank');
        }

        // Mostrar/ocultar configurações baseado no status habilitado
        document.getElementById('inter_enabled').addEventListener('change', function() {
            const cardBody = this.closest('.card').querySelector('.card-body');
            const inputs = cardBody.querySelectorAll('input:not([type="checkbox"])');
            
            inputs.forEach(input => {
                input.disabled = !this.checked;
            });
        });

        // Feedback visual ao salvar
        document.querySelector('form').addEventListener('submit', function() {
            const submitBtn = document.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
            submitBtn.disabled = true;
            
            // Reabilitar após 3 segundos caso não haja redirecionamento
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 3000);
        });
        
        // Validação dos campos obrigatórios
        function validateForm() {
            const interEnabled = document.getElementById('inter_enabled').checked;
            
            if (interEnabled) {
                const clientId = document.getElementById('inter_client_id').value.trim();
                const clientSecret = document.getElementById('inter_client_secret').value.trim();
                const pixKey = document.getElementById('inter_pix_key').value.trim();
                
                if (!clientId || !clientSecret || !pixKey) {
                    alert('Por favor, preencha Client ID, Client Secret e Chave PIX para o Banco Inter');
                    return false;
                }
            }
            
            return true;
        }
        
        // Adicionar validação ao formulário
        document.querySelector('form').addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>