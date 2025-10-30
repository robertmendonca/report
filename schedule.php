<?php
session_start();

if (!isset($_SESSION['auth_cookie'])) {
  $_SESSION['error_message'] = 'Você precisa fazer login para acessar esta página.';
  header('Location: login.php');
  exit;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
require_once 'api_client.php';
require_once 'config.php';


# Função para carregar clientes de um arquivo CSV
function loadClients($filePath)
{
    $clients = [];
    if (file_exists($filePath)) {
        $file = fopen($filePath, 'r');
        while (($line = fgetcsv($file)) !== false) {
            $clients[] = [
                'subject' => $line[0] ?? '',
                'name' => $line[1] ?? '',
                'volume_name' => !empty($line[2]) ? $line[2] : '', // ✅ Se vazio, retorna string vazia
                'emails' => $line[3] ?? '',
                'default_checkbox' => $line[4] ?? '0',
                'checked_checkbox' => $line[5] ?? '0',
            ];
        }
        fclose($file);
    }
    return $clients;
}


# Função para salvar clientes em um arquivo CSV
function saveClients($filePath, $clients)
{
    $file = fopen($filePath, 'w');
    foreach ($clients as $client) {
        fputcsv($file, [
            $client['subject'],
            $client['name'],
            $client['volume_name'],
            $client['emails'],
            $client['default_checkbox'] ?? '0',
            $client['checked_checkbox'] ?? '0',
        ]);        
    }
    fclose($file);
}

# Função para validar múltiplos emails
function validateEmails($emails)
{
    $emailArray = explode(';', $emails); // Divide os emails por ponto e vírgula
    $validEmails = [];
    foreach ($emailArray as $email) {
        $email = trim($email); // Remove espaços em branco
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $validEmails[] = $email; // Adiciona apenas emails válidos
        }
    }
    return implode(';', $validEmails); // Retorna os emails válidos como string separada por ponto e vírgula
}


$toastMessage = '';
$toastMessageErro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clients = loadClients('clients.csv');

    if (isset($_POST['clients'])) {
        foreach ($_POST['clients'] as $key => $data) {
            if (!empty($data['name']) && !empty($data['emails']) && !empty($data['subject'])) {
                $validatedEmails = validateEmails($data['emails']); // Valida os emails
                if (!empty($validatedEmails)) {
                    $clients[$key] = [
                        'subject' => $data['subject'],
                        'name' => $data['name'],
                        'volume_name' => $data['volume_name'],
                        'emails' => $validatedEmails,
                        'default_checkbox' => isset($data['default_checkbox']) ? '1' : '0', // Checkbox Capacity
                        'checked_checkbox' => isset($data['checked_checkbox']) ? '1' : '0', // Checkbox Volume
                    ];
                }
            }
        }
    }

    if (isset($_POST['delete'])) {
        unset($clients[$_POST['delete']]);
    }

    saveClients('clients.csv', $clients);

    // Define a mensagem do alerta
    $toastMessage = 'Alterações salvas com sucesso!';
}


# Carregar clientes e renderizar formulário
$clients = loadClients('clients.csv');



