<?php

# Importar as classes do PHPMailer necessárias para envio de email
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Carregar automaticamente as classes do PHPMailer

# Configurações do SMTP (sem autenticação)
$smtpHost = "158.98.137.90"; 
$smtpPort = 25; 
$fromEmail = "pt-storage@kyndryl.com";
$fromName = "ARXVIEW Backup";

# Verificar argumentos
if ($argc < 3) {
    echo "Uso: php send_email.php 'Assunto' 'Mensagem' '2087a01b.kyndryl.com@amer.teams.ms' [logfile] [backupfile]\n";
    exit(1);
}

$subject = $argv[1];
$body = $argv[2];
$recipient = $argv[3];
$logFile = $argv[4] ?? null;
$backupFile = $argv[5] ?? null;

try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $smtpHost;
    $mail->Port = $smtpPort;
    $mail->SMTPAuth = false; 

    $mail->setFrom($fromEmail, $fromName);
    $mail->addAddress($recipient);
    
    $mail->Subject = $subject;
    $mail->Body = $body;
    $mail->isHTML(true);

    // Anexar log (se existir e for válido)
    if (!empty($logFile) && file_exists($logFile) && is_readable($logFile)) {
        $mail->addAttachment($logFile, basename($logFile));
        echo "✅ Log anexado: $logFile\n";
    } else if (!empty($logFile)) {
        echo "⚠️ Aviso: Arquivo de log não encontrado ou sem permissão de leitura: $logFile\n";
    }

    // Anexar backup (se existir e for válido)
    if (!empty($backupFile) && file_exists($backupFile) && is_readable($backupFile)) {
        $mail->addAttachment($backupFile, basename($backupFile));
        echo "✅ Backup anexado: $backupFile\n";
    } else if (!empty($backupFile)) {
        echo "⚠️ Aviso: Arquivo de backup não encontrado ou sem permissão de leitura: $backupFile\n";
    }

    $mail->send();
    echo "✅ Email enviado com sucesso para $recipient\n";
} catch (Exception $e) {
    echo "❌ Erro ao enviar email: {$mail->ErrorInfo}\n";
}
