<?php
/**
 * Teste das Correções - URL Malformada
 * Execute para verificar se o problema foi resolvido
 */

echo "=== TESTE DAS CORREÇÕES - URL MALFORMADA ===\n";
echo "Data/Hora: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Carregar as classes
    require_once 'config.php';
    require_once 'src/Gateways/GatewayInterface.php';
    require_once 'src/Gateways/BancoInterGateway.php';
    
    echo "✅ Classes carregadas com sucesso\n\n";
    
    // Criar instância do gateway
    echo "=== TESTE 1: CRIAÇÃO DO GATEWAY ===\n";
    $gateway = new PixDinamico\Gateways\BancoInterGateway();
    echo "✅ Gateway criado com sucesso\n";
    
    // Usar reflexão para verificar estado interno
    $reflection = new ReflectionClass($gateway);
    
    // Verificar baseUrl
    $baseUrlProp = $reflection->getProperty('baseUrl');
    $baseUrlProp->setAccessible(true);
    $baseUrl = $baseUrlProp->getValue($gateway);
    
    echo "Base URL: " . ($baseUrl ?? 'NULL') . "\n";
    
    if ($baseUrl) {
        echo "✅ Base URL configurada corretamente\n";
        
        if (filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            echo "✅ Base URL é válida\n";
        } else {
            echo "❌ Base URL é inválida\n";
        }
    } else {
        echo "❌ Base URL ainda está NULL\n";
    }
    
    // Verificar config
    $configProp = $reflection->getProperty('config');
    $configProp->setAccessible(true);
    $config = $configProp->getValue($gateway);
    
    echo "\nConfigurações carregadas:\n";
    if (is_array($config)) {
        echo "✅ Config é um array válido\n";
        echo "Sandbox: " . ($config['sandbox'] ? 'Sim' : 'Não') . "\n";
        echo "API URL: " . ($config['api_url'] ?? 'N/A') . "\n";
        echo "API URL Sandbox: " . ($config['api_url_sandbox'] ?? 'N/A') . "\n";
        echo "Client ID: " . (empty($config['client_id']) ? '❌ Vazio' : '✅ Configurado') . "\n";
        echo "Client Secret: " . (empty($config['client_secret']) ? '❌ Vazio' : '✅ Configurado') . "\n";
    } else {
        echo "❌ Config não é um array válido\n";
    }
    
    echo "\n=== TESTE 2: CONSTRUÇÃO DE URL ===\n";
    
    // Testar construção de URL
    $testEndpoint = '/oauth/v2/token';
    $fullUrl = $baseUrl . $testEndpoint;
    
    echo "Endpoint: $testEndpoint\n";
    echo "URL Completa: $fullUrl\n";
    
    if (filter_var($fullUrl, FILTER_VALIDATE_URL)) {
        echo "✅ URL completa é válida\n";
    } else {
        echo "❌ URL completa é inválida\n";
    }
    
    echo "\n=== TESTE 3: VALIDAÇÕES DE SEGURANÇA ===\n";
    
    // Testar método makeRequest com reflexão (sem fazer requisição real)
    try {
        $makeRequestMethod = $reflection->getMethod('makeRequest');
        $makeRequestMethod->setAccessible(true);
        
        // Este teste vai falhar por falta de credenciais, mas vai validar a URL
        echo "Testando validação de URL no makeRequest...\n";
        
        try {
            $makeRequestMethod->invoke($gateway, '/test', 'GET', [], false);
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'URL inválida') !== false) {
                echo "❌ Ainda há problema com URL: " . $e->getMessage() . "\n";
            } elseif (strpos($e->getMessage(), 'malformed') !== false) {
                echo "❌ Ainda há erro de URL malformada: " . $e->getMessage() . "\n";
            } elseif (strpos($e->getMessage(), 'Client ID não configurado') !== false) {
                echo "✅ Validação de credenciais funcionando (esperado)\n";
            } else {
                echo "ℹ️ Outro erro (pode ser esperado): " . $e->getMessage() . "\n";
            }
        }
        
    } catch (Exception $e) {
        echo "Erro no teste de makeRequest: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== TESTE 4: SIMULAÇÃO DE AUTENTICAÇÃO ===\n";
    
    // Testar autenticação (vai falhar por falta de credenciais, mas testará URL)
    try {
        $authMethod = $reflection->getMethod('authenticate');
        $authMethod->setAccessible(true);
        $authMethod->invoke($gateway);
        echo "✅ Autenticação funcionou (improvável sem credenciais)\n";
        
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Client ID não configurado') !== false) {
            echo "✅ Erro esperado: Client ID não configurado\n";
        } elseif (strpos($e->getMessage(), 'Client Secret não configurado') !== false) {
            echo "✅ Erro esperado: Client Secret não configurado\n";
        } elseif (strpos($e->getMessage(), 'URL base não configurada') !== false) {
            echo "❌ Problema: URL base ainda não configurada\n";
        } elseif (strpos($e->getMessage(), 'malformed') !== false) {
            echo "❌ Problema: Ainda há URL malformada - " . $e->getMessage() . "\n";
        } else {
            echo "ℹ️ Outro erro: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== RESULTADO ===\n";
    
    if ($baseUrl && filter_var($baseUrl, FILTER_VALIDATE_URL)) {
        echo "✅ CORREÇÃO APLICADA COM SUCESSO!\n";
        echo "✅ Base URL está configurada corretamente\n";
        echo "✅ URLs serão construídas corretamente\n";
        echo "\n📝 PRÓXIMOS PASSOS:\n";
        echo "1. Configure Client ID e Client Secret no admin/config.php\n";
        echo "2. Configure a chave PIX\n";
        echo "3. Faça upload dos certificados (se produção)\n";
        echo "4. Teste a geração de PIX\n";
    } else {
        echo "❌ AINDA HÁ PROBLEMAS COM A URL\n";
        echo "Base URL: " . ($baseUrl ?? 'NULL') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro geral: " . $e->getMessage() . "\n";
}

echo "\n=== FIM DO TESTE ===\n";
?>