<?php

// Configuração base da API ARXVIEW
$arxhost = '158.98.137.91';
$prot = 'https';
$port = '443';
$apipath = "$prot://$arxhost:$port/api";
$format = 'json';

// Configurações padrão do cURL
$defaultCurlOptions = [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/x-www-form-urlencoded'
    ],
];

// Configuração de email (PHPMailer)
$smtpHost = '158.98.137.90';
$smtpPort = 25;
$fromEmail = 'pt-storage@kyndryl.com';
$fromName = 'ARXVIEW Custom Reports';

?>
