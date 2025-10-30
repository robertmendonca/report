<?php
session_start();
require_once 'config.php';

/**
 * @throws RuntimeException
 */
function authenticate(string $username, string $password, int $ldap = 1): string
{
    global $apipath, $defaultCurlOptions;

    $payload = http_build_query([
        'u' => $username,
        'p' => $password,
        'l' => $ldap,
    ], '', '&', PHP_QUERY_RFC3986);

    $ch = curl_init("$apipath/login.php");
    curl_setopt_array($ch, $defaultCurlOptions);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

    $response = curl_exec($ch);

    if ($response === false) {
        $errorMessage = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException('Erro de ligação ao ARXVIEW: ' . $errorMessage);
    }

    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status !== 200) {
        $details = trim($response) !== '' ? trim($response) : "Credenciais inválidas. Código HTTP: $status";
        throw new RuntimeException($details);
    }

    return trim($response);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = 'Requisição inválida.';
    header('Location: login.php');
    exit;
}

$usernameInput = filter_input(INPUT_POST, 'username', FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE);
$password = filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE);

if ($usernameInput === null || $usernameInput === '' || $password === null || $password === '') {
    $_SESSION['error_message'] = 'Utilizador e palavra-passe são obrigatórios.';
    header('Location: login.php');
    exit;
}

$username = sprintf('%s@activedir.service.lsb.esni.ibm.com', trim($usernameInput));

try {
    $authCookie = authenticate($username, $password);
} catch (RuntimeException $exception) {
    $_SESSION['error_message'] = $exception->getMessage();
    header('Location: login.php');
    exit;
}

session_regenerate_id(true);
$_SESSION['auth_cookie'] = $authCookie;
$_SESSION['username'] = $username;

header('Location: index.php');
exit;
?>
