<?php
session_start();

// Verifica se o cookie de autenticação está definido
if (!isset($_SESSION['auth_cookie'])) {
  $_SESSION['error_message'] = 'Você precisa fazer login para acessar esta página.';
  header('Location: login.php');
  exit;
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
  <meta name="author" content="Robert Mendonça" />
  <meta name="generator" content="Robert Mendonça" />
  <title>ARXVIEW Custom Reports - Dashboard</title>

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
        <!-- sidebar.php -->
        <div class="card">
          <div class="card-body">
            <ul class="nav flex-column text-center">
              <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-2 <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : 'text-body' ?>"
                  href="/reports/">
                  <i class="fa-solid fa-house"></i>
                  Home
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-2 <?= basename($_SERVER['PHP_SELF']) === 'capacity-report.php' ? 'active' : 'text-body' ?>"
                  href="capacity-report.php">
                  <i class="fa-regular fa-file-lines"></i>
                  Capacity Reports
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-2 <?= basename($_SERVER['PHP_SELF']) === 'volume-report.php' ? 'active' : 'text-body' ?>"
                  href="volume-report.php">
                  <i class="fa-regular fa-file-lines"></i>
                  Volumes Reports
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-2 <?= basename($_SERVER['PHP_SELF']) === 'firmware-report.php' ? 'active' : 'text-body' ?>"
                  href="firmware-report.php">
                  <i class="fa-regular fa-file-lines"></i>
                  Firmware Reports
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-2 <?= basename($_SERVER['PHP_SELF']) === 'firmware-report.php' ? 'active' : 'text-body' ?>"
                  href="disk-firmware-report.php">
                  <i class="fa-regular fa-file-lines"></i>
                  Disk Firmware Reports
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-2 <?= basename($_SERVER['PHP_SELF']) === 'host_capacity.php' ? 'active' : 'text-body' ?>"
                  href="host_capacity.php">
                  <i class="fa-regular fa-file-lines"></i>
                  Host Capacity Reports
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-2 <?= basename($_SERVER['PHP_SELF']) === 'schedule.php' ? 'active' : 'text-body' ?>"
                  href="schedule.php">
                  <i class="fa-regular fa-calendar"></i>
                  Schedule Report
                </a>
              </li>
            </ul>
          </div>
        </div>



      </main>
    </div>
  </div>
  <script src="./assets/js/bootstrap.bundle.min.js"></script>
  <script src="./assets/js/chart.umd.js"></script>
  <script src="./assets/js/dashboard.js"></script>
</body>

</html>