<?php
/**
 * Teste Específico de URLs - Banco Inter
 * Execute para identificar exatamente onde está o problema da URL malformada
 */

echo "=== TESTE ESPECÍFICO DE URLs - BANCO INTER ===\n";
echo "Data/Hora: " . date('Y-m-d H:i:s') . "\n\n";

// URLs para testar
$urls = [
    'sandbox' => 'https://cdpj-sandbox.partners.bancointer.com.br',
    'producao' => 'https://cdpj.partners.bancointer.com.br'
];

$endpoints = [
    '/oauth/v2/token',
    '/pix/v2/cob'
];

foreach ($urls as $ambiente => $baseUrl) {
    echo "=== TESTANDO AMBIENTE: " . strtoupper($ambiente) . " ===\n";
    echo "Base URL: $baseUrl\n";
    
    foreach ($endpoints as $endpoint) {
        $fullUrl = $baseUrl . $endpoint;
        echo "\nTestando: $fullUrl\n";
        
        // 1. Validação básica da URL
        if (filter_var($fullUrl, FILTER_VALIDATE_URL)) {
            echo "✅ URL é válida sintaticamente\n";
        } else {
            echo "❌ URL é inválida sintaticamente\n";
            continue;
        }
        
        // 2. Teste de conectividade com cURL
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $fullUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json'
            ],
            CURLOPT_POSTFIELDS => http_build_query([
                'client_id' => 'test',
                'client_secret' => 'test',
                'grant_type' => 'client_credentials'
            ]),
            CURLOPT_SSL_VERIFYPEER => false, // Para teste
            CURLOPT_SSL_VERIFYHOST => false  // Para teste
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        
        echo "HTTP Code: $httpCode\n";
        
        if ($error) {
            echo "❌ Erro cURL: $error\n";
            
            // Analisar tipos de erro
            if (strpos($error, 'malformed') !== false) {
                echo "🔍 ERRO ENCONTRADO: URL malformada no cURL\n";
            } elseif (strpos($error, 'resolve') !== false) {
                echo "🔍 Erro de DNS/resolução de nome\n";
            } elseif (strpos($error, 'connect') !== false) {
                echo "🔍 Erro de conexão\n";
            } elseif (strpos($error, 'timeout') !== false) {
                echo "🔍 Erro de timeout\n";
            }
        } else {
            echo "✅ cURL executado sem erros\n";
            if ($httpCode >= 200 && $httpCode < 300) {
                echo "✅ Resposta HTTP OK\n";
            } elseif ($httpCode >= 400 && $httpCode < 500) {
                echo "ℹ️ Erro cliente (esperado para teste)\n";
            } elseif ($httpCode >= 500) {
                echo "⚠️ Erro servidor\n";
            }
        }
        
        // Informações detalhadas da conexão
        echo "URL Efetiva: " . $info['url'] . "\n";
        echo "Tempo Total: " . $info['total_time'] . "s\n";
        echo "Tempo Conexão: " . $info['connect_time'] . "s\n";
        
        if ($response && strlen($response) < 500) {
            echo "Resposta: " . substr($response, 0, 200) . "...\n";
        }
        
        echo "---\n";
    }
    echo "\n";
}

// Teste específico do problema
echo "=== TESTE DIAGNÓSTICO DO PROBLEMA ===\n";

// Simular exatamente como o código faz
try {
    require_once 'config.php';
    require_once 'src/PixManager.php';
    require_once 'src/Gateways/GatewayInterface.php';
    require_once 'src/Gateways/BancoInterGateway.php';
    
    echo "Classes carregadas com sucesso\n";
    
    // Criar instância do gateway
    $gateway = new PixDinamico\Gateways\BancoInterGateway();
    echo "Gateway instanciado\n";
    
    // Usar reflexão para inspecionar o estado interno
    $reflection = new ReflectionClass($gateway);
    
    // Verificar propriedade config
    $configProp = $reflection->getProperty('config');
    $configProp->setAccessible(true);
    $config = $configProp->getValue($gateway);
    
    echo "Config interno:\n";
    if (is_array($config)) {
        foreach (['sandbox', 'api_url', 'api_url_sandbox', 'client_id', 'client_secret'] as $key) {
            $value = $config[$key] ?? 'N/A';
            if ($key === 'client_secret' && $value !== 'N/A') {
                $value = '***';
            }
            echo "  $key: $value\n";
        }
    } else {
        echo "  ❌ Config não é array: " . gettype($config) . "\n";
    }
    
    // Verificar baseUrl
    $baseUrlProp = $reflection->getProperty('baseUrl');
    $baseUrlProp->setAccessible(true);
    $baseUrl = $baseUrlProp->getValue($gateway);
    
    echo "Base URL interna: " . ($baseUrl ?? 'NULL') . "\n";
    
    // Testar se a baseUrl é válida
    if ($baseUrl) {
        if (filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            echo "✅ Base URL é válida\n";
        } else {
            echo "❌ Base URL é inválida\n";
        }
        
        // Testar construção de URL completa
        $fullUrl = $baseUrl . '/oauth/v2/token';
        echo "URL completa seria: $fullUrl\n";
        
        if (filter_var($fullUrl, FILTER_VALIDATE_URL)) {
            echo "✅ URL completa é válida\n";
        } else {
            echo "❌ URL completa é inválida\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Erro no teste: " . $e->getMessage() . "\n";
}

echo "\n=== INSTRUÇÕES ===\n";
echo "1. Execute este arquivo no servidor onde está o erro\n";
echo "2. Copie TODA a saída\n";
echo "3. Cole no prompt para análise\n";
echo "4. Procure por linhas com '❌ Erro cURL' e 'malformed'\n";
echo "5. Verifique se Base URL está NULL ou inválida\n";

?>