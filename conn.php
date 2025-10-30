<?php

# Configuração de conexão com a API
$arxhost = '158.98.137.91'; // Endereço do servidor da API
$user = 'ptsanmon@activedir.service.lsb.esni.ibm.com'; // Usuário para autenticação
$password = 'j7UZYPUEa83NHAy'; // Senha para autenticação
$ldap = 1; // Indica se a autenticação será por LDAP
$prot = 'https'; // Protocolo utilizado para comunicação com a API
$port = '443'; // Porta para conexão

# Configuração do servidor de email
$smtpHost = '158.98.137.90'; // Servidor SMTP
$smtpPort = 25; // Porta SMTP
$fromEmail = 'pt-storage@kyndryl.com'; // Email do remetente
$fromName = 'ARXVIEW Custom Reports'; // Nome do remetente

# Configuração do formato dos dados da API
$format = 'json'; // Formato de resposta da API

# Construção da URL base da API
$apipath = "$prot://$arxhost:$port/api";

# Configurações para a chamada cURL
$options = array(
    CURLOPT_POST => true, // Indica que as requisições serão POST
    CURLOPT_RETURNTRANSFER => true, // Retorna o conteúdo da resposta como string
    CURLOPT_SSL_VERIFYPEER => false, // Desativa verificação de SSL
    CURLOPT_SSL_VERIFYHOST => false, // Desativa verificação do host SSL
);

# Inicialização do cURL
$ch = curl_init();
curl_setopt_array($ch, $options);

# Login na API
curl_setopt($ch, CURLOPT_URL, "$apipath/login.php"); // URL do endpoint de login
curl_setopt($ch, CURLOPT_POSTFIELDS, array('u' => $user, 'p' => $password, 'l' => $ldap)); // Dados para login
$out = curl_exec($ch); // Execução da requisição
if (curl_errno($ch)) {
    die("Erro ao fazer login: " . curl_error($ch) . "\n"); // Exibe erro se houver falha no login
}
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Verifica o status HTTP da requisição
if ($status != 200) {
    die("HTTP Status do login: $status\n"); // Finaliza se o status não for 200 (sucesso)
}
curl_setopt($ch, CURLOPT_COOKIE, $out); // Armazena o cookie da sessão

# Função para fazer chamadas à API
function fetchData($ch, $method, $format, $apipath)
{
    curl_setopt($ch, CURLOPT_URL, "$apipath/api.php"); // URL da API
    $params = ['m' => $method, 'format' => $format]; // Parâmetros da requisição
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params); // Configura os parâmetros no cURL

    $out = curl_exec($ch); // Executa a requisição
    if (curl_errno($ch)) {
        die("Erro ao chamar a API ($method): " . curl_error($ch) . "\n"); // Exibe erro se houver falha
    }
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Verifica o status HTTP da requisição
    if ($status != 200) {
        die("HTTP Status da API ($method): $status\n"); // Finaliza se o status não for 200
    }
    return json_decode($out, true); // Retorna os dados decodificados
}



?>