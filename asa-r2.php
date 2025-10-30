<?php

function bytesToTB($bytes) {
    return round($bytes / 1099511627776, 2);
}

function getStorageJson($ip, $uuid) {
    $url = "https://$ip/api/storage/availability-zones/$uuid";
    $username = 'admin';
    $password = 'K`|E@dl.3Bql.VW';

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_HTTPGET, true);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        return ['error' => curl_error($ch), 'ip' => $ip];
    }

    curl_close($ch);

    return json_decode($response, true);
}

// ✅ Lista de IPs + UUIDs dos availability zones
$equipamentosEndpoints = [
    ['ip' => '10.251.40.141', 'uuid' => '7e84059a-424c-11f0-8921-d039eaca4bac'],
    // ['ip' => '10.252.40.141', 'uuid' => '5fa6899c-72bc-11f0-923c-d039eaca4cb4'],
    // ...
];

$equipamentos = [];

foreach ($equipamentosEndpoints as $info) {
    $json = getStorageJson($info['ip'], $info['uuid']);
    $equipamentos[] = $json;
}

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Status dos Storages</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f4f8;
            margin: 0;
            padding: 2rem;
            color: #333;
        }
        h1 {
            text-align: center;
            margin-bottom: 2rem;
        }
        .equipamento {
            background: white;
            border-radius: 12px;
            box-shadow: 0 0 12px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            margin-bottom: 2rem;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        h2 {
            font-size: 1.1rem;
            margin-bottom: 1rem;
            color: #222;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }
        th, td {
            padding: 0.6rem 1rem;
            text-align: left;
        }
        th:nth-child(even) {
            background-color: #f0f0f5;
        }
        tr:nth-child(even) {
            background-color: #f0f0f5;
        }
        .error {
            background-color: #ffe5e5;
            color: #b10000;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            max-width: 800px;
            margin: 0 auto 2rem auto;
        }
    </style>
</head>
<body>
    <h1>Status das Pools dos ASA R2</h1>

    <?php foreach ($equipamentos as $equipamento): ?>
        <?php if (isset($equipamento['error'])): ?>
            <div class="error">
                Erro ao consultar <?= htmlspecialchars($equipamento['ip']) ?>: <?= htmlspecialchars($equipamento['error']) ?>
            </div>
            <?php continue; ?>
        <?php endif; ?>

        <?php
            $space = $equipamento['space'];
            $nodeNames = array_column($equipamento['nodes'], 'name');

            $dados = [
                'Total Size (TiB)' => bytesToTB($space['size']),
                'Available (TiB)' => bytesToTB($space['available']),
                'Physical Used (TiB)' => bytesToTB($space['physical_used']),
                'Physical Used (%)' => $space['physical_used_percent'] . '%',
                'Efficiency Savings (TiB)' => bytesToTB($space['efficiency_without_snapshots']['savings']),
                'Efficiency Ratio' => $space['efficiency_without_snapshots']['ratio'],
            ];
        ?>
        <div class="equipamento">
            <h2>Equipamento: <?= htmlspecialchars(implode(' / ', $nodeNames)) ?></h2>
            <table>
                <tbody>
                    <?php foreach ($dados as $label => $value): ?>
                        <tr>
                            <th><?= htmlspecialchars($label) ?></th>
                            <td><?= htmlspecialchars($value) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>
</body>
</html>
