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
  $dataV7000 = fetchData('getIBMV7000MdiskGroups');
  $dataFlashSystem = fetchData('getIBMFlashSystemMdiskGroups');
  $dataSVC = fetchData('getIBMSVCMdiskGroups');
  $dataXIV = fetchData('getIBMXIVPools');
  $dataDS8000 = fetchData('getIBMDS8000Extpools');
  $dataNetapp = fetchData('getNetAppCAggregates');
} catch (Exception $e) {
  die("Erro ao obter dados da API: " . $e->getMessage());
}


# Função para normalizar os dados
function normalizeData($data, $source)
{
  $normalized = [];

  $formatCapacity = function ($value) {
    return number_format((float) $value, 2, ',', '.'); // Formata com duas casas decimais
  };

  foreach ($data as $entry) {
    if ($source === 'XIV') {
      $normalized[] = [
        'array_name' => $entry['array_name'] ?? 'N/A',
        'mdiskgrp_name' => $entry['pool_name'] ?? 'N/A',
        'capacity' => $formatCapacity($entry['soft_size'] ?? 0),
        'provisioned_capacity' => $formatCapacity($entry['used_by_volumes'] ?? 0),
        'free_capacity' => $formatCapacity($entry['empty_space_soft'] ?? 0),
        'equipment_type' => 'A9000 / XIV',
      ];
    } elseif ($source === 'DS8000') {
      $normalized[] = [
        'array_name' => $entry['array_name'] ?? 'N/A',
        'mdiskgrp_name' => $entry['extpool_name'] ?? 'N/A',
        'capacity' => $formatCapacity($entry['total_storage'] ?? 0),
        'provisioned_capacity' => $formatCapacity($entry['reserved_storage'] ?? 0),
        'free_capacity' => $formatCapacity($entry['available_storage'] ?? 0),
        'equipment_type' => 'DS8000',
      ];
    } elseif ($source === 'SVC') {
      $normalized[] = [
        'array_name' => $entry['array_name'] ?? 'N/A',
        'mdiskgrp_name' => $entry['mdisk_group_name'] ?? 'N/A',
        'capacity' => $formatCapacity($entry['capacity'] ?? 0),
        'provisioned_capacity' => $formatCapacity($entry['real_capacity'] ?? 0),
        'free_capacity' => $formatCapacity($entry['free_capacity'] ?? 0),
        'equipment_type' => 'SVC',
      ];
    } elseif ($source === 'FlashSystem') {
      $normalized[] = [
        'array_name' => $entry['array_name'] ?? 'N/A',
        'mdiskgrp_name' => $entry['mdiskgrp_name'] ?? 'N/A',
        'capacity' => $formatCapacity($entry['capacity'] ?? 0),
        'provisioned_capacity' => $formatCapacity($entry['used_capacity'] ?? 0),
        'free_capacity' => $formatCapacity($entry['physical_free_capacity'] ?? 0),
        'equipment_type' => 'FlashSystem',
      ];
    } elseif ($source === 'Netapp') {
      $normalized[] = [
        'array_name' => $entry['array_name'] ?? 'N/A',
        'mdiskgrp_name' => $entry['aggregate_name'] ?? 'N/A',
        'capacity' => $formatCapacity($entry['size'] ?? 0),
        'provisioned_capacity' => $formatCapacity($entry['allocated'] ?? 0),
        'free_capacity' => $formatCapacity($entry['available'] ?? 0),
        'equipment_type' => 'Netapp',
      ];
    } else {
      $normalized[] = [
        'array_name' => $entry['array_name'] ?? 'N/A',
        'mdiskgrp_name' => $entry['mdiskgrp_name'] ?? 'N/A',
        'capacity' => $formatCapacity($entry['capacity'] ?? 0),
        'provisioned_capacity' => $formatCapacity($entry['provisioned_capacity'] ?? 0),
        'free_capacity' => $formatCapacity($entry['free_capacity'] ?? 0),
        'equipment_type' => $source,
      ];
    }
  }
  return $normalized;
}

# Normalizar os dados
$normalizedV7000 = normalizeData($dataV7000, 'V7000');
$normalizedFlashSystem = normalizeData($dataFlashSystem, 'FlashSystem');
$normalizedSVC = normalizeData($dataSVC, 'SVC');
$normalizedXIV = normalizeData($dataXIV, 'XIV');
$normalizedDS8000 = normalizeData($dataDS8000, 'DS8000');
$normalizedNetapp = normalizeData($dataNetapp, 'Netapp');

# Combinar os dados
$allData = array_merge(
  $normalizedV7000,
  $normalizedFlashSystem,
  $normalizedSVC,
  $normalizedXIV,
  $normalizedDS8000,
  $normalizedNetapp
);

# Filtros (agora com suporte a múltiplos valores separados por ;)
$clientFilter = array_filter(array_map('trim', explode(';', $_GET['client'] ?? '')));
$equipmentFilter = array_filter(array_map('trim', explode(';', $_GET['equipment'] ?? '')));
$mdiskgrpFilter = array_filter(array_map('trim', explode(';', $_GET['mdiskgrp_name'] ?? '')));

# Aplicar filtros
$filteredData = array_filter($allData, function ($entry) use ($clientFilter, $equipmentFilter, $mdiskgrpFilter) {
  $matchClient = empty($clientFilter) || in_array(true, array_map(fn($f) => stripos($entry['array_name'], $f) !== false, $clientFilter));
  $matchEquipment = empty($equipmentFilter) || in_array(true, array_map(fn($f) => stripos($entry['equipment_type'], $f) !== false, $equipmentFilter));
  $matchMdiskgrp = empty($mdiskgrpFilter) || in_array(true, array_map(fn($f) => stripos($entry['mdiskgrp_name'], $f) !== false, $mdiskgrpFilter));
  return $matchClient && $matchEquipment && $matchMdiskgrp;
});

# Gerar a tabela
function generateHtmlTable($data)
{
  $html = "<table class='table table-striped table-sm'>";
  $html .= "<thead ><tr><th>Storage System</th><th>Pool</th><th>Capacity (GB)</th><th>Provisioned Capacity (GB)</th><th>Free Capacity (GB)</th><th>Equipment Type</th></tr></thead>";
  $html .= "<tbody>";
  foreach ($data as $entry) {
    $html .= "<tr><td>{$entry['array_name']}</td><td>{$entry['mdiskgrp_name']}</td><td>{$entry['capacity']}</td><td>{$entry['provisioned_capacity']}</td><td>{$entry['free_capacity']}</td><td>{$entry['equipment_type']}</td></tr>";
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
  fputcsv($csv, ['Storage System', 'Equipment Type', 'Pool', 'Capacity (GB)', 'Provisioned Capacity (GB)', 'Free Capacity (GB)']);
  foreach ($filteredData as $entry) {
    fputcsv($csv, [
      $entry['array_name'],
      $entry['equipment_type'],
      $entry['mdiskgrp_name'],
      $entry['capacity'],
      $entry['provisioned_capacity'],
      $entry['free_capacity']
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
  fputcsv($csv, ['Storage System', 'Equipment Type', 'Pool', 'Capacity (GB)', 'Provisioned Capacity (GB)', 'Free Capacity (GB)']);
  foreach ($filteredData as $entry) {
    fputcsv($csv, [
      $entry['array_name'],
      $entry['equipment_type'],
      $entry['mdiskgrp_name'],
      $entry['capacity'],
      $entry['provisioned_capacity'],
      $entry['free_capacity']
    ]);
  }
  fclose($csv);
  exit; // Finaliza o script após a geração do CSV
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
  <title>Capacity Report - ARXVIEW</title>

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
                  <input type="text" name="client" value="<?php echo implode(';', $clientFilter); ?>"
                    class="form-control" placeholder="Filter by Storage System" aria-label="Filter by Storage System">
                  <input type="text" name="equipment" value="<?php echo implode(';', $equipmentFilter); ?>"
                    class="form-control" placeholder="Filter by Equipment" aria-label="Filter by Equipment">
                  <input type="text" name="mdiskgrp_name" value="<?php echo implode(';', $mdiskgrpFilter); ?>"
                    class="form-control" placeholder="Filter by Pool" aria-label="Filter by Pool">

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
                  <input type="hidden" name="client" value="<?php echo implode(';', $clientFilter); ?>">
                  <input type="hidden" name="equipment" value="<?php echo implode(';', $equipmentFilter); ?>">
                  <input type="hidden" name="mdiskgrp_name" value="<?php echo implode(';', $mdiskgrpFilter); ?>">
                  <button class="btn btn-outline-secondary" type="submit" name="export_csv">Send Email</button>
                  <button class="btn btn-outline-secondary" type="submit" name="download_csv">Export CSV</button>
                </div>
              </div>
            </div>
          </div>
        </form>

        <div class="table-responsive small">

          <?php echo generateHtmlTable($filteredData);
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