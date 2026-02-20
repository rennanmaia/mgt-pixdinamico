<?php
/**
 * Debug API Banco Inter - Problemas JSON
 * Teste específico para identificar problema de JSON vazio
 */

echo "=== DEBUG API BANCO INTER - JSON ===\n";
echo "Data/Hora: " . date('Y-m-d H:i:s') . "\n\n";

echo "🔧 PROBLEMAS IDENTIFICADOS:\n";
echo "❌ SyntaxError: JSON.parse: unexpected end of data\n";
echo "❌ Resposta vazia do servidor\n";
echo "❌ Possível erro na estrutura da API\n\n";

echo "✅ CORREÇÕES APLICADAS:\n";
echo "✅ Método POST para criar PIX (conforme documentação)\n";
echo "✅ Estrutura de payload corrigida\n";
echo "✅ Headers x-conta-corrente adicionado\n";
echo "✅ Melhor tratamento de resposta JSON\n";
echo "✅ Logs detalhados para debug\n\n";

try {
    // Carregar classes
    require_once 'config.php';
    require_once 'src/Gateways/GatewayInterface.php';
    require_once 'src/Gateways/BancoInterGateway.php';
    
    echo "📋 TESTE 1: CONFIGURAÇÃO DA API\n";
    
    // Verificar configurações
    $gatewayConfig = getGatewayConfig('banco_inter');
    echo "URL Sandbox: " . $gatewayConfig['api_url_sandbox'] . "\n";
    echo "Client ID: " . (empty($gatewayConfig['client_id']) ? '❌ Vazio' : '✅ Configurado') . "\n";
    echo "Client Secret: " . (empty($gatewayConfig['client_secret']) ? '❌ Vazio' : '✅ Configurado') . "\n";
    echo "PIX Key: " . (empty($gatewayConfig['pix_key']) ? '❌ Vazio' : '✅ Configurado') . "\n\n";
    
    // Criar gateway
    $gateway = new PixDinamico\Gateways\BancoInterGateway();
    
    echo "📋 TESTE 2: ESTRUTURA DO PAYLOAD\n";
    
    // Teste de estrutura do payload conforme documentação
    $testPayload = [
        'chave' => '12345678000195', // Exemplo
        'solicitacaoPagador' => 'Teste PIX Dinâmico',
        'devedor' => [
            'cpf' => '12345678910',
            'nome' => 'João da Silva'
        ],
        'valor' => [
            'original' => '10.50',
            'modalidadeAlteracao' => 1
        ],
        'calendario' => [
            'expiracao' => 86400
        ]
    ];
    
    echo "Payload de teste:\n";
    echo json_encode($testPayload, JSON_PRETTY_PRINT) . "\n\n";
    
    echo "📋 TESTE 3: CONECTIVIDADE BÁSICA\n";
    
    $testUrl = 'https://cdpj-sandbox.partners.uatinter.co/oauth/v2/token';
    echo "Testando URL de autenticação: $testUrl\n";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $testUrl,
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
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    
    echo "Resultado:\n";
    echo "HTTP Code: $httpCode\n";
    echo "Content-Type: " . ($contentType ?: 'N/A') . "\n";
    echo "cURL Error: " . ($error ?: 'Nenhum') . "\n";
    echo "Response Length: " . strlen($response) . " bytes\n";
    
    if ($response) {
        echo "Response Preview: " . substr($response, 0, 200) . "...\n";
        
        // Tentar fazer parse JSON
        $jsonData = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "✅ JSON válido recebido\n";
        } else {
            echo "❌ JSON inválido: " . json_last_error_msg() . "\n";
        }
    } else {
        echo "❌ Resposta vazia - Este é o problema!\n";
    }
    
    echo "\n📋 TESTE 4: HEADERS E FORMATO\n";
    
    // Testar diferentes Content-Types
    $contentTypes = [
        'application/json',
        'application/x-www-form-urlencoded'
    ];
    
    foreach ($contentTypes as $contentType) {
        echo "\nTestando Content-Type: $contentType\n";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $testUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => [
                "Content-Type: $contentType",
                'Accept: application/json'
            ],
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        if ($contentType === 'application/json') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'client_id' => 'test',
                'client_secret' => 'test',
                'grant_type' => 'client_credentials'
            ]));
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'client_id' => 'test',
                'client_secret' => 'test',
                'grant_type' => 'client_credentials'
            ]));
        }
        
        $testResponse = curl_exec($ch);
        $testHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "HTTP Code: $testHttpCode\n";
        echo "Response: " . (empty($testResponse) ? 'VAZIO' : 'Recebido') . "\n";
    }
    
    echo "\n📋 TESTE 5: DIAGNÓSTICO FINAL\n";
    
    if ($error && strpos($error, 'resolve host') !== false) {
        echo "🔍 PROBLEMA: DNS não resolve o hostname\n";
        echo "💡 SOLUÇÃO: Verificar DNS do servidor\n";
    } elseif (empty($response)) {
        echo "🔍 PROBLEMA: Servidor retorna resposta vazia\n";
        echo "💡 POSSÍVEIS CAUSAS:\n";
        echo "   - Firewall bloqueando resposta\n";
        echo "   - Proxy interceptando\n";
        echo "   - Servidor API com problema\n";
        echo "   - Headers incorretos\n";
        echo "   - SSL/TLS incompatível\n";
    } elseif ($httpCode >= 400) {
        echo "🔍 PROBLEMA: Erro HTTP $httpCode\n";
        echo "💡 POSSÍVEIS CAUSAS:\n";
        echo "   - Credenciais inválidas (esperado para teste)\n";
        echo "   - Endpoint incorreto\n";
        echo "   - Método HTTP incorreto\n";
    } else {
        echo "✅ CONECTIVIDADE OK - Problema pode ser nas credenciais\n";
    }
    
    echo "\n=== SOLUÇÕES RECOMENDADAS ===\n\n";
    
    echo "1. 🔧 PARA RESPOSTA VAZIA:\n";
    echo "   - Verificar firewall do servidor\n";
    echo "   - Verificar proxy/load balancer\n";
    echo "   - Contactar administrador de rede\n";
    echo "   - Testar com curl direto no servidor\n\n";
    
    echo "2. 🔧 PARA PROBLEMAS DNS:\n";
    echo "   - Configurar DNS: 8.8.8.8, 1.1.1.1\n";
    echo "   - Testar: ping cdpj-sandbox.partners.uatinter.co\n";
    echo "   - Verificar /etc/hosts ou equivalente\n\n";
    
    echo "3. 🔧 PARA USO IMEDIATO:\n";
    echo "   - Sistema funciona em modo offline\n";
    echo "   - Configure credenciais reais quando resolver conectividade\n";
    echo "   - Interface mostra status automaticamente\n\n";
    
    echo "4. 🔧 COMANDO DE TESTE DIRETO:\n";
    echo "   curl -v -X POST \\\n";
    echo "     'https://cdpj-sandbox.partners.uatinter.co/oauth/v2/token' \\\n";
    echo "     -H 'Content-Type: application/x-www-form-urlencoded' \\\n";
    echo "     -d 'client_id=test&client_secret=test&grant_type=client_credentials'\n\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}

echo "=== FIM DO DEBUG ===\n";
?>