# Função para renderizar formulário para editar clientes
function renderClientForm($clients)
{

    echo "";
    echo "<table class='table table-striped table-sm'>";
    echo "<thead class='thead-dark'>";
    echo "<tr>";
    echo "<th>Nome Relatório</th>";
    echo "<th>Cliente / Equipamento</th>";
    echo "<th>Volume Name</th>";
    echo "<th>Emails</th>";
    echo "<th>Tipo de Relatório</th>";
    echo "<th>Ações</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";

    foreach ($clients as $key => $client) {
        echo "<tr>";
        echo "<td><input type='text' class='form-control' name='clients[$key][subject]' value='" . htmlspecialchars($client['subject']) . "'></td>";
        echo "<td><input type='text' class='form-control' name='clients[$key][name]' value='" . htmlspecialchars($client['name']) . "'></td>";
        echo "<td><input type='text' class='form-control' name='clients[$key][volume_name]' value='" . htmlspecialchars($client['volume_name']) . "' placeholder='Nome do Volume'></td>";
        echo "<td><textarea class='form-control' name='clients[$key][emails]' placeholder='Insira os emails separados por ponto e vírgula (;)' rows='3'>" . htmlspecialchars($client['emails'], ENT_QUOTES) . "</textarea></td>";
        echo "<td>
                <div class='form-check'>
                    <input class='form-check-input' type='checkbox' name='clients[$key][default_checkbox]' id='flexCheckDefault_$key' value='1' " . ($client['default_checkbox'] === '1' ? 'checked' : '') . ">
                    <label class='form-check-label' for='flexCheckDefault_$key'>
                       Capacity Report
                    </label>
                </div>
                <div class='form-check'>
                    <input class='form-check-input' type='checkbox' name='clients[$key][checked_checkbox]' id='flexCheckChecked_$key' value='1' " . ($client['checked_checkbox'] === '1' ? 'checked' : '') . ">
                    <label class='form-check-label' for='flexCheckChecked_$key'>
                        Volume Report
                    </label>
                </div>
              </td>";

        // Botões de ação
        echo "<td>
        <button type='submit' class='btn btn-danger btn-sm' name='delete' value='$key'>Remover</button>
        <button type='button' 
            class='btn btn-info btn-sm' 
            onclick='testClient(\"$key\")'>
            Enviar
        </button>
        <div id='testResult_$key' class='mt-2'></div>
    </td>";

        echo "</tr>";
    }

    // Adicionar novo cliente
    echo "<tr>";
    echo "<td><input type='text' class='form-control' name='clients[new][subject]' placeholder='Nome Relatório'></td>";
    echo "<td><input type='text' class='form-control' name='clients[new][name]' placeholder='Novo Cliente'></td>";
    echo "<td><input type='text' class='form-control' name='clients[new][volume_name]' placeholder='Nome do Volume'></td>";
    echo "<td><input type='text' class='form-control' name='clients[new][emails]' placeholder='Novos Emails separados por ;'></td>";
    echo "<td>
            <div class='form-check'>
                <input class='form-check-input' type='checkbox' name='clients[new][default_checkbox]' id='flexCheckDefault_new' value='1'>
                <label class='form-check-label' for='flexCheckDefault_new'>
                   Capacity Report
                </label>
            </div>
            <div class='form-check'>
                <input class='form-check-input' type='checkbox' name='clients[new][checked_checkbox]' id='flexCheckChecked_new' value='1'>
                <label class='form-check-label' for='flexCheckChecked_new'>
                   Volume Report
                </label>
            </div>
          </td>";
    echo "<td></td>";
    echo "</tr>";

    echo "</tbody>";
    echo "</table>";
    echo "<div class='text-center'>";
    echo "<button type='submit' class='btn btn-success'>Adicionar / Salvar Alterações</button>";
    echo "</div>";
    echo "</form>";
}

?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <script src="./assets/js/color-modes.js"></script>

    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="" />
    <meta name="author" content="Mark Otto, Jacob Thornton, and Bootstrap contributors" />
    <meta name="generator" content="Hugo 0.122.0" />
    <title>Schedule Reports</title>

    <link href="./assets/css/bootstrap.min.css" rel="stylesheet" />
    <link href="./assets/css/all.css" rel="stylesheet" />

    <!-- Custom styles for this template -->
    <link href="./assets/css/bootstrap-icons.min.css" rel="stylesheet" />
    <!-- Custom styles for this template -->
    <link href="./assets/css/dashboard.css" rel="stylesheet" />
</head>

<body>
    <?php require 'theme.php'; ?>
     <?php require 'menu.php'; ?>

    <!-- Alerta -->
    <?php if (!empty($toastMessage)): ?>
        <div class="toast align-items-center text-bg-success border-0 position-fixed start-50 top-50 translate-middle" role="alert" aria-live="assertive" aria-atomic="true" id="alertToast">
            <div class="d-flex">
                <div class="toast-body">
                    <?= htmlspecialchars($toastMessage) ?>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"><i class="fa-solid fa-circle-xmark fa-lg"></i></button>
            </div>
        </div>
    <?php endif; ?>
    <?php if (!empty($toastMessageErro)): ?>
        <div class="toast align-items-center text-bg-success border-0 position-fixed start-50 top-50 translate-middle" role="alert" aria-live="assertive" aria-atomic="true" id="alertToast">
            <div class="d-flex">
                <div class="toast-body">
                    <?= htmlspecialchars($toastMessageErro) ?>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"><i class="fa-solid fa-circle-xmark fa-lg"></i></button>
            </div>
        </div>
    <?php endif; ?>
    <div id="toastContainer"></div>



    <div class="container-fluid">
        <div class="row">

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
        <div
          class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        </div>
            <p><strong> (Enviado todo dia 1º)</strong></p>
                <div class="table-responsive small">
                    <div class="table-responsive small">
                        <form method='post'>

                            <?php renderClientForm($clients); ?>

                        </form>
                        <br>
                        <br>
                    </div>
            </main>
        </div>
    </div>
    <script src="./assets/js/bootstrap.bundle.min.js"></script>
    <script src="./assets/js/chart.umd.js"></script>
    <script src="./assets/js/dashboard.js"></script>
    <script src='./assets/js/teste.js'></script>
</body>

</html>