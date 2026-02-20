<?php
/**
 * Configuração do Banco Inter - Exemplo
 * 
 * Para ativar a integração com Banco Inter:
 * 1. Copie este arquivo como inter_config.php
 * 2. Preencha os dados abaixo com suas credenciais
 * 3. Coloque os certificados na pasta certificates/
 */

// NÃO COMMITAR ESTE ARQUIVO COM DADOS REAIS!

$interConfig = [
    'enabled' => true,
    'sandbox' => true, // Mudar para false em produção
    
    // Credenciais obtidas no Portal de APIs do Banco Inter
    'client_id' => 'SEU_CLIENT_ID_AQUI',
    'client_secret' => 'SEU_CLIENT_SECRET_AQUI',
    
    // Sua chave PIX cadastrada no Banco Inter
    'pix_key' => 'sua.chave@pix.com.br', // ou CPF/CNPJ/chave aleatória
    
    // Certificados SSL (baixar do Portal do Banco Inter)
    'certificate_path' => ADDON_PIX_DIR . '/certificates/inter_cert.crt',
    'private_key_path' => ADDON_PIX_DIR . '/certificates/inter_key.key',
];

// Para aplicar as configurações, edite o config.php principal e substitua:
// $gatewayConfig['banco_inter'] = array_merge($gatewayConfig['banco_inter'], $interConfig);

// URLs importantes:
// Produção: https://cdpj.partners.bancointer.com.br
// Sandbox:  https://cdpj-sandbox.partners.bancointer.com.br
// Portal:   https://developers.bancointer.com.br/

// Webhook configurado automaticamente:
// URL: [SEU_DOMINIO]/mgt-pixdinamico/webhook/banco_inter.php
?>