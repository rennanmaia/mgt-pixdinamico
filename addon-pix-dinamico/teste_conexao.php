<?php
// Teste de conexão rápida
$host = 'localhost';
$db = 'mkradius';
$user = 'root';
$pass = 'vertrigo';

echo "Testando conexão...\n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "✅ Conexão estabelecida com sucesso!\n";
    
    // Verifica se as tabelas existem
    $tables = ['addon_pix_config', 'addon_pix_transactions', 'addon_pix_logs'];
    foreach ($tables as $table) {
        $result = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($result->rowCount() > 0) {
            echo "✅ Tabela $table existe\n";
        } else {
            echo "❌ Tabela $table não existe\n";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Erro de conexão: " . $e->getMessage() . "\n";
    
    // Tenta sem senha
    echo "\nTentando sem senha...\n";
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        echo "✅ Conexão estabelecida sem senha!\n";
    } catch (PDOException $e2) {
        echo "❌ Erro sem senha: " . $e2->getMessage() . "\n";
    }
}
?>