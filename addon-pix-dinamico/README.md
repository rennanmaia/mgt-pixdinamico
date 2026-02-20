# Addon PIX Dinâmico para MK-AUTH

## 📋 Descrição

Este addon adiciona funcionalidade de geração de PIX dinâmico ao sistema MK-AUTH 25.04 TUX 4.9, permitindo que os usuários gerem PIX para lançamentos de clientes, com QR Code e código compartilháveis.

### 🎯 Funcionalidades

- ✅ Geração de PIX dinâmico integrado com Banco Inter
- ✅ Interface administrativa para buscar e gerar PIX
- ✅ QR Code e código PIX copiáveis
- ✅ Página pública compartilhável para clientes
- ✅ Sistema de webhooks para confirmação automática de pagamentos
- ✅ **Baixa automática nos lançamentos quando PIX é pago**
- ✅ **Registro automático na tabela sis_caixa**
- ✅ Arquitetura modular para suporte a múltiplos bancos/gateways
- ✅ Sistema completo de logs e auditoria
- ✅ **URLs dinâmicas - funciona em qualquer servidor**
- ✅ **Integração completa com sistema de caixa do MK-AUTH**

## 🚀 Instalação

### 1. Pré-requisitos

- MK-AUTH 25.04 TUX 4.9
- PHP 7.4+ com extensões: cURL, PDO, JSON
- MySQL/MariaDB com banco `mkradius`
- Servidor web (Apache/Nginx) com HTTPS configurado

### 2. Instalação do Addon

1. **Copie o addon para o diretório web:**
   ```bash
   cp -r addon-pix-dinamico /var/www/html/mgt-pixdinamico
   ```

2. **Configure as permissões:**
   ```bash
   chmod 755 /var/www/html/mgt-pixdinamico
   chmod 777 /var/www/html/mgt-pixdinamico/logs
   chmod 777 /var/www/html/mgt-pixdinamico/temp
   chmod 644 /var/www/html/mgt-pixdinamico/certificates/*
   ```

3. **Acesse o diagnóstico:**
   ```
   https://seu-dominio/mgt-pixdinamico/diagnostico.php
   ```

### 3. Configuração do Banco Inter

1. **Acesse o Portal de APIs do Banco Inter**
2. **Registre sua aplicação e obtenha:**
   - Client ID
   - Client Secret
   - Certificados SSL (.crt e .key)

3. **Configure os certificados:**
   ```bash
   cp seu_certificado.crt addon-pix-dinamico/certificates/inter_cert.crt
   cp sua_chave_privada.key addon-pix-dinamico/certificates/inter_key.key
   ```

### 4. Configuração Inicial

Edite o arquivo `config.php` e configure:

```php
$gatewayConfig = [
    'banco_inter' => [
        'enabled' => true,
        'client_id' => 'SEU_CLIENT_ID_AQUI',
        'client_secret' => 'SEU_CLIENT_SECRET_AQUI',
        'pix_key' => 'SUA_CHAVE_PIX_AQUI', // CPF, CNPJ, email ou chave aleatória
        'sandbox' => false, // true para teste, false para produção
    ]
];

// Configurações de integração
$pixConfig = [
    'auto_baixa_lancamento' => true,  // Dar baixa automática nos lançamentos
    'auto_registro_caixa' => true,    // Registrar no sis_caixa automaticamente
];
```

### 5. Banco de Dados

O sistema está configurado para:
- **Database:** mkradius
- **Usuário:** root  
- **Senha:** vertrigo

As tabelas necessárias são criadas automaticamente na primeira execução.

## 📖 Como Usar

### 1. Acesso à Interface Administrativa

Acesse: `https://seu-dominio/mgt-pixdinamico/admin/`

### 2. Gerando um PIX

1. **Busque o lançamento:**
   - Por ID do lançamento
   - Por número do recibo
   - Por nome do cliente
   - Por login do cliente

2. **Gere o PIX:**
   - Clique em "Gerar PIX" no lançamento desejado
   - O sistema irá gerar automaticamente:
     - QR Code
     - Código PIX
     - Link público compartilhável

3. **Compartilhe com o cliente:**
   - Copie o link público
   - Compartilhe via WhatsApp
   - Envie por email
   - O cliente pode acessar e pagar diretamente

### 3. Processo Automático de Pagamento

Quando o cliente paga o PIX, o sistema automaticamente:

1. **Recebe o webhook** do Banco Inter
2. **Atualiza o status** da transação PIX para "pago"
3. **Dá baixa no lançamento** (sis_lanc):
   - Marca status como "pago"
   - Registra data e valor do pagamento
   - Adiciona observação sobre o PIX
4. **Registra no caixa** (sis_caixa):
   - Cria entrada de recebimento
   - Associa ao lançamento original
   - Registra dados completos da transação

### 3. Página Pública do Cliente

O cliente acessa o link e visualiza:
- Dados do pagamento (valor, descrição)
- QR Code para escaneio
- Código PIX para cópia manual
- Countdown de expiração
- Status em tempo real

## 🔧 Configurações Avançadas

### Configuração de Webhooks

Para receber confirmações automáticas de pagamento, configure no Portal do Banco Inter:

**URL do Webhook:** `https://seu-dominio/mgt-pixdinamico/webhook/banco_inter.php`

**Importante:** O sistema detecta automaticamente a URL correta baseada no seu domínio.

### Personalização de Expiração

