<?php
require_once '../config.php';

use PixDinamico\PixManager;

$uuid = $_GET['uuid'] ?? '';

if (!$uuid) {
    http_response_code(404);
    die('PIX não encontrado');
}

try {
    $pixManager = new PixManager();
    $pixData = $pixManager->getPixByUuid($uuid);
    
    if (!$pixData) {
        http_response_code(404);
        die('PIX não encontrado');
    }
    
    // Verificar se PIX ainda é válido
    $isExpired = strtotime($pixData['expires_at']) < time();
    $isPaid = $pixData['status'] === 'paid';
    
} catch (Exception $e) {
    http_response_code(500);
    die('Erro interno do servidor');
}
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PIX - Pagamento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <meta name="robots" content="noindex, nofollow">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .pix-container {
            max-width: 500px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .pix-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .pix-header {
            background: linear-gradient(135deg, #32cd32 0%, #228b22 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .pix-logo {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .qr-section {
            padding: 2rem;
            text-align: center;
            background: #f8f9fa;
        }
        
        .qr-code-container {
            background: white;
            padding: 1rem;
            border-radius: 15px;
            display: inline-block;
            margin-bottom: 1rem;
        }
        
        .pix-code-section {
            padding: 0 2rem 2rem;
        }
        
        .copy-btn {
            position: relative;
        }
        
        .copy-feedback {
            position: absolute;
            top: -40px;
            left: 50%;
            transform: translateX(-50%);
            background: #28a745;
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 14px;
            opacity: 0;
            transition: opacity 0.3s;
            white-space: nowrap;
            z-index: 1000;
        }
        
        .copy-feedback.show {
            opacity: 1;
        }
        
        .copy-feedback::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #28a745 transparent transparent transparent;
        }
        
        .status-badge {
            font-size: 1.1rem;
            padding: 0.5rem 1rem;
            border-radius: 50px;
        }
        
        .expired-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(220, 53, 69, 0.9);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            font-size: 1.2rem;
            border-radius: 20px;
        }
        
        .paid-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(40, 167, 69, 0.9);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            font-size: 1.2rem;
            border-radius: 20px;
        }
        
        .countdown {
            font-weight: bold;
            color: #dc3545;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .amount {
            font-size: 2rem;
            font-weight: bold;
            color: #32cd32;
            margin: 1rem 0;
        }
        
        @media (max-width: 576px) {
            .pix-container {
                margin: 1rem auto;
            }
            
            .pix-header {
                padding: 1.5rem 1rem;
            }
            
            .qr-section, .pix-code-section {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="pix-container">
        <div class="pix-card position-relative">
            <?php if ($isPaid): ?>
                <div class="paid-overlay">
                    <i class="fas fa-check-circle fa-4x mb-3"></i>
                    <h3>Pagamento Confirmado!</h3>
                    <p>Este PIX já foi pago</p>
                </div>
            <?php elseif ($isExpired): ?>
                <div class="expired-overlay">
                    <i class="fas fa-clock fa-4x mb-3"></i>
                    <h3>PIX Expirado</h3>
                    <p>Este PIX não é mais válido</p>
                </div>
            <?php endif; ?>
            
            <!-- Header -->
            <div class="pix-header">
                <div class="pix-logo">
                    <i class="fab fa-pix"></i>
                </div>
                <h2>Pagamento via PIX</h2>
                <?php if (!$isPaid && !$isExpired): ?>
                    <span class="status-badge bg-warning text-dark">
                        <i class="fas fa-clock"></i> Aguardando Pagamento
                    </span>
                <?php endif; ?>
            </div>
            
            <!-- Informações do Pagamento -->
            <div class="p-3">
                <div class="info-item">
                    <span><i class="fas fa-user"></i> Cliente:</span>
                    <strong><?= htmlspecialchars($pixData['cliente_nome']) ?></strong>
                </div>
                
                <div class="info-item">
                    <span><i class="fas fa-file-invoice"></i> Recibo:</span>
                    <strong><?= htmlspecialchars($pixData['recibo']) ?></strong>
                </div>
                
                <?php if ($pixData['obs']): ?>
                <div class="info-item">
                    <span><i class="fas fa-comment"></i> Descrição:</span>
                    <span><?= htmlspecialchars($pixData['obs']) ?></span>
                </div>
                <?php endif; ?>
                
                <div class="text-center">
                    <div class="amount">
                        R$ <?= number_format($pixData['amount'], 2, ',', '.') ?>
                    </div>
                </div>
            </div>
            
            <?php if (!$isPaid && !$isExpired): ?>
                <!-- QR Code -->
                <div class="qr-section">
                    <h5><i class="fas fa-qrcode"></i> Escaneie o QR Code</h5>
                    <div class="qr-code-container" id="qrContainer">
                        <!-- QR Code será inserido aqui -->
                    </div>
                    <p class="text-muted small mb-0">
                        Abra o app do seu banco e escaneie o código
                    </p>
                </div>
                
                <!-- Código PIX -->
                <div class="pix-code-section">
                    <h6><i class="fas fa-copy"></i> Ou copie o código PIX</h6>
                    <div class="input-group">
                        <input 
                            type="text" 
                            class="form-control" 
                            id="pixCode" 
                            value="<?= htmlspecialchars($pixData['pix_code']) ?>" 
                            readonly
                            style="font-size: 12px;"
                        >
                        <button 
                            class="btn btn-primary copy-btn" 
                            type="button" 
                            onclick="copyPixCode()"
                        >
                            <i class="fas fa-copy"></i>
                            <div class="copy-feedback" id="copyFeedback">
                                Código copiado!
                            </div>
                        </button>
                    </div>
                    
                    <div class="mt-3 text-center">
                        <small class="text-muted">
                            <i class="fas fa-clock"></i>
                            Este PIX expira em: 
                            <span class="countdown" id="countdown">
                                <?= date('d/m/Y H:i', strtotime($pixData['expires_at'])) ?>
                            </span>
                        </small>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Footer -->
            <div class="text-center p-3" style="background-color: #f8f9fa;">
                <small class="text-muted">
                    <i class="fas fa-shield-alt"></i>
                    Pagamento seguro e instantâneo
                </small>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <script>
        // Gerar QR Code
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!$isPaid && !$isExpired && $pixData['pix_code']): ?>
                const qrContainer = document.getElementById('qrContainer');
                
                <?php if ($pixData['qr_code'] && strpos($pixData['qr_code'], 'data:image') === 0): ?>
                    // Se for imagem base64 do banco
                    const img = document.createElement('img');
                    img.src = '<?= $pixData['qr_code'] ?>';
                    img.style.maxWidth = '200px';
                    img.style.height = 'auto';
                    qrContainer.appendChild(img);
                <?php else: ?>
                    // Gerar QR Code do código PIX
                    QRCode.toCanvas('<?= addslashes($pixData['pix_code']) ?>', {
                        width: 200,
                        margin: 1,
                        color: {
                            dark: '#000000',
                            light: '#FFFFFF'
                        }
                    }, function(error, canvas) {
                        if (error) {
                            console.error('Erro ao gerar QR Code:', error);
                            qrContainer.innerHTML = '<p class="text-danger">Erro ao gerar QR Code</p>';
                        } else {
                            qrContainer.appendChild(canvas);
                        }
                    });
                <?php endif; ?>
                
                // Iniciar countdown se não expirado
                startCountdown('<?= $pixData['expires_at'] ?>');
                
                // Verificar status periodicamente
                setInterval(checkPaymentStatus, 30000); // A cada 30 segundos
            <?php endif; ?>
        });
        
        function copyPixCode() {
            const pixCodeInput = document.getElementById('pixCode');
            const feedback = document.getElementById('copyFeedback');
            
            // Selecionar e copiar
            pixCodeInput.select();
            pixCodeInput.setSelectionRange(0, 99999);
            
            try {
                document.execCommand('copy');
                
                // Mostrar feedback
                feedback.classList.add('show');
                
                setTimeout(() => {
                    feedback.classList.remove('show');
                }, 2000);
                
                // Tentar usar a API moderna se disponível
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(pixCodeInput.value);
                }
                
            } catch (err) {
                console.error('Erro ao copiar:', err);
            }
        }
        
        function startCountdown(expiresAt) {
            const countdownElement = document.getElementById('countdown');
            const expiryTime = new Date(expiresAt).getTime();
            
            const timer = setInterval(function() {
                const now = new Date().getTime();
                const distance = expiryTime - now;
                
                if (distance < 0) {
                    clearInterval(timer);
                    location.reload(); // Recarregar página quando expirar
                    return;
                }
                
                const hours = Math.floor(distance / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                
                countdownElement.innerHTML = `${hours}h ${minutes}m ${seconds}s`;
                
                // Destacar quando restam menos de 1 hora
                if (distance < 3600000) { // 1 hora em ms
                    countdownElement.style.color = '#dc3545';
                    countdownElement.style.fontWeight = 'bold';
                }
            }, 1000);
        }
        
        function checkPaymentStatus() {
            fetch(`api/check_status.php?uuid=<?= urlencode($uuid) ?>`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.status === 'paid') {
                        location.reload();
                    }
                })
                .catch(error => {
                    console.log('Erro ao verificar status:', error);
                });
        }
    </script>
</body>
</html>