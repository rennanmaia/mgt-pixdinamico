# Estrutura do Addon PIX Dinâmico

```
addon-pix-dinamico/
├── README.md                      # Documentação completa
├── config.php                     # Configurações principais
├── 
├── src/                           # Classes principais
│   ├── PixManager.php            # Gerenciador principal do PIX
│   └── Gateways/                 # Gateways de pagamento
│       ├── GatewayInterface.php  # Interface base
│       └── BancoInterGateway.php # Implementação Banco Inter
│
├── admin/                        # Interface administrativa
│   ├── index.html               # Dashboard principal
│   ├── config.php               # Configurações via web
│   └── api/                     # APIs administrativas
│       ├── search_lancamentos.php
│       └── generate_pix.php
│
├── public/                      # Páginas públicas
│   ├── view.php                 # Visualização pública do PIX
│   └── api/
│       └── check_status.php     # Verificação de status
│
├── webhook/                     # Webhooks dos bancos
│   └── banco_inter.php         # Webhook Banco Inter
│
├── logs/                       # Logs do sistema
├── certificates/               # Certificados SSL
└── temp/                      # Arquivos temporários
```

## 🚀 Inicialização Rápida

1. **Copie para o MK-AUTH:**
   ```bash
   cp -r addon-pix-dinamico /var/www/mk-auth/
   ```

2. **Configure permissões:**
   ```bash
   chmod 755 addon-pix-dinamico
   chmod 777 addon-pix-dinamico/{logs,temp}
   ```

3. **Acesse a interface:**
   ```
   https://seu-dominio/mk-auth/addon-pix-dinamico/admin/
   ```

## 🔧 Configuração Mínima

No arquivo `config.php`, configure:

```php
$gatewayConfig = [
    'banco_inter' => [
        'client_id' => 'SEU_CLIENT_ID',
        'client_secret' => 'SEU_CLIENT_SECRET', 
        'pix_key' => 'SUA_CHAVE_PIX',
        'sandbox' => false
    ]
];
```

## 📋 Funcionalidades Implementadas

- ✅ Geração de PIX dinâmico
- ✅ QR Code automático
- ✅ Interface administrativa
- ✅ Página pública responsiva
- ✅ Sistema de webhooks
- ✅ Logs completos
- ✅ Arquitetura modular
- ✅ Integração com MK-AUTH

## 🌟 Próximos Passos

1. Configure suas credenciais do Banco Inter
2. Teste em ambiente sandbox
3. Configure webhooks para produção
4. Personalize conforme necessário

Consulte o README.md completo para instruções detalhadas.