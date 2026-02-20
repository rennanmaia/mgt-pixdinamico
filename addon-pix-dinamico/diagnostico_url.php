<?php
/**
 * Diagnóstico Detalhado - PIX Dinâmico
 * Execute este arquivo no servidor para diagnosticar o problema de URL malformada
 */

echo "=== DIAGNÓSTICO PIX DINÂMICO - URL MALFORMADA ===\n";
echo "Data/Hora: " . date('Y-m-d H:i:s') . "\n\n";

// 1. Verificar se arquivos existem
echo "1. VERIFICAÇÃO DE ARQUIVOS:\n";
$arquivos = [
    'config.php',
    'src/PixManager.php',
    'src/Gateways/BancoInterGateway.php'
];

foreach ($arquivos as $arquivo) {
    $caminho = __DIR__ . '/' . $arquivo;
    echo ($arquivo . ': ' . (file_exists($caminho) ? '✅ Existe' : '❌ Não encontrado') . "\n");
}
echo "\n";

// 2. Testar carregamento de configurações
echo "2. CONFIGURAÇÕES DO SISTEMA:\n";
try {
    require_once 'config.php';
    
    echo "URL Base: " . (function_exists('getBaseUrl') ? getBaseUrl() : 'Função não encontrada') . "\n";
    echo "URL Addon: " . (function_exists('getAddonUrl') ? getAddonUrl() : 'Função não encontrada') . "\n";
    echo "DB Host: " . $db_host . "\n";
    echo "DB Name: " . $db_name . "\n";
    echo "DB User: " . $db_user . "\n";
    echo "DB Pass: " . (empty($db_pass) ? '(vazio)' : '***configurada***') . "\n";
    
} catch (Exception $e) {
    echo "❌ Erro ao carregar config.php: " . $e->getMessage() . "\n";
}
echo "\n";

// 3. Testar conexão com banco
echo "3. TESTE DE CONEXÃO COM BANCO:\n";
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "✅ Conexão estabelecida\n";
    
    // Verificar tabelas
    $tabelas = ['addon_pix_config', 'addon_pix_transactions', 'addon_pix_logs'];
    foreach ($tabelas as $tabela) {
        $result = $pdo->query("SHOW TABLES LIKE '$tabela'");
        echo "Tabela $tabela: " . ($result->rowCount() > 0 ? '✅ Existe' : '❌ Não existe') . "\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Erro de conexão: " . $e->getMessage() . "\n";
}
echo "\n";

// 4. Testar carregamento das classes
echo "4. TESTE DE CLASSES:\n";
try {
    require_once 'src/PixManager.php';
    echo "✅ PixManager carregado\n";
    
    $pixManager = new PixDinamico\PixManager();
    echo "✅ PixManager instanciado\n";
    
} catch (Exception $e) {
    echo "❌ Erro ao carregar PixManager: " . $e->getMessage() . "\n";
}

try {
    require_once 'src/Gateways/GatewayInterface.php';
    require_once 'src/Gateways/BancoInterGateway.php';
    echo "✅ BancoInterGateway carregado\n";
    
} catch (Exception $e) {
    echo "❌ Erro ao carregar BancoInterGateway: " . $e->getMessage() . "\n";
}
echo "\n";

// 5. Testar configurações do gateway
echo "5. CONFIGURAÇÕES DO GATEWAY BANCO INTER:\n";
try {
    $gatewayConfig = getGatewayConfig('banco_inter');
    
    echo "Nome: " . ($gatewayConfig['name'] ?? 'N/A') . "\n";
    echo "Habilitado: " . ($gatewayConfig['enabled'] ? 'Sim' : 'Não') . "\n";
    echo "Sandbox: " . ($gatewayConfig['sandbox'] ? 'Sim' : 'Não') . "\n";
    echo "API URL: " . ($gatewayConfig['api_url'] ?? 'N/A') . "\n";
    echo "API URL Sandbox: " . ($gatewayConfig['api_url_sandbox'] ?? 'N/A') . "\n";
    echo "Client ID: " . (empty($gatewayConfig['client_id']) ? '❌ Vazio' : '✅ Configurado') . "\n";
    echo "Client Secret: " . (empty($gatewayConfig['client_secret']) ? '❌ Vazio' : '✅ Configurado') . "\n";
    echo "PIX Key: " . (empty($gatewayConfig['pix_key']) ? '❌ Vazio' : '✅ Configurado') . "\n";
    echo "Scope: " . ($gatewayConfig['scope'] ?? 'N/A') . "\n";
    echo "Webhook URL: " . ($gatewayConfig['webhook_url'] ?? 'N/A') . "\n";
    
} catch (Exception $e) {
    echo "❌ Erro ao obter configurações: " . $e->getMessage() . "\n";
}
echo "\n";

