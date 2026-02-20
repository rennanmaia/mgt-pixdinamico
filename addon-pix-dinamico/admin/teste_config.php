<?php
/**
 * Teste de salvamento de configurações
 */

require_once '../config.php';

use PixDinamico\PixManager;

try {
    $pixManager = new PixManager();
    
    echo "<h3>Teste de Salvamento de Configurações</h3>";
    
    // Teste 1: Salvar uma configuração
    echo "<h4>1. Salvando configuração de teste...</h4>";
    $result1 = $pixManager->saveConfig('banco_inter', 'client_id', 'teste_client_id_123');
    echo $result1 ? "✅ Sucesso<br>" : "❌ Falhou<br>";
    
    // Teste 2: Buscar a configuração salva
    echo "<h4>2. Buscando configuração salva...</h4>";
    $savedValue = $pixManager->getConfig('banco_inter', 'client_id');
    echo "Valor salvo: " . ($savedValue ?: 'Nenhum') . "<br>";
    echo ($savedValue === 'teste_client_id_123') ? "✅ Correto<br>" : "❌ Incorreto<br>";
    
    // Teste 3: Salvar múltiplas configurações
    echo "<h4>3. Salvando múltiplas configurações...</h4>";
    $multiConfigs = [
        'banco_inter' => [
            'client_secret' => 'teste_secret_456',
            'pix_key' => 'teste@pix.com',
            'sandbox' => '1'
        ]
    ];
    $result3 = $pixManager->saveMultipleConfigs($multiConfigs);
    echo $result3 ? "✅ Sucesso<br>" : "❌ Falhou<br>";
    
    // Teste 4: Buscar todas as configurações
    echo "<h4>4. Buscando todas as configurações do Banco Inter...</h4>";
    $allConfigs = $pixManager->getConfig('banco_inter');
    echo "<pre>";
    print_r($allConfigs);
    echo "</pre>";
    
    // Teste 5: Verificar tabela
    echo "<h4>5. Verificando registros na tabela...</h4>";
    $stmt = $pixManager->getDatabase()->query("SELECT * FROM addon_pix_config WHERE gateway = 'banco_inter'");
    $records = $stmt->fetchAll();
    echo "Registros encontrados: " . count($records) . "<br>";
    
    foreach ($records as $record) {
        echo "- {$record['config_key']}: {$record['config_value']}<br>";
    }
    
} catch (Exception $e) {
    echo "<h4 style='color: red'>Erro: " . $e->getMessage() . "</h4>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>

<p><a href="config.php">← Voltar para Configurações</a></p>