No arquivo `config.php`:

```php
$pixConfig = [
    'expiration_minutes' => 1440, // 24 horas (padrão)
];
```

### Configuração de Logs

```php
$pixConfig = [
    'log_enabled' => true,
    'log_file' => ADDON_PIX_DIR . '/logs/pix.log'
];
```

## 🏗️ Arquitetura Modular

### Adicionando Novos Gateways

O sistema foi projetado para suportar múltiplos bancos/gateways. Para adicionar um novo:

1. **Crie a classe do gateway:**
   ```php
   // src/Gateways/NovoGateway.php
   class NovoGateway implements GatewayInterface {
       // Implementar métodos obrigatórios
   }
   ```

2. **Configure no config.php:**
   ```php
   $gatewayConfig = [
       'novo_gateway' => [
           'name' => 'Novo Gateway',
           'enabled' => true,
           // outras configurações...
       ]
   ];
   ```

3. **Crie o webhook:**
   ```php
   // webhook/novo_gateway.php
   ```

### Gateways Planejados

- ✅ Banco Inter (Implementado)
- ⏳ PicPay (Estrutura preparada)
- ⏳ PagSeguro (Estrutura preparada)
- ⏳ Mercado Pago (Estrutura preparada)

## 📊 Estrutura do Banco de Dados

O addon cria automaticamente as seguintes tabelas:

### `addon_pix_config`
Armazena configurações dos gateways.

### `addon_pix_transactions`
Registra todas as transações PIX geradas com associação aos lançamentos.

### `addon_pix_logs`
Sistema completo de logs para auditoria.

### Integração com `sis_caixa`
Quando um PIX é pago, automaticamente:
- Cria registro de entrada na tabela `sis_caixa`
- Associa ao lançamento original
- Registra dados completos da transação PIX

### Integração com `sis_lanc`
- Atualiza status para "pago"
- Registra data e valor do pagamento
- Adiciona observações sobre a forma de pagamento

## 🔍 Monitoramento e Logs

### Visualizar Logs

```bash
tail -f addon-pix-dinamico/logs/pix.log
```

### Consulta de Transações

```sql
SELECT 
    pt.*,
    l.recibo,
    c.nome as cliente_nome
FROM addon_pix_transactions pt
LEFT JOIN sis_lanc l ON pt.lanc_id = l.id  
LEFT JOIN sis_cliente c ON pt.cliente_id = c.id
WHERE pt.status = 'pending'
ORDER BY pt.created_at DESC;
```

## 🚨 Solução de Problemas

### Erro: "Classe não encontrada"

Verifique se o autoloader está configurado corretamente e se os arquivos têm as permissões adequadas.

### Erro: "Conexão com banco falhou"

Verifique as configurações de banco no arquivo de configuração do MK-AUTH.

### PIX não é gerado

1. Verifique configurações do Banco Inter
2. Confirme que os certificados estão no local correto
3. Verifique logs em `logs/pix.log`

### Webhook não funciona

1. Confirme URL do webhook no Portal do Banco Inter
2. Verifique se HTTPS está configurado
3. Teste conectividade externa

## 🔒 Segurança

### Certificados SSL

- Mantenha os certificados em local seguro
- Renove regularmente conforme orientações do banco
- Nunca exponha chaves privadas

### Validação de Webhooks

- O sistema valida webhooks recebidos
- Logs registram todas as tentativas
- Implementação básica fornecida (expandir conforme necessário)

### Dados Sensíveis

- Configurações são armazenadas de forma segura
- Logs não expõem dados bancários sensíveis
- Comunicação sempre via HTTPS

## 📈 Integração com MK-AUTH

### Atualização Automática de Lançamentos

Quando um PIX é pago, o sistema automaticamente:
- Marca o lançamento como "pago" em `sis_lanc`
- Registra data e valor do pagamento
- Atualiza observações com informações do PIX

### Possíveis Extensões

- Integração com módulo de email do MK-AUTH
- Geração automática de recibos
- Atualização de status de clientes
- Integração com sistema de cobrança

## 🆘 Suporte

### Logs Importantes

- `logs/pix.log` - Logs gerais do addon
- `logs/webhook.log` - Logs específicos de webhooks
- Logs do MySQL/MariaDB
- Logs do servidor web

### Informações para Suporte

Ao solicitar suporte, forneça:
1. Versão do MK-AUTH
2. Logs relevantes (sem dados sensíveis)
3. Configurações utilizadas
4. Descrição detalhada do problema

## 📝 Changelog

### Versão 1.0.0 (Atual)
- ✅ Implementação inicial
- ✅ Integração com Banco Inter
- ✅ Interface administrativa completa
- ✅ Página pública responsiva
- ✅ Sistema de webhooks
- ✅ Logs e auditoria
- ✅ Arquitetura modular preparada

### Próximas Versões
- 🔄 Integração com PicPay
- 🔄 Integração com PagSeguro
- 🔄 Interface de configuração via web
- 🔄 Relatórios e dashboard
- 🔄 API REST para integrações externas

## 📄 Licença

Este addon é fornecido "como está" para integração com MK-AUTH. Use por sua conta e risco, seguindo as boas práticas de segurança e as orientações dos bancos parceiros.

---

**Desenvolvido para MK-AUTH 25.04 TUX 4.9**

*Para dúvidas técnicas, consulte os logs do sistema e a documentação oficial do Banco Inter.*