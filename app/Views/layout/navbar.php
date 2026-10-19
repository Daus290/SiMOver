<!-- Navbar Satu Baris Solid Oranye (#EA580C) dengan Highlight Garis Bawah Figma -->
<nav class="navbar navbar-expand-lg navbar-dishub sticky-top">
  <div class="container">
    <a class="navbar-brand-wrapper" href="<?= base_url() ?>">
      <img src="<?= base_url('assets/img/logo-dishub.png') ?>" alt="Logo Dishub Kota Banjarbaru" height="42" class="d-inline-block rounded-circle bg-white p-1">
      <div class="d-flex flex-column text-white ms-1">
        <span class="fw-bold" style="font-size: 1rem; line-height: 1.1; letter-spacing: 0.3px;">DISHUB BANJARBARU</span>
        <span class="small opacity-75" style="font-size: 0.72rem; letter-spacing: 0.2px;">SIMOVER</span>
      </div>
    </a>

    <button class="navbar-toggler border-0 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Buka menu navigasi">
      <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarContent">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center gap-1">
        <li class="nav-item">
          <a class="nav-link nav-link-dishub <?= ($active_nav === 'beranda') ? 'active' : '' ?>" 
             href="<?= base_url() ?>"
             <?= ($active_nav === 'beranda') ? 'aria-current="page"' : '' ?>>
            Beranda
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link nav-link-dishub <?= ($active_nav === 'live_cctv') ? 'active' : '' ?>" 
             href="<?= base_url('live-cctv') ?>"
             <?= ($active_nav === 'live_cctv') ? 'aria-current="page"' : '' ?>>
            Live CCTV
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link nav-link-dishub <?= ($active_nav === 'dashboard') ? 'active' : '' ?>" 
             href="<?= base_url('dashboard') ?>"
             <?= ($active_nav === 'dashboard') ? 'aria-current="page"' : '' ?>>
            Dashboard
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link nav-link-dishub <?= ($active_nav === 'peta') ? 'active' : '' ?>" 
             href="<?= base_url('peta-cctv') ?>"
             <?= ($active_nav === 'peta') ? 'aria-current="page"' : '' ?>>
            Peta Interaktif
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>
