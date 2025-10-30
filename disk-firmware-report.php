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

# Buscar dados de todos os equipamentos utilizando a função fetchData
$dataV7000 = fetchData('getIBMV7000Disks');
$dataFlashSystem = fetchData('getIBMFlashSystemDisks');
$dataSVC = fetchData('getIBMSVCVdisks');
$dataDS8000 = fetchData('getIBMDS8000DDMs');
$dataNetapp = fetchData('getNetAppCDisks');
} catch (Exception $e) {
  die("Erro ao obter dados da API: " . $e->getMessage());
}


# Função para normalizar os dados
function normalizeData($data, $source)
{
  $normalized = [];

  $formatCapacity = function ($value) {
    return number_format((float)$value, 2, ',', '.'); // Formata com duas casas decimais
  };

  foreach ($data as $entry) {
    if ($source === 'DS8000') {
      $normalized[] = [
      'array_name' => $entry['array_name'] ?? 'N/A',
        'manufacturer' => $entry['model_number'] ?? 'N/A',
        'fru_identity' => $entry['serial_number'] ?? 'N/A',
        'firmware_level' => $entry['firmwarelevel'] ?? 'N/A',
        'capacity' => $formatCapacity($entry['capacity'] ?? 0),
        'manufacturer' => $entry['manufacturer'] ?? 'N/A',
          'equipment_type' => 'DS8000',
        ];
    } elseif ($source === 'FlashSystem') {
      $normalized[] = [
        'array_name' => $entry['array_name'] ?? 'N/A',
        'manufacturer' => $entry['manufacturer'] ?? 'N/A',
        'fru_identity' => $entry['fru_identity'] ?? 'N/A',
        'firmware_level' => $entry['firmware_level'] ?? 'N/A',
        'capacity' => $formatCapacity($entry['capacity'] ?? 0),
        'manufacturer' => $entry['manufacturer'] ?? 'N/A',
        'equipment_type' => 'FlashSystem',
      ];
    } elseif ($source === 'SVC') {
      $normalized[] = [
        'array_name' => $entry['array_name'] ?? 'N/A',
        'manufacturer' => $entry['manufacturer'] ?? 'N/A',
        'fru_identity' => $entry['fru_identity'] ?? 'N/A',
        'firmware_level' => $entry['firmware_level'] ?? 'N/A',
        'capacity' => $formatCapacity($entry['capacity'] ?? 0),
        'manufacturer' => $entry['manufacturer'] ?? 'N/A',
        'equipment_type' => 'SVC',
      ];
    } elseif ($source === 'Netapp') {
      $normalized[] = [
        'array_name' => $entry['array_name'] ?? 'N/A',
        'manufacturer' => $entry['model'] ?? 'N/A',
        'fru_identity' => $entry['serial_number'] ?? 'N/A',
        'firmware_level' => $entry['firmware_revision'] ?? 'N/A',
        'capacity' => $formatCapacity($entry['capacity'] ?? 0),
        'manufacturer' => $entry['manufacturer'] ?? 'N/A',
        'equipment_type' => 'Netapp',
      ];
    } else {
      $normalized[] = [
        'array_name' => $entry['array_name'] ?? 'N/A',
        'manufacturer' => $entry['manufacturer'] ?? 'N/A',
        'fru_identity' => $entry['fru_identity'] ?? 'N/A',
        'firmware_level' => $entry['firmware_level'] ?? 'N/A',
        'capacity' => $formatCapacity($entry['capacity'] ?? 0),
        'manufacturer' => $entry['manufacturer'] ?? 'N/A',
        'equipment_type' => $source,
      ];
    }
  }
  return $normalized;
}

$normalizedV7000 = normalizeData($dataV7000, 'V7000');
$normalizedFlashSystem = normalizeData($dataFlashSystem, 'FlashSystem');
$normalizedSVC = normalizeData($dataSVC, 'SVC');
$normalizedDS8000 = normalizeData($dataDS8000, 'DS8000');
$normalizedNetapp = normalizeData($dataNetapp, 'Netapp');