// 6. Testar construção de URLs no BancoInterGateway
echo "6. TESTE DE URLS NO BANCO INTER GATEWAY:\n";
try {
    $gateway = new PixDinamico\Gateways\BancoInterGateway();
    
    // Usar reflexão para acessar propriedades privadas
    $reflection = new ReflectionClass($gateway);
    $configProperty = $reflection->getProperty('config');
    $configProperty->setAccessible(true);
    $config = $configProperty->getValue($gateway);
    
    $baseUrlProperty = $reflection->getProperty('baseUrl');
    $baseUrlProperty->setAccessible(true);
    $baseUrl = $baseUrlProperty->getValue($gateway);
    
    echo "Config carregado: " . (is_array($config) ? 'Sim' : 'Não') . "\n";
    echo "Base URL construída: " . ($baseUrl ?? 'NULL/Vazio') . "\n";
    
    if (is_array($config)) {
        echo "Sandbox ativo: " . ($config['sandbox'] ? 'Sim' : 'Não') . "\n";
        echo "URL que deveria usar: " . ($config['sandbox'] ? 
            ($config['api_url_sandbox'] ?? 'N/A') : 
            ($config['api_url'] ?? 'N/A')) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro ao testar gateway: " . $e->getMessage() . "\n";
}
echo "\n";

// 7. Testar configurações salvas no banco
echo "7. CONFIGURAÇÕES SALVAS NO BANCO:\n";
try {
    if (isset($pdo)) {
        $stmt = $pdo->prepare("SELECT config_key, config_value FROM addon_pix_config WHERE gateway = 'banco_inter'");
        $stmt->execute();
        $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($configs)) {
            echo "❌ Nenhuma configuração salva no banco\n";
        } else {
            foreach ($configs as $config) {
                $valor = $config['config_value'];
                if (in_array($config['config_key'], ['client_secret'])) {
                    $valor = '***configurado***';
                }
                echo $config['config_key'] . ': ' . $valor . "\n";
            }
        }
    }
} catch (Exception $e) {
    echo "❌ Erro ao consultar configurações: " . $e->getMessage() . "\n";
}
echo "\n";

// 8. Simular construção de URL de autenticação
echo "8. SIMULAÇÃO DE URL DE AUTENTICAÇÃO:\n";
try {
    $gateway = new PixDinamico\Gateways\BancoInterGateway();
    
    // Simular dados que seriam enviados
    $testData = [
        'client_id' => 'test_client_id',
        'client_secret' => 'test_client_secret',
        'scope' => 'pix-write pix-read',
        'grant_type' => 'client_credentials'
    ];
    
    // Usar reflexão para acessar método privado
    $reflection = new ReflectionClass($gateway);
    $baseUrlProperty = $reflection->getProperty('baseUrl');
    $baseUrlProperty->setAccessible(true);
    $baseUrl = $baseUrlProperty->getValue($gateway);
    
    $endpoint = '/oauth/v2/token';
    $fullUrl = $baseUrl . $endpoint;
    
    echo "Base URL: " . ($baseUrl ?? 'NULL') . "\n";
    echo "Endpoint: " . $endpoint . "\n";
    echo "URL Completa: " . $fullUrl . "\n";
    echo "URL válida: " . (filter_var($fullUrl, FILTER_VALIDATE_URL) ? '✅ Sim' : '❌ Não') . "\n";
    
} catch (Exception $e) {
    echo "❌ Erro na simulação: " . $e->getMessage() . "\n";
}
echo "\n";

// 9. Informações do ambiente
echo "9. INFORMAÇÕES DO AMBIENTE:\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "cURL habilitado: " . (extension_loaded('curl') ? '✅ Sim' : '❌ Não') . "\n";
echo "PDO habilitado: " . (extension_loaded('pdo') ? '✅ Sim' : '❌ Não') . "\n";
echo "JSON habilitado: " . (extension_loaded('json') ? '✅ Sim' : '❌ Não') . "\n";
echo "Servidor: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n";
echo "Script Name: " . ($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "\n";
echo "HTTP Host: " . ($_SERVER['HTTP_HOST'] ?? 'N/A') . "\n";

echo "\n=== FIM DO DIAGNÓSTICO ===\n";
?>