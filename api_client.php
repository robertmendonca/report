<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

function ensureAuthenticated()
{
    global $apipath, $defaultCurlOptions;

    if (!isset($_SESSION['auth_cookie'])) {
        if (!isset($_SESSION['username']) || !isset($_SESSION['password'])) {
            throw new Exception("Utilizador não autenticado.");
        }

        $ch = curl_init();
        curl_setopt_array($ch, $defaultCurlOptions);
        curl_setopt($ch, CURLOPT_URL, "$apipath/login.php");
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'u' => $_SESSION['username'],
            'p' => $_SESSION['password'],
            'l' => 1
        ]);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception("Erro no login automático: " . curl_error($ch));
        }

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($status !== 200) {
            throw new Exception("Login automático falhou com código HTTP $status.");
        }

        $_SESSION['auth_cookie'] = trim($response);
        curl_close($ch);
    }
}

function getApiCurlHandle()
{
    global $defaultCurlOptions;
    ensureAuthenticated();

    $ch = curl_init();
    curl_setopt_array($ch, $defaultCurlOptions);
    curl_setopt($ch, CURLOPT_COOKIE, $_SESSION['auth_cookie']);
    return $ch;
}

function fetchData($method)
{
    global $apipath, $format;
    $ch = getApiCurlHandle();
    curl_setopt($ch, CURLOPT_URL, "$apipath/api.php");
    curl_setopt($ch, CURLOPT_POSTFIELDS, ['m' => $method, 'format' => $format]);

    $out = curl_exec($ch);
    if (curl_errno($ch)) {
        throw new Exception("Erro na API ($method): " . curl_error($ch));
    }

    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($status !== 200) {
        throw new Exception("Erro HTTP $status ao chamar $method");
    }

    return json_decode($out, true);
}
?>