$allData = array_merge(
  $normalizedV7000,
  $normalizedFlashSystem,
  $normalizedSVC,
  $normalizedDS8000,
  $normalizedNetapp
);

$arrayNameFilter = $_GET['array_name'] ?? '';
$firmwareFilter = $_GET['firmware_level'] ?? '';
$modelFilter = $_GET['manufacturer'] ?? '';


# Aplicar filtros
$filteredData = array_filter($allData, function ($entry) use ($arrayNameFilter, $firmwareFilter, $modelFilter) {
  return (!$arrayNameFilter || stripos($entry['array_name'], $arrayNameFilter) !== false) &&
         (!$firmwareFilter || stripos($entry['firmware_level'], $firmwareFilter) !== false) &&
         (!$modelFilter || stripos($entry['manufacturer'], $modelFilter) !== false);
});

# Gerar a tabela
function generateHtmlTable($data) {
  $html = "<table class='table table-striped table-sm'>";
  $html .= "<thead><tr>
  <th>Storage</th>
  <th>Model</th>
  <th>FRU / Serial Number</th>
  <th>Firmware</th>
  <th>Capacity</th>
  <th>Manufacturer</th>
  <th>Type</th>
  </tr></thead><tbody>";
  foreach ($data as $entry) {
    $html .= "<tr><td>{$entry['array_name']}</td>
    <td>{$entry['manufacturer']}</td>
    <td>{$entry['fru_identity']}</td>
    <td>{$entry['firmware_level']}</td>
    <td>{$entry['capacity']}</td>
    <td>{$entry['manufacturer']}</td>
    <td>{$entry['equipment_type']}</td></tr>";
  }
  $html .= "</tbody></table>";
  return $html;
}

# Processar envio do email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_csv'])) {
  $recipient = $_POST['email'] ?? '';
  if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
    die("Endereço de email inválido.");
  }

  $csvFile = tempnam(sys_get_temp_dir(), 'data');
  $csv = fopen($csvFile, 'w');
  fputcsv($csv, ['Storage', 'Model', 'FRU / Serial Number', 'Firmware', 'Capacity', 'Manufacturer']);
  foreach ($filteredData as $entry) {
    fputcsv($csv, [
      $entry['array_name'],
      $entry['manufacturer'],
      $entry['fru_identity'],
      $entry['firmware_level'],
      $entry['capacity'],
      $entry['manufacturer'],
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

# Processar geração de CSV sem envio de e-mail
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['download_csv'])) {
  $csvFileName = 'report.csv'; // Nome do arquivo CSV gerado para download
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment;filename="' . $csvFileName . '"');

  $csv = fopen('php://output', 'w'); // Cria o CSV diretamente na saída
  fputcsv($csv, ['Storage', 'Model', 'FRU / Serial Number', 'Firmware', 'Capacity', 'Manufacturer']);
  foreach ($filteredData as $entry) {
    fputcsv($csv, [
      $entry['array_name'],
      $entry['manufacturer'],
      $entry['fru_identity'],
      $entry['firmware_level'],
      $entry['capacity'],
      $entry['manufacturer'],
    ]);
  }
  fclose($csv);
  exit; // Finaliza o script após a geração do CSV
}

# Configuração de paginação
$itemsPerPage = 50; // Número de itens por página
$totalItems = count($filteredData); // Total de itens filtrados
$totalPages = ceil($totalItems / $itemsPerPage); // Número total de páginas
$currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1; // Página atual
$currentPage = max(1, min($currentPage, $totalPages)); // Garantir que a página esteja no intervalo válido
$startIndex = ($currentPage - 1) * $itemsPerPage; // Índice inicial para a página atual

# Dados para a página atual
$paginatedData = array_slice($filteredData, $startIndex, $itemsPerPage);
function currentPagination($currentPage, $arrayNameFilter, $firmwareFilter, $modelFilter, $totalPages)
{
  if ($currentPage > 1) {
    $prevPage = $currentPage - 1;
    echo "<li class='page-item'><a class='page-link' href='?array_name=$arrayNameFilter&firmware_level=$firmwareFilter&manufacturer=$modelFilter&page=$prevPage'>Previous</a></li>";
  }

  $maxVisible = 7;
  $startPage = max(1, $currentPage - 3);
  $endPage = min($totalPages, $currentPage + 3);

  if ($startPage > 1) {
    echo "<li class='page-item'><a class='page-link' href='?array_name=$arrayNameFilter&firmware_level=$firmwareFilter&manufacturer=$modelFilter&page=1'>1</a></li>";
    if ($startPage > 2) {
      echo "<li class='page-item disabled'><a class='page-link' href='#'>...</a></li>";
    }
  }

  for ($i = $startPage; $i <= $endPage; $i++) {
    $active = $i == $currentPage ? "active" : "";
    echo "<li class='page-item $active'><a class='page-link' href='?array_name=$arrayNameFilter&firmware_level=$firmwareFilter&manufacturer=$modelFilter&page=$i'>$i</a></li>";
  }

  if ($endPage < $totalPages) {
    if ($endPage < $totalPages - 1) {
      echo "<li class='page-item disabled'><a class='page-link' href='#'>...</a></li>";
    }
    echo "<li class='page-item'><a class='page-link' href='?array_name=$arrayNameFilter&firmware_level=$firmwareFilter&manufacturer=$modelFilter&page=$totalPages'>$totalPages</a></li>";
  }

  if ($currentPage < $totalPages) {
    $nextPage = $currentPage + 1;
    echo "<li class='page-item'><a class='page-link' href='?array_name=$arrayNameFilter&firmware_level=$firmwareFilter&manufacturer=$modelFilter&page=$nextPage'>Next</a></li>";
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
  <title>Disk Firmware Report - ARXVIEW</title>

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
          <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-0 mb-0 ">
            <div class="btn-toolbar mb-2 mb-md-0">
              <div class="btn-group me-2">
                <div class="input-group">
                <input type="text" name="array_name" value="<?php echo $arrayNameFilter; ?>" class="form-control" placeholder="Filter by Storage System" aria-label="Filter by Storage System">
                <input type="text" name="firmware_level" value="<?php echo $firmwareFilter; ?>" class="form-control" placeholder="Filter by Firmware" aria-label="Filter by Firmware">
                <input type="text" name="manufacturer" value="<?php echo $modelFilter; ?>" class="form-control" placeholder="Filter by Model" aria-label="Filter by Model">

                  <button class="btn btn-outline-secondary" type='submit'>Filter</button>
                </div>
              </div>
            </div>
          </div>
        </form>
        <form method="post">
          <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-3 border-bottom">
            <div class="btn-toolbar mb-2 mb-md-0">
              <div class="btn-group me-2">
                <div class="input-group">
                  <input type="email" name="email" class="form-control" placeholder="E-mail to send" aria-label="E-mail to send">
                  <input type="hidden" name="array_name" value="<?php echo $arrayNameFilter; ?>">
                  <input type="hidden" name="firmware_level" value="<?php echo $firmwareFilter; ?>">
                  <input type="hidden" name="manufacturer" value="<?php echo $modelFilter; ?>">
                  <button class="btn btn-outline-secondary" type="submit" name="export_csv">Send Email</button>
                  <button class="btn btn-outline-secondary" type="submit" name="download_csv">Export CSV</button>
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
          echo currentPagination($currentPage, $arrayNameFilter, $firmwareFilter, $modelFilter, $totalPages);
          echo "</ul>
          </nav>";

          # Renderizar tabela
          echo generateHtmlTable($paginatedData);

          # Paginação
          echo "<nav>
            <ul class='pagination navigation example justify-content-center'>";
          echo currentPagination($currentPage, $arrayNameFilter, $firmwareFilter, $modelFilter, $totalPages);
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