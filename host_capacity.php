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

try {
    $dataAssets = fetchData('getHostProvisionedSummary');
} catch (Exception $e) {
    die("Erro ao obter dados da API: " . $e->getMessage());
}

# Normalização
function normalizeData($data)
{
    $normalized = [];

    foreach ($data as $entry) {
        $normalized[] = [
            'host_name' => $entry['host_name'] ?? 'N/A',
            'provisioned' => $entry['provisioned'] ?? 'N/A',
            'host_total_provisioned' => $entry['host_total_provisioned'] ?? 'N/A',
            'array_name' => $entry['array_name'] ?? 'N/A',
            'array_vendor' => $entry['array_vendor'] ?? 'N/A',
            'array_product' => $entry['array_product'] ?? 'N/A',
        ];
    }
    return $normalized;
}

$allData = normalizeData($dataAssets);

# Filtros
$hostNameFilter = $_GET['host_name'] ?? '';
$arrayNameFilter = $_GET['array_name'] ?? '';
$arrayProductFilter = $_GET['array_product'] ?? '';

# Aplicar filtros
$filteredData = array_filter($allData, function ($entry) use ($hostNameFilter, $arrayNameFilter, $arrayProductFilter) {
    return (!$hostNameFilter || stripos($entry['host_name'], $hostNameFilter) !== false) &&
        (!$arrayNameFilter || stripos($entry['array_name'], $arrayNameFilter) !== false) &&
        (!$arrayProductFilter || stripos($entry['array_product'], $arrayProductFilter) !== false);
});

# Tabela
function generateHtmlTable($data)
{
    $html = "<table class='table table-striped table-sm'>";
    $html .= "<thead><tr><th>Host Name</th><th>Provisionado</th><th>Total Provisionado</th><th>Storage</th><th>Fabricante</th><th>Modelo</th></tr></thead><tbody>";
    foreach ($data as $entry) {
        $html .= "<tr>
      <td>{$entry['host_name']}</td>
      <td>{$entry['provisioned']}</td>
      <td>{$entry['host_total_provisioned']}</td>
      <td>{$entry['array_name']}</td>
      <td>{$entry['array_vendor']}</td>
      <td>{$entry['array_product']}</td>
    </tr>";
    }
    $html .= "</tbody></table>";
    return $html;
}

# Enviar por email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_csv'])) {
    $recipient = $_POST['email'] ?? '';
    if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        die("Endereço de email inválido.");
    }

    $csvFile = tempnam(sys_get_temp_dir(), 'data');
    $csv = fopen($csvFile, 'w');
    fputcsv($csv, ['Host Name', 'Provisionado', 'Total Provisionado', 'Storage', 'Fabricante', 'Modelo']);
    foreach ($filteredData as $entry) {
        fputcsv($csv, [
            $entry['host_name'],
            $entry['provisioned'],
            $entry['host_total_provisioned'],
            $entry['array_name'],
            $entry['array_vendor'],
            $entry['array_product']
        ]);
    }
    fclose($csv);

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->Port = $smtpPort;
        $mail->SMTPAuth = false;
        $mail->SMTPSecure = false;

        $mail->setFrom($fromEmail, 'ARXVIEW');
        $mail->addAddress($recipient);
        $mail->Subject = $fromName;
        $mail->Body = "Segue em anexo os dados filtrados solicitados.<br>" . generateHtmlTable($filteredData);
        $mail->isHTML(true);
        $mail->addAttachment($csvFile, 'report.csv');
        $mail->send();
        echo "Email enviado com sucesso para $recipient.";
    } catch (Exception $e) {
        echo "Erro ao enviar email: {$mail->ErrorInfo}";
    }
    unlink($csvFile);
    exit;
}

# Exportar CSV direto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['download_csv'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="report.csv"');

    $csv = fopen('php://output', 'w');
    fputcsv($csv, ['Host Name', 'Provisionado', 'Total Provisionado', 'Storage', 'Fabricante', 'Modelo']);
    foreach ($filteredData as $entry) {
        fputcsv($csv, [
            $entry['host_name'],
            $entry['provisioned'],
            $entry['host_total_provisioned'],
            $entry['array_name'],
            $entry['array_vendor'],
            $entry['array_product']
        ]);
    }
    fclose($csv);
    exit;
}
# Configuração de paginação
$itemsPerPage = 50; // Número de itens por página
$totalItems = count($filteredData); // Total de itens filtrados
$totalPages = ceil($totalItems / $itemsPerPage); // Número total de páginas
$currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1; // Página atual
$currentPage = max(1, min($currentPage, $totalPages)); // Garantir que a página esteja no intervalo válido
$startIndex = ($currentPage - 1) * $itemsPerPage; // Índice inicial para a página atual

# Dados para a página atual
$paginatedData = array_slice($filteredData, $startIndex, $itemsPerPage);
function currentPagination($currentPage, $hostNameFilter, $arrayNameFilter, $arrayProductFilter, $totalPages)
{
    $maxVisible = 7;
    $startPage = max(1, $currentPage - 3);
    $endPage = min($totalPages, $currentPage + 3);

    if ($currentPage > 1) {
        $prevPage = $currentPage - 1;
        echo "<li class='page-item'><a class='page-link' href='?host_name=$hostNameFilter&array_name=$arrayNameFilter&array_product=$arrayProductFilter&page=$prevPage'>Previous</a></li>";
    }

    if ($startPage > 1) {
        echo "<li class='page-item'><a class='page-link' href='?host_name=$hostNameFilter&array_name=$arrayNameFilter&array_product=$arrayProductFilter&page=1'>1</a></li>";
        if ($startPage > 2) {
            echo "<li class='page-item disabled'><a class='page-link' href='#'>...</a></li>";
        }
    }

    for ($i = $startPage; $i <= $endPage; $i++) {
        $active = $i == $currentPage ? "active" : "";
        echo "<li class='page-item $active'><a class='page-link' href='?host_name=$hostNameFilter&array_name=$arrayNameFilter&array_product=$arrayProductFilter&page=$i'>$i</a></li>";
    }

    if ($endPage < $totalPages) {
        if ($endPage < $totalPages - 1) {
            echo "<li class='page-item disabled'><a class='page-link' href='#'>...</a></li>";
        }
        echo "<li class='page-item'><a class='page-link' href='?host_name=$hostNameFilter&array_name=$arrayNameFilter&array_product=$arrayProductFilter&page=$totalPages'>$totalPages</a></li>";
    }

    if ($currentPage < $totalPages) {
        $nextPage = $currentPage + 1;
        echo "<li class='page-item'><a class='page-link' href='?host_name=$hostNameFilter&array_name=$arrayNameFilter&array_product=$arrayProductFilter&page=$nextPage'>Next</a></li>";
    }
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
    <title>Firmware Report - ARXVIEW</title>

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

    <div class="container-fluid">
        <div class="row">

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div
                    class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                </div>
                <form method='get'>
                    <div
                        class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-0 mb-0 ">
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <div class="btn-group me-2">
                                <div class="input-group">
                                    <input type="text" name="host_name" value="<?php echo $hostNameFilter; ?>" class="form-control" placeholder="Filter by Host Name"
                                        aria-label="Filter by Host Name">
                                    <input type="text" name="array_name" value="<?php echo $arrayNameFilter; ?>" class="form-control" placeholder="Filter by Storage System"
                                        aria-label="Filter by Storage System">
                                    <input type="text" name="array_product" value="<?php echo $arrayProductFilter; ?>" class="form-control" placeholder="Filter by Model"
                                        aria-label="Filter by Model">

                                    <button class="btn btn-outline-secondary" type='submit'>Filter</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <form method="post">
                    <div
                        class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-3 border-bottom">
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <div class="btn-group me-2">
                                <div class="input-group">
                                    <input type="email" name="email" class="form-control" placeholder="E-mail to send"
                                        aria-label="E-mail to send">
                                    <input type="hidden" name="host_name" value="<?php echo $hostNameFilter; ?>">
                                    <input type="hidden" name="array_name" value="<?php echo $arrayNameFilter; ?>">
                                    <input type="hidden" name="array_product" value="<?php echo $arrayProductFilter; ?>">
                                    <button class="btn btn-outline-secondary" type="submit" name="export_csv">Send
                                        Email</button>
                                    <button class="btn btn-outline-secondary" type="submit" name="download_csv">Export
                                        CSV</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="table-responsive small">

                    <?php

                    # Paginação
                    echo "<nav>
            <ul class='pagination navigation example justify-content-center'>";
                    echo currentPagination($currentPage, $hostNameFilter, $arrayNameFilter, $arrayProductFilter, $totalPages);
                    echo "</ul>
          </nav>";

                    # Renderizar tabela
                    echo generateHtmlTable($paginatedData);

                    # Paginação
                    echo "<nav>
            <ul class='pagination navigation example justify-content-center'>";
                    echo currentPagination($currentPage, $hostNameFilter, $arrayNameFilter, $arrayProductFilter, $totalPages);
                    echo "</ul>
          </nav>";

                    ?>

                </div>
            </main>
        </div>
    </div>
    <script src="./assets/js/bootstrap.bundle.min.js"></script>
    <script src="./assets/js/chart.umd.js"></script>
    <script src="./assets/js/dashboard.js"></script>
</body>

</html>