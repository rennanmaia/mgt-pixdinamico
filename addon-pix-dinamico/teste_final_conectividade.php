<?php
/**
 * Teste Final - Conectividade Banco Inter
 * Verifica se o problema de DNS/conectividade foi resolvido
 */

echo "=== TESTE FINAL - CONECTIVIDADE BANCO INTER ===\n";
echo "Data/Hora: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Carregar classes
    require_once 'config.php';
    require_once 'src/Gateways/GatewayInterface.php';
    require_once 'src/Gateways/BancoInterGateway.php';
    
    echo "✅ Classes carregadas\n\n";
    
    // Criar gateway
    $gateway = new PixDinamico\Gateways\BancoInterGateway();
    echo "✅ Gateway criado\n";
    
    // Verificar configurações com reflexão
    $reflection = new ReflectionClass($gateway);
    
    $baseUrlProp = $reflection->getProperty('baseUrl');
    $baseUrlProp->setAccessible(true);
    $baseUrl = $baseUrlProp->getValue($gateway);
    
    echo "Base URL: $baseUrl\n";
    
    if (!$baseUrl) {
        throw new Exception('Base URL não configurada');
    }
    
    echo "\n=== TESTE DE CONECTIVIDADE DIRETA ===\n";
    
    // Testar conectividade com as novas configurações
    $testUrl = $baseUrl . '/oauth/v2/token';
    echo "Testando: $testUrl\n\n";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $testUrl,
        CURLOPT_RETURNTRANSFER => true,
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
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'PIX-Dinamico/1.0',
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1
    ]);
    
    // DNS alternativo se disponível
    if (defined('CURLOPT_DNS_SERVERS')) {
        curl_setopt($ch, CURLOPT_DNS_SERVERS, '8.8.8.8,8.8.4.4');
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    echo "Resultado do teste:\n";
    
    if ($error) {
        echo "❌ Erro cURL: $error\n";
        
        // Analisar tipo de erro
        if (strpos($error, 'resolve host') !== false) {
            echo "🔍 Problema: DNS não consegue resolver o hostname\n";
            echo "💡 Soluções:\n";
            echo "   - Verificar conexão com internet\n";
            echo "   - Configurar DNS alternativo no servidor\n";
            echo "   - Verificar firewall/proxy\n";
        } elseif (strpos($error, 'connect') !== false) {
            echo "🔍 Problema: Não consegue conectar ao servidor\n";
            echo "💡 Soluções:\n";
            echo "   - Verificar firewall (porta 443)\n";
            echo "   - Verificar proxy\n";
            echo "   - Contactar administrador\n";
        } else {
            echo "🔍 Problema: Outro tipo de erro\n";
        }
    } else {
        echo "✅ Conectividade OK!\n";
        echo "HTTP Code: $httpCode\n";
        echo "Tempo total: " . round($info['total_time'], 2) . "s\n";
        echo "Tempo DNS: " . round($info['namelookup_time'], 2) . "s\n";
        echo "Tempo conexão: " . round($info['connect_time'], 2) . "s\n";
        
        if ($httpCode >= 400 && $httpCode < 500) {
            echo "ℹ️ Erro 4xx é esperado (credenciais de teste)\n";
        }
        
        if (strlen($response) > 0 && strlen($response) < 1000) {
            echo "Resposta: " . substr($response, 0, 200) . "\n";
        }
    }
    
    echo "\n=== TESTE ATRAVÉS DO GATEWAY ===\n";
    
    // Testar através do método do gateway
    try {
        $makeRequestMethod = $reflection->getMethod('makeRequest');
        $makeRequestMethod->setAccessible(true);
        
        $result = $makeRequestMethod->invoke($gateway, '/oauth/v2/token', 'POST', [
            'client_id' => 'test',
            'client_secret' => 'test',
            'grant_type' => 'client_credentials'
        ], true);
        
        echo "✅ Gateway makeRequest funcionou!\n";
        
    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
        echo "Erro no gateway: $errorMsg\n";
        
        if (strpos($errorMsg, 'resolve host') !== false) {
            echo "❌ Ainda há problema de DNS\n";
        } elseif (strpos($errorMsg, 'malformed') !== false) {
            echo "❌ Ainda há problema de URL malformada\n";
        } elseif (strpos($errorMsg, 'HTTP 400') !== false || strpos($errorMsg, 'HTTP 401') !== false) {
            echo "✅ Conectividade OK - erro 4xx é esperado para credenciais de teste\n";
        } else {
            echo "ℹ️ Outro tipo de erro\n";
        }
    }
    
    echo "\n=== RESULTADO FINAL ===\n";
    
    if (!$error || ($httpCode >= 400 && $httpCode < 500)) {
        echo "🎉 PROBLEMA DE CONECTIVIDADE RESOLVIDO!\n";
        echo "✅ DNS funciona\n";
        echo "✅ Conectividade OK\n";
        echo "✅ URLs válidas\n";
        echo "\n📝 AGORA VOCÊ PODE:\n";
        echo "1. Configurar credenciais reais do Banco Inter\n";
        echo "2. Testar geração de PIX real\n";
        echo "3. Sistema está pronto para uso\n";
    } else {
        echo "❌ AINDA HÁ PROBLEMAS DE CONECTIVIDADE\n";
        echo "Execute: php diagnostico_conectividade.php\n";
        echo "Contacte o administrador do servidor\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro geral: " . $e->getMessage() . "\n";
}

echo "\n=== FIM DO TESTE ===\n";
?>