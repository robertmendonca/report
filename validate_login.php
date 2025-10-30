<?php
session_start();
require_once 'config.php';

function authenticate($username, $password, $ldap = 1)
{
    global $apipath, $defaultCurlOptions;

    $ch = curl_init();
    curl_setopt_array($ch, $defaultCurlOptions);
    curl_setopt($ch, CURLOPT_URL, "$apipath/login.php");
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'u' => $username,
        'p' => $password,
        'l' => $ldap
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $_SESSION['error_message'] = "Erro de ligação ao ARXVIEW: " . curl_error($ch);
        header('Location: login.php');
        exit;
    }

    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status === 200) {
        $_SESSION['auth_cookie'] = trim($response);
        $_SESSION['username'] = $username;
        $_SESSION['password'] = $password;
        header('Location: index.php');
        exit;
    } else {
        $_SESSION['error_message'] = "Credenciais inválidas. Código HTTP: $status";
        header('Location: login.php');
        exit;
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] . '@activedir.service.lsb.esni.ibm.com';
    $password = $_POST['password'];
    authenticate($username, $password);
} else {
    $_SESSION['error_message'] = "Requisição inválida.";
    header('Location: login.php');
    exit;
}
?>
