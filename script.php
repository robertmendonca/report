<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
require 'conn.php';

// Detectar execução via CLI
$isCli = php_sapi_name() === 'cli';
if (!$isCli) {
    header('Content-Type: application/json');
}

function processAndSendReport($data, $ch, $prot, $arxhost, $port, $format, $smtpHost, $smtpPort, $fromEmail, $fromName)
{
    $subject = $data['subject'] ?? 'Relatrio ARXVIEW';
    $name = $data['name'] ?? '';
    $emails = $data['emails'] ?? $data['email'] ?? '';
    $includeCapacity = $data['default_checkbox'] === '1';
    $includeVolume = $data['checked_checkbox'] === '1';

    if (!$includeCapacity && !$includeVolume) {
        return ['success' => false, 'message' => 'Nenhum relatorio selecionado.'];
    }

    [$capacityData, $volumeData] = fetchAndNormalizeData($ch, "$prot://$arxhost:$port/api", $format);

    $arrayNames = array_map('trim', explode(';', $name));
    $volumeNames = array_map('trim', explode(';', $data['volume_name'] ?? ''));

    $filteredCapacity = $includeCapacity ? array_filter($capacityData, function ($entry) use ($arrayNames) {
        foreach ($arrayNames as $arrayName) {
            if (stripos($entry['array_name'], $arrayName) !== false)
                return true;
        }
        return false;
    }) : [];

    $filteredVolume = $includeVolume ? array_filter($volumeData, function ($entry) use ($arrayNames, $volumeNames) {
        $matchArray = false;
        foreach ($arrayNames as $arrayName) {
            if (stripos($entry['array_name'], $arrayName) !== false) {
                $matchArray = true;
                break;
            }
        }

        $matchVolume = true;
        if (!empty($volumeNames)) {
            $matchVolume = false;
            foreach ($volumeNames as $volumeName) {
                if (stripos($entry['vdisk_name'] ?? '', $volumeName) !== false) {
                    $matchVolume = true;
                    break;
                }
            }
        }

        return $matchArray && $matchVolume;
    }) : [];

    if (empty($filteredCapacity) && empty($filteredVolume)) {
        return ['success' => false, 'message' => "Sem dados para \"$name\"."];
    }

    $attachments = [];
    if (!empty($filteredCapacity)) {
        $file = tempnam(sys_get_temp_dir(), 'cap_');
        createCsv($filteredCapacity, ['Storage System', 'Pool', 'Capacity (GB)', 'Provisioned Capacity (GB)', 'Free Capacity (GB)', 'Equipment Type'], $file);
        $attachments[$file] = $subject . '_Capacity.csv';
    }
    if (!empty($filteredVolume)) {
        $file = tempnam(sys_get_temp_dir(), 'vol_');
        createCsv($filteredVolume, ['Storage System', 'Volume', 'Pool', 'Capacity (GB)', 'WWN', 'Equipment Type'], $file);
        $attachments[$file] = $subject . '_Volume.csv';
    }

    try {
        sendEmail($emails, $subject, "<h1>$subject</h1><p>Relatorios em anexo.</p>", $attachments, $smtpHost, $smtpPort, $fromEmail, $fromName);
        foreach ($attachments as $f => $_)
            unlink($f);
        return ['success' => true, 'message' => "Relatorio enviado para $emails."];
    } catch (Exception $e) {
        return ['success' => false, 'message' => "Erro: {$e->getMessage()}"];
    }
}


function fetchAndNormalizeData($ch, $apipath, $format)
{
    # Dados de Capacidade
    $capacityData = [
        ...normalizeCapacityData(fetchData($ch, 'getIBMV7000MdiskGroups', $format, $apipath), 'V7000'),
        ...normalizeCapacityData(fetchData($ch, 'getIBMFlashSystemMdiskGroups', $format, $apipath), 'FlashSystem'),
        ...normalizeCapacityData(fetchData($ch, 'getIBMSVCMdiskGroups', $format, $apipath), 'SVC'),
        ...normalizeCapacityData(fetchData($ch, 'getIBMXIVPools', $format, $apipath), 'XIV'),
        ...normalizeCapacityData(fetchData($ch, 'getIBMDS8000Extpools', $format, $apipath), 'DS8000'),
        ...normalizeCapacityData(fetchData($ch, 'getNetAppAggregates', $format, $apipath), 'Netapp'),
    ];

    # Dados de Volumes
    $volumeData = [
        ...normalizeVolumeData(fetchData($ch, 'getIBMV7000Vdisks', $format, $apipath), 'V7000'),
        ...normalizeVolumeData(fetchData($ch, 'getIBMFlashSystemVdisks', $format, $apipath), 'FlashSystem'),
        ...normalizeVolumeData(fetchData($ch, 'getIBMSVCVdisks', $format, $apipath), 'SVC'),
        ...normalizeVolumeData(fetchData($ch, 'getIBMXIVVolumes', $format, $apipath), 'XIV'),
        ...normalizeVolumeData(fetchData($ch, 'getIBMDS8000Volumes', $format, $apipath), 'DS8000'),
        ...normalizeVolumeData(fetchData($ch, 'getNetAppCLUNs', $format, $apipath), 'Netapp'),
    ];

    return [$capacityData, $volumeData];
}

function normalizeCapacityData($data, $source)
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

function normalizeVolumeData($data, $source)
{
    $normalized = [];

    $formatCapacity = function ($value) {
        return number_format((float) $value, 2, ',', '.'); // Formata com duas casas decimais
    };

    foreach ($data as $entry) {
        if ($source === 'XIV') {
            $normalized[] = [
                'array_name' => $entry['array_name'] ?? 'N/A',
                'vdisk_name' => $entry['volume_name'] ?? 'N/A',
                'pool' => $entry['pool_name'] ?? 'N/A',
                'capacity' => $formatCapacity($entry['capacity'] ?? 0),
                'wwn' => $entry['wwn'] ?? 'N/A',
                'equipment_type' => 'A9000 / XIV',
            ];
        } elseif ($source === 'DS8000') {
            // Verifica se o volume não é do tipo 'CKD Alias'
            if (!isset($entry['ckd_volume_type']) || $entry['ckd_volume_type'] !== 'CKD Alias') {
                $normalized[] = [
                    'array_name' => $entry['array_name'] ?? 'N/A',
                    'vdisk_name' => (!empty($entry['volume_name']) && $entry['volume_name'] !== '-')
                        ? $entry['volume_name']
                        : ($entry['ckd_volume_serial'] ?? 'N/A'),
                    'volume_id' => $entry['volume_id'] ?? 'N/A',
                    'pool' => $entry['resource_group'] ?? 'N/A',
                    'capacity' => $formatCapacity($entry['capacity'] ?? 0),
                    'wwn' => $entry['wwn'] ?? 'N/A',
                    'volume_type' => $entry['volume_type'] ?? 'N/A',
                    'equipment_type' => 'DS8000',
                ];
            }
        } elseif ($source === 'Netapp') {
            $normalized[] = [
                'array_name' => $entry['array_name'] ?? 'N/A',
                'vdisk_name' => $entry['volume_name'] ?? 'N/A',
                'pool' => $entry['aggregate_name'] ?? 'N/A',
                'capacity' => $formatCapacity($entry['size'] ?? 0),
                'wwn' => $entry['wwn'] ?? 'N/A',
                'equipment_type' => 'Netapp',
            ];
        } else {
            $normalized[] = [
                'array_name' => $entry['array_name'] ?? 'N/A',
                'vdisk_name' => $entry['vdisk_name'] ?? 'N/A',
                'pool' => $entry['primary_mdiskgrp_name'] ?? 'N/A',
                'capacity' => $formatCapacity($entry['capacity'] ?? 0),
                'wwn' => $entry['wwn'] ?? 'N/A',
                'equipment_type' => $source,
            ];
        }
    }
    return $normalized;
}

function sendEmail($to, $subject, $html, $attachments, $host, $port, $fromEmail, $fromName)
{
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $host;
    $mail->Port = $port;
    $mail->SMTPAuth = false;
    $mail->SMTPSecure = false;
    $mail->setFrom($fromEmail, $fromName);

    foreach (explode(';', $to) as $addr) {
        if (filter_var(trim($addr), FILTER_VALIDATE_EMAIL)) {
            $mail->addAddress(trim($addr));
        }
    }

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $html;

    foreach ($attachments as $path => $name) {
        $mail->addAttachment($path, $name);
    }

    $mail->send();
}

function createCsv($data, $headers, $filePath)
{
    $f = fopen($filePath, 'w');
    fputcsv($f, $headers);
    foreach ($data as $row) {
        fputcsv($f, $row);
    }
    fclose($f);
}

function loadClients($filePath)
{
    $clients = [];
    if (file_exists($filePath)) {
        $f = fopen($filePath, 'r');
        while (($line = fgetcsv($f)) !== false) {
            $clients[] = [
                'subject' => $line[0] ?? '',
                'name' => $line[1] ?? '',
                'volume_name' => $line[2] ?? '',
                'emails' => $line[3] ?? '',
                'default_checkbox' => $line[4] ?? '0',
                'checked_checkbox' => $line[5] ?? '0',
            ];
        }
        fclose($f);
    }
    return $clients;
}

# Execução
if ($isCli) {
    echo "Modo CLI detectado. Executando relatorio...\n";

    [$capacityData, $volumeData] = fetchAndNormalizeData($ch, "$prot://$arxhost:$port/api", $format);

    $clients = loadClients(__DIR__ . '/clients.csv');


    if (empty($clients)) {
        echo "[ERRO] Nenhum cliente carregado do arquivo clients.csv\n";
    } else {
        echo "[INFO] " . count($clients) . " clientes carregados.\n";
    }

    foreach ($clients as $index => $client) {
        echo "[DEBUG] Processando cliente #$index - Subject: {$client['subject']}\n";
        $result = processAndSendReport($client, $ch, $prot, $arxhost, $port, $format, $smtpHost, $smtpPort, $fromEmail, $fromName);
        echo "[{$client['subject']}] {$result['message']}\n";
    }

    curl_setopt($ch, CURLOPT_URL, "$prot://$arxhost:$port/api/logout.php");
    curl_exec($ch);
    curl_close($ch);

    exit;
}



// Browser/fetch
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['emails'])) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
    exit;
}

$result = processAndSendReport($data, $ch, $prot, $arxhost, $port, $format, $smtpHost, $smtpPort, $fromEmail, $fromName);
echo json_encode($result);
exit;
