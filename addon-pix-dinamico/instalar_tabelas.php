<?php
/**
 * Script de instalação manual das tabelas do addon PIX
 */

require_once 'config.php';

?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação de Tabelas - PIX Dinâmico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1><i class="fas fa-database"></i> Instalação de Tabelas PIX</h1>
        <p class="text-muted">Este script cria as tabelas necessárias para o funcionamento do addon PIX</p>

        <?php
        $results = [];
        
        try {
            // Conectar ao banco
            global $db_host, $db_name, $db_user, $db_pass;
            
            $dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
            $pdo = new PDO($dsn, $db_user, $db_pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            $results[] = ['✅', 'Conexão com banco de dados', 'Conectado com sucesso', 'success'];
            
            // 1. Tabela de configurações
            $sql1 = "CREATE TABLE IF NOT EXISTS `addon_pix_config` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `gateway` varchar(50) NOT NULL,
                `config_key` varchar(100) NOT NULL,
                `config_value` text,
                `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `gateway_key` (`gateway`, `config_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1";
            
            $pdo->exec($sql1);
            $results[] = ['✅', 'Tabela addon_pix_config', 'Criada com sucesso', 'success'];
            
            // 2. Tabela de transações
            $sql2 = "CREATE TABLE IF NOT EXISTS `addon_pix_transactions` (
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
            
            $pdo->exec($sql2);
            $results[] = ['✅', 'Tabela addon_pix_transactions', 'Criada com sucesso', 'success'];
            
            // 3. Tabela de logs
            $sql3 = "CREATE TABLE IF NOT EXISTS `addon_pix_logs` (
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
            
            $pdo->exec($sql3);
            $results[] = ['✅', 'Tabela addon_pix_logs', 'Criada com sucesso', 'success'];
            
            // Verificar se as tabelas foram criadas
            $tables = ['addon_pix_config', 'addon_pix_transactions', 'addon_pix_logs'];
            
            foreach ($tables as $table) {
                $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
                $stmt->execute([$table]);
                
                if ($stmt->rowCount() > 0) {
                    $results[] = ['✅', "Verificação {$table}", 'Tabela existe', 'success'];
                } else {
                    $results[] = ['❌', "Verificação {$table}", 'Tabela não encontrada', 'danger'];
                }
            }
            
            // Testar inserção na tabela de logs
            try {
                $stmt = $pdo->prepare("INSERT INTO addon_pix_logs (level, message, context) VALUES (?, ?, ?)");
                $stmt->execute(['info', 'Teste de instalação', '{"teste": "instalacao"}']);
                $results[] = ['✅', 'Teste de inserção', 'Log de teste inserido com sucesso', 'success'];
                
                // Limpar o teste
                $pdo->exec("DELETE FROM addon_pix_logs WHERE message = 'Teste de instalação'");
            } catch (Exception $e) {
                $results[] = ['❌', 'Teste de inserção', 'Erro: ' . $e->getMessage(), 'danger'];
            }
            
        } catch (Exception $e) {
            $results[] = ['❌', 'Erro geral', $e->getMessage(), 'danger'];
        }
        ?>

        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list-check"></i> Resultados da Instalação</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th width="50">Status</th>
                                <th>Operação</th>
                                <th>Resultado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $result): ?>
                            <tr class="table-<?= $result[3] ?>">
                                <td><?= $result[0] ?></td>
                                <td><?= $result[1] ?></td>
                                <td><?= $result[2] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Scripts SQL para execução manual -->
        <div class="card mt-4">
            <div class="card-header">
                <h6><i class="fas fa-code"></i> Scripts SQL (para execução manual se necessário)</h6>
            </div>
            <div class="card-body">
                <div class="accordion" id="sqlAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sql1">
                                Tabela addon_pix_config
                            </button>
                        </h2>
                        <div id="sql1" class="accordion-collapse collapse" data-bs-parent="#sqlAccordion">
                            <div class="accordion-body">
                                <pre><code><?= htmlspecialchars($sql1 ?? '') ?></code></pre>
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sql2">
                                Tabela addon_pix_transactions
                            </button>
                        </h2>
                        <div id="sql2" class="accordion-collapse collapse" data-bs-parent="#sqlAccordion">
                            <div class="accordion-body">
                                <pre><code><?= htmlspecialchars($sql2 ?? '') ?></code></pre>
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sql3">
                                Tabela addon_pix_logs
                            </button>
                        </h2>
                        <div id="sql3" class="accordion-collapse collapse" data-bs-parent="#sqlAccordion">
                            <div class="accordion-body">
                                <pre><code><?= htmlspecialchars($sql3 ?? '') ?></code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-4">
            <a href="diagnostico.php" class="btn btn-primary">
                <i class="fas fa-stethoscope"></i> Executar Diagnóstico
            </a>
            <a href="admin/" class="btn btn-success">
                <i class="fas fa-arrow-right"></i> Ir para Administração
            </a>
            <button onclick="location.reload()" class="btn btn-secondary">
                <i class="fas fa-sync"></i> Executar Novamente
            </button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>