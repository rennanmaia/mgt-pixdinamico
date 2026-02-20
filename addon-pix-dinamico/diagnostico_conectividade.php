<?php
/**
 * Diagnóstico de Conectividade - Banco Inter API
 * Resolve problemas de DNS e conectividade
 */

echo "=== DIAGNÓSTICO DE CONECTIVIDADE - BANCO INTER ===\n";
echo "Data/Hora: " . date('Y-m-d H:i:s') . "\n\n";

// Verificação defensiva: garantir que a extensão cURL esteja habilitada
if (!function_exists('curl_init')) {
    echo "❌ Extensão cURL do PHP não está habilitada neste servidor.\n";
    echo "Ative a extensão php_curl no php.ini e reinicie o servidor web.\n";
    echo "Sem cURL, não é possível testar a conectividade HTTP com a API do Banco Inter.\n";
    echo "=== FIM DO DIAGNÓSTICO ===\n";
    return;
}

$hosts = [
    'cdpj-sandbox.partners.uatinter.co', // URL correta da sandbox
    'cdpj.partners.bancointer.com.br',
    'google.com' // Para testar conectividade geral
];

echo "1. TESTE DE RESOLUÇÃO DNS:\n";
foreach ($hosts as $host) {
    echo "Testando: $host\n";
    
    // Teste de resolução DNS
    $ip = gethostbyname($host);
    if ($ip === $host) {
        echo "❌ DNS não resolveu\n";
    } else {
        echo "✅ DNS resolvido: $ip\n";
    }
    
    // Teste com nslookup se disponível
    if (function_exists('exec')) {
        $output = [];
        exec("nslookup $host 2>&1", $output, $return_code);
        if ($return_code === 0) {
            echo "✅ nslookup funcionou\n";
        } else {
            echo "❌ nslookup falhou\n";
        }
    }
    
    echo "---\n";
}

echo "\n2. TESTE DE CONECTIVIDADE HTTP:\n";
$urls = [
    'https://cdpj-sandbox.partners.uatinter.co', // URL correta da sandbox
    'https://cdpj.partners.bancointer.com.br',
    'https://google.com'
];

foreach ($urls as $url) {
    echo "Testando conectividade: $url\n";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; PIX-Diagnostico/1.0)'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    if ($error) {
        echo "❌ Erro cURL: $error\n";
        
        // Classificar tipo de erro
        if (strpos($error, 'resolve host') !== false) {
            echo "🔍 Tipo: Problema de DNS\n";
        } elseif (strpos($error, 'connect') !== false) {
            echo "🔍 Tipo: Problema de conectividade\n";
        } elseif (strpos($error, 'timeout') !== false) {
            echo "🔍 Tipo: Timeout\n";
        } elseif (strpos($error, 'SSL') !== false) {
            echo "🔍 Tipo: Problema SSL/TLS\n";
        }
    } else {
        echo "✅ Conectividade OK - HTTP $httpCode\n";
        echo "Tempo resposta: " . round($info['total_time'], 2) . "s\n";
    }
    
    echo "---\n";
}

echo "\n3. INFORMAÇÕES DO AMBIENTE:\n";
echo "Sistema Operacional: " . php_uname('s') . "\n";
echo "PHP Version: " . PHP_VERSION . "\n";
// Proteção extra caso a função curl_version não esteja disponível
if (function_exists('curl_version')) {
    $curlInfo = curl_version();
    echo "cURL Version: " . ($curlInfo['version'] ?? 'N/A') . "\n";
    echo "OpenSSL Version: " . ($curlInfo['ssl_version'] ?? 'N/A') . "\n";
} else {
    echo "cURL Version: N/A (função curl_version indisponível)\n";
    echo "OpenSSL Version: N/A\n";
}

// Verificar configurações de rede do PHP
echo "\nConfigurações PHP:\n";
echo "allow_url_fopen: " . (ini_get('allow_url_fopen') ? 'Habilitado' : 'Desabilitado') . "\n";
echo "user_agent: " . (ini_get('user_agent') ?: 'Padrão') . "\n";
echo "auto_detect_line_endings: " . (ini_get('auto_detect_line_endings') ? 'Habilitado' : 'Desabilitado') . "\n";

// Verificar proxy se configurado
if (getenv('HTTP_PROXY') || getenv('HTTPS_PROXY')) {
    echo "\nProxy detectado:\n";
    echo "HTTP_PROXY: " . (getenv('HTTP_PROXY') ?: 'Não definido') . "\n";
    echo "HTTPS_PROXY: " . (getenv('HTTPS_PROXY') ?: 'Não definido') . "\n";
    echo "NO_PROXY: " . (getenv('NO_PROXY') ?: 'Não definido') . "\n";
}

echo "\n4. TESTES ALTERNATIVOS:\n";

// Teste com diferentes configurações cURL
echo "Testando com diferentes configurações cURL...\n";

$configs = [
    'Padrão' => [],
    'IPv4 apenas' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4],
    'DNS alternativo' => [CURLOPT_DNS_SERVERS => '8.8.8.8,8.8.4.4'],
    'Sem SSL verify' => [CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false],
    'HTTP/1.1' => [CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1]
];

$testUrl = 'https://cdpj-sandbox.partners.uatinter.co'; // URL correta da sandbox

foreach ($configs as $name => $options) {
    echo "\nTeste: $name\n";
    
    $ch = curl_init();
    curl_setopt_array($ch, array_merge([
        CURLOPT_URL => $testUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_CONNECTTIMEOUT => 3
    ], $options));
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($error) {
        echo "❌ $error\n";
    } else {
        echo "✅ Sucesso - HTTP $httpCode\n";
    }
}

echo "\n=== SOLUÇÕES SUGERIDAS ===\n";

echo "Se o problema for DNS:\n";
echo "1. Verificar se o servidor tem acesso à internet\n";
echo "2. Configurar DNS alternativo (8.8.8.8, 1.1.1.1)\n";
echo "3. Verificar firewall/proxy\n";
echo "4. Contactar administrador do servidor\n\n";

echo "Se o problema for SSL:\n";
echo "1. Atualizar certificados CA do sistema\n";
echo "2. Verificar versão do OpenSSL\n";
echo "3. Usar CURLOPT_SSL_VERIFYPEER => false temporariamente\n\n";

echo "Se o problema for firewall:\n";
echo "1. Liberar portas 80 e 443 para saída\n";
echo "2. Liberar domínios *.bancointer.com.br\n";
echo "3. Verificar regras de proxy/firewall\n";

echo "\n=== FIM DO DIAGNÓSTICO ===\n";
?>