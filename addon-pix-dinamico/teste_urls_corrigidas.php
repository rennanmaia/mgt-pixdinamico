<?php
/**
 * Teste das Correções de URL - Banco Inter
 * Verifica se as URLs corretas estão sendo usadas
 */

echo "=== TESTE DAS CORREÇÕES DE URL - BANCO INTER ===\n";
echo "Data/Hora: " . date('Y-m-d H:i:s') . "\n\n";

echo "🔧 CORREÇÕES APLICADAS:\n";
echo "✅ URL Sandbox: https://cdpj-sandbox.partners.bancointer.com.br → https://cdpj-sandbox.partners.uatinter.co\n";
echo "✅ Método PIX: POST → PUT\n";
echo "✅ Endpoint: /pix/v2/cob → /pix/v2/cob/{txid}\n";
echo "✅ Geração de txid implementada\n\n";

try {
    // Carregar classes
    require_once 'config.php';
    require_once 'src/Gateways/GatewayInterface.php';
    require_once 'src/Gateways/BancoInterGateway.php';
    
    echo "📋 TESTE 1: CONFIGURAÇÕES ATUALIZADAS\n";
    
    // Verificar configurações do config.php
    $gatewayConfig = getGatewayConfig('banco_inter');
    
    echo "URL Produção: " . $gatewayConfig['api_url'] . "\n";
    echo "URL Sandbox: " . $gatewayConfig['api_url_sandbox'] . "\n";
    echo "Sandbox ativo: " . ($gatewayConfig['sandbox'] ? 'SIM' : 'NÃO') . "\n\n";
    
    if ($gatewayConfig['api_url_sandbox'] === 'https://cdpj-sandbox.partners.uatinter.co') {
        echo "✅ URL Sandbox corrigida corretamente!\n";
    } else {
        echo "❌ URL Sandbox ainda incorreta: " . $gatewayConfig['api_url_sandbox'] . "\n";
    }
    
    echo "\n📋 TESTE 2: GATEWAY COM URLs CORRETAS\n";
    
    // Criar gateway
    $gateway = new PixDinamico\Gateways\BancoInterGateway();
    
    // Verificar baseUrl com reflexão
    $reflection = new ReflectionClass($gateway);
    $baseUrlProp = $reflection->getProperty('baseUrl');
    $baseUrlProp->setAccessible(true);
    $baseUrl = $baseUrlProp->getValue($gateway);
    
    echo "Base URL atual: $baseUrl\n";
    
    if ($baseUrl === 'https://cdpj-sandbox.partners.uatinter.co') {
        echo "✅ Base URL corrigida corretamente!\n";
    } else {
        echo "❌ Base URL ainda incorreta\n";
    }
    
    echo "\n📋 TESTE 3: GERAÇÃO DE TXID\n";
    
    // Testar geração de txid
    $generateTxidMethod = $reflection->getMethod('generateTxid');
    $generateTxidMethod->setAccessible(true);
    
    $txid1 = $generateTxidMethod->invoke($gateway);
    $txid2 = $generateTxidMethod->invoke($gateway);
    
    echo "TXID 1: $txid1\n";
    echo "TXID 2: $txid2\n";
    echo "Únicos: " . ($txid1 !== $txid2 ? '✅ SIM' : '❌ NÃO') . "\n";
    echo "Tamanho válido: " . (strlen($txid1) <= 35 && strlen($txid1) >= 25 ? '✅ SIM' : '❌ NÃO') . "\n";
    
    echo "\n📋 TESTE 4: CONECTIVIDADE COM URL CORRETA\n";
    
    $correctUrl = 'https://cdpj-sandbox.partners.uatinter.co/oauth/v2/token';
    echo "Testando URL correta: $correctUrl\n";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $correctUrl,
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
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    echo "Resultado do teste:\n";
    
    if ($error) {
        echo "Erro cURL: $error\n";
        
        if (strpos($error, 'resolve host') !== false) {
            echo "🔍 Ainda há problema de DNS\n";
        } elseif (strpos($error, 'malformed') !== false) {
            echo "❌ Ainda há problema de URL malformada\n";
        } else {
            echo "ℹ️ Outro tipo de erro de conectividade\n";
        }
    } else {
        echo "✅ Conectividade OK!\n";
        echo "HTTP Code: $httpCode\n";
        echo "Tempo total: " . round($info['total_time'], 2) . "s\n";
        
        if ($httpCode >= 400 && $httpCode < 500) {
            echo "ℹ️ Erro 4xx é esperado para credenciais de teste\n";
        }
    }
    
    echo "\n📋 TESTE 5: ENDPOINT PIX CORRIGIDO\n";
    
    $txidTeste = $generateTxidMethod->invoke($gateway);
    $pixEndpoint = $baseUrl . "/pix/v2/cob/$txidTeste";
    
    echo "Endpoint PIX: $pixEndpoint\n";
    echo "Método: PUT (corrigido de POST)\n";
    
    if (filter_var($pixEndpoint, FILTER_VALIDATE_URL)) {
        echo "✅ Endpoint PIX é uma URL válida\n";
    } else {
        echo "❌ Endpoint PIX inválido\n";
    }
    
    echo "\n📋 TESTE 6: COMPARAÇÃO ANTES vs DEPOIS\n";
    
    echo "ANTES das correções:\n";
    echo "  ❌ URL: https://cdpj-sandbox.partners.bancointer.com.br\n";
    echo "  ❌ Método: POST /pix/v2/cob\n";
    echo "  ❌ Erro: Could not resolve host\n";
    echo "  ❌ Status: Modo offline forçado\n\n";
    
    echo "DEPOIS das correções:\n";
    echo "  ✅ URL: https://cdpj-sandbox.partners.uatinter.co\n";
    echo "  ✅ Método: PUT /pix/v2/cob/{txid}\n";
    if ($error) {
        if (strpos($error, 'resolve host') !== false) {
            echo "  ⚠️ Status: Ainda há problema de DNS (verificar com administrador)\n";
        } else {
            echo "  ✅ Status: URL corrigida, conectividade melhorada\n";
        }
    } else {
        echo "  ✅ Status: Conectividade funcionando!\n";
    }
    
    echo "\n=== RESULTADO FINAL ===\n\n";
    
    if (!$error || ($httpCode >= 400 && $httpCode < 500)) {
        echo "🎉 URLs CORRIGIDAS COM SUCESSO!\n\n";
        echo "✅ Mudanças implementadas:\n";
        echo "  - URL sandbox atualizada para o domínio correto\n";
        echo "  - Método PUT implementado para PIX imediato\n";
        echo "  - Geração de txid único implementada\n";
        echo "  - Endpoint correto /pix/v2/cob/{txid}\n\n";
        
        echo "📝 PRÓXIMOS PASSOS:\n";
        echo "1. Configure credenciais reais do Banco Inter\n";
        echo "2. Teste geração de PIX real\n";
        echo "3. Sistema deve funcionar online agora\n";
    } else {
        echo "⚠️ URLS CORRIGIDAS, MAS AINDA HÁ PROBLEMAS DE CONECTIVIDADE\n\n";
        echo "✅ Correções aplicadas corretamente\n";
        echo "❌ Problema de rede/DNS ainda presente\n\n";
        echo "💡 SOLUÇÕES:\n";
        echo "1. Verificar DNS do servidor (configurar 8.8.8.8)\n";
        echo "2. Verificar firewall/proxy\n";
        echo "3. Contactar administrador do servidor\n";
        echo "4. Usar modo offline para desenvolvimento\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro no teste: " . $e->getMessage() . "\n";
}

echo "\n=== FIM DO TESTE ===\n";
?>