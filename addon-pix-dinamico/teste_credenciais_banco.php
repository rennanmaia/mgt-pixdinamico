<?php
/**
 * Teste de Credenciais de Banco - PIX Dinâmico
 * Execute para encontrar a senha correta do banco de dados
 */

echo "=== TESTE DE CREDENCIAIS DO BANCO ===\n";
echo "Data/Hora: " . date('Y-m-d H:i:s') . "\n\n";

$host = 'localhost';
$db = 'mkradius';
$user = 'root';

// Senhas comuns para testar
$senhas = [
    '', // Sem senha (XAMPP padrão)
    'vertrigo', // Configuração atual
    'root', // Senha igual ao usuário
    'password', // Senha comum
    'admin', // Senha comum
    '123456', // Senha comum
    'mkauth', // Senha relacionada ao sistema
    'mysql', // Senha padrão MySQL
];

echo "Testando conexões com diferentes senhas...\n\n";

foreach ($senhas as $index => $senha) {
    echo "Teste " . ($index + 1) . " - Senha: " . (empty($senha) ? '(vazia)' : '"' . $senha . '"') . "\n";
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $senha, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 3
        ]);
        
        echo "✅ CONEXÃO ESTABELECIDA!\n";
        echo "🎯 Senha correta encontrada: " . (empty($senha) ? '(vazia)' : $senha) . "\n\n";
        
        // Testar se consegue acessar o banco
        $stmt = $pdo->query("SELECT DATABASE() as db_name");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Banco conectado: " . $result['db_name'] . "\n";
        
        // Verificar se as tabelas do PIX existem
        echo "\nVerificando tabelas do PIX:\n";
        $tabelas = ['addon_pix_config', 'addon_pix_transactions', 'addon_pix_logs'];
        
        foreach ($tabelas as $tabela) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$tabela'");
            if ($stmt->rowCount() > 0) {
                echo "✅ $tabela existe\n";
            } else {
                echo "❌ $tabela não existe\n";
            }
        }
        
        // Se chegou até aqui, essa é a senha correta
        echo "\n=== SENHA CORRETA ENCONTRADA ===\n";
        echo "Host: $host\n";
        echo "Database: $db\n";
        echo "User: $user\n";
        echo "Password: " . (empty($senha) ? '(vazia)' : $senha) . "\n";
        
        echo "\n📝 ATUALIZE O config.php COM:\n";
        echo "\$db_pass = '" . $senha . "';\n";
        
        break;
        
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Access denied') !== false) {
            echo "❌ Acesso negado\n";
        } elseif (strpos($e->getMessage(), 'Unknown database') !== false) {
            echo "❌ Banco não existe\n";
        } else {
            echo "❌ Erro: " . $e->getMessage() . "\n";
        }
    }
    
    echo "---\n";
}

echo "\n=== INFORMAÇÕES ADICIONAIS ===\n";
echo "Servidor: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Sistema: " . php_uname() . "\n";

// Tentar descobrir mais sobre o ambiente
if (function_exists('phpinfo')) {
    ob_start();
    phpinfo(INFO_VARIABLES);
    $phpinfo = ob_get_clean();
    
    if (strpos($phpinfo, 'XAMPP') !== false) {
        echo "Ambiente: XAMPP detectado\n";
        echo "💡 Dica: XAMPP geralmente usa senha vazia para root\n";
    } elseif (strpos($phpinfo, 'WAMP') !== false) {
        echo "Ambiente: WAMP detectado\n";
        echo "💡 Dica: WAMP geralmente usa senha vazia ou 'root'\n";
    } else {
        echo "Ambiente: Servidor personalizado\n";
    }
}

echo "\n=== FIM DO TESTE ===\n";
?>