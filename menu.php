<div class="sidebar border border-right col-md-3 col-lg-2 p-0 bg-body-tertiary">
  <div class="offcanvas-md offcanvas-end bg-body-tertiary" tabindex="-1" id="sidebarMenu"
    aria-labelledby="sidebarMenuLabel">
    <div class="offcanvas-body d-md-flex flex-column p-0 pt-lg-3 overflow-y-auto">
      <!-- sidebar.php -->
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
          <a class="nav-link d-flex align-items-center gap-2 <?= basename($_SERVER['PHP_SELF']) === 'disk-firmware-report.php' ? 'active' : 'text-body' ?>"
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

      <hr class="my-3" />

      <ul class="nav flex-column mb-auto">
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center gap-2" href="logout.php">
            <i class="fa-solid fa-right-from-bracket"></i>
            Logout
          </a>
        </li>
      </ul>
    </div>
  </div>
</div>