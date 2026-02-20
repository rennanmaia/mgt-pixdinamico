<?php
/**
 * Script de verificação e diagnóstico do sistema PIX
 */

require_once 'config.php';

use PixDinamico\PixManager;

?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico PIX - Sistema</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1><i class="fas fa-stethoscope"></i> Diagnóstico do Sistema PIX</h1>
        <p class="text-muted">Verificação de configurações e conectividade</p>

        <?php
        $checks = [];
        
        // 1. Verificar conexão com banco
        try {
            $pixManager = new PixManager(null, false); // Não criar tabelas automaticamente
            $checks[] = ['✅', 'Conexão com banco de dados', 'Sucesso', 'success'];
        } catch (Exception $e) {
            $checks[] = ['❌', 'Conexão com banco de dados', 'Erro: ' . $e->getMessage(), 'danger'];
        }
        
        // 1.1 Verificar se as tabelas existem
        if (isset($pixManager)) {
            try {
                $db = $pixManager->getDatabase();
                $tables = ['addon_pix_config', 'addon_pix_transactions', 'addon_pix_logs'];
                
                foreach ($tables as $table) {
                    $stmt = $db->prepare("SHOW TABLES LIKE ?");
                    $stmt->execute([$table]);
                    
                    if ($stmt->rowCount() > 0) {
                        $checks[] = ['✅', "Tabela {$table}", 'Existe', 'success'];
                    } else {
                        $checks[] = ['❌', "Tabela {$table}", 'Não existe', 'danger'];
                    }
                }
            } catch (Exception $e) {
                $checks[] = ['❌', 'Verificação de tabelas', 'Erro: ' . $e->getMessage(), 'danger'];
            }
        }
        
        // 2. Verificar URLs dinâmicas
        $baseUrl = getBaseUrl();
        $addonUrl = getAddonUrl();
        $checks[] = ['ℹ️', 'URL Base', $baseUrl, 'info'];
        $checks[] = ['ℹ️', 'URL do Addon', $addonUrl, 'info'];
        
        // 3. Verificar configurações PIX
        $pixEnabled = getPixConfig('enabled');
        $checks[] = [$pixEnabled ? '✅' : '❌', 'PIX Habilitado', $pixEnabled ? 'Sim' : 'Não', $pixEnabled ? 'success' : 'warning'];
        
        // 4. Verificar gateway Banco Inter
        $interConfig = getGatewayConfig('banco_inter');
        $interEnabled = $interConfig['enabled'];
        $checks[] = [$interEnabled ? '✅' : '❌', 'Banco Inter Habilitado', $interEnabled ? 'Sim' : 'Não', $interEnabled ? 'success' : 'warning'];
        
        if ($interEnabled) {
            $hasClientId = !empty($interConfig['client_id']);
            $hasClientSecret = !empty($interConfig['client_secret']);
            $hasPixKey = !empty($interConfig['pix_key']);
            $hasCert = file_exists($interConfig['certificate_path']);
            $hasKey = file_exists($interConfig['private_key_path']);
            
            $checks[] = [$hasClientId ? '✅' : '❌', 'Client ID configurado', $hasClientId ? 'Sim' : 'Não', $hasClientId ? 'success' : 'danger'];
            $checks[] = [$hasClientSecret ? '✅' : '❌', 'Client Secret configurado', $hasClientSecret ? 'Sim' : 'Não', $hasClientSecret ? 'success' : 'danger'];
            $checks[] = [$hasPixKey ? '✅' : '❌', 'Chave PIX configurada', $hasPixKey ? 'Sim' : 'Não', $hasPixKey ? 'success' : 'danger'];
            $checks[] = [$hasCert ? '✅' : '❌', 'Certificado SSL', $hasCert ? 'Encontrado' : 'Não encontrado', $hasCert ? 'success' : 'danger'];
            $checks[] = [$hasKey ? '✅' : '❌', 'Chave privada SSL', $hasKey ? 'Encontrada' : 'Não encontrada', $hasKey ? 'success' : 'danger'];
        }
        
        // 5. Verificar diretórios
        $logsDir = ADDON_PIX_DIR . '/logs';
        $certsDir = ADDON_PIX_DIR . '/certificates';
        $tempDir = ADDON_PIX_DIR . '/temp';
        
        $checks[] = [is_dir($logsDir) && is_writable($logsDir) ? '✅' : '❌', 'Diretório logs', is_dir($logsDir) ? (is_writable($logsDir) ? 'Gravável' : 'Não gravável') : 'Não existe', is_dir($logsDir) && is_writable($logsDir) ? 'success' : 'danger'];
        $checks[] = [is_dir($certsDir) ? '✅' : '❌', 'Diretório certificates', is_dir($certsDir) ? 'Existe' : 'Não existe', is_dir($certsDir) ? 'success' : 'warning'];
        $checks[] = [is_dir($tempDir) && is_writable($tempDir) ? '✅' : '❌', 'Diretório temp', is_dir($tempDir) ? (is_writable($tempDir) ? 'Gravável' : 'Não gravável') : 'Não existe', is_dir($tempDir) && is_writable($tempDir) ? 'success' : 'danger'];
        
        // 6. Verificar configurações de integração
        $autoBaixa = getPixConfig('auto_baixa_lancamento');
        $autoCaixa = getPixConfig('auto_registro_caixa');
        
        $checks[] = [$autoBaixa ? '✅' : 'ℹ️', 'Baixa automática de lançamentos', $autoBaixa ? 'Habilitada' : 'Desabilitada', $autoBaixa ? 'success' : 'info'];
        $checks[] = [$autoCaixa ? '✅' : 'ℹ️', 'Registro automático no caixa', $autoCaixa ? 'Habilitado' : 'Desabilitado', $autoCaixa ? 'success' : 'info'];
        
        // 7. Verificar webhook URL
        $webhookUrl = $interConfig['webhook_url'] ?? '';
        $checks[] = ['ℹ️', 'URL do Webhook', $webhookUrl, 'info'];
        ?>

        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-clipboard-check"></i> Resultados da Verificação</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th width="50">Status</th>
                                <th>Item</th>
                                <th>Resultado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($checks as $check): ?>
                            <tr class="table-<?= $check[3] ?>">
                                <td><?= $check[0] ?></td>
                                <td><?= $check[1] ?></td>
                                <td><?= $check[2] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Informações do Sistema -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6><i class="fas fa-info-circle"></i> Informações do Sistema</h6>
                    </div>
                    <div class="card-body">
                        <small>
                            <strong>Versão PHP:</strong> <?= PHP_VERSION ?><br>
                            <strong>Servidor:</strong> <?= $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' ?><br>
                            <strong>Diretório do Addon:</strong> <?= ADDON_PIX_DIR ?><br>
                            <strong>Usuário do Processo:</strong> <?= get_current_user() ?><br>
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6><i class="fas fa-database"></i> Informações do Banco</h6>
                    </div>
                    <div class="card-body">
                        <small>
                            <strong>Host:</strong> <?= $db_host ?><br>
                            <strong>Database:</strong> <?= $db_name ?><br>
                            <strong>Usuário:</strong> <?= $db_user ?><br>
                            <strong>Charset:</strong> utf8mb4<br>
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Próximos Passos -->
        <div class="card mt-4">
            <div class="card-header">
                <h6><i class="fas fa-tasks"></i> Próximos Passos</h6>
            </div>
            <div class="card-body">
                <ol>
                    <li>Configure as credenciais do Banco Inter em <code>config.php</code></li>
                    <li>Faça upload dos certificados SSL para <code>certificates/</code></li>
                    <li>Configure a URL do webhook no Portal do Banco Inter: <code><?= $webhookUrl ?></code></li>
                    <li>Teste em ambiente sandbox antes de ativar produção</li>
                    <li>Acesse a <a href="admin/">interface administrativa</a> para gerar PIX</li>
                </ol>
            </div>
        </div>

        <div class="text-center mt-4">
            <a href="instalar_tabelas.php" class="btn btn-warning">
                <i class="fas fa-database"></i> Instalar Tabelas
            </a>
            <a href="admin/" class="btn btn-primary">
                <i class="fas fa-arrow-right"></i> Ir para Administração
            </a>
            <button onclick="location.reload()" class="btn btn-secondary">
                <i class="fas fa-sync"></i> Atualizar Diagnóstico
            </button>
        </div>
    </div>
</body>
</html>