<!-- Modal Ubah Kata Sandi (Simulasi Demo Validasi) -->
<div class="modal fade" id="modalUbahKataSandi" tabindex="-1" aria-labelledby="modalUbahKataSandiLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius: var(--admin-card-radius, 8px); border: 1px solid var(--admin-border);">
      
      <!-- Header Modal (Tanpa Ikon Kunci sesuai kesepakatan desain) -->
      <div class="modal-header pb-2" style="border-bottom: 1px solid #F1F5F9;">
        <h5 class="modal-title fw-bold fs-6 text-dark" id="modalUbahKataSandiLabel">
          Ubah Kata Sandi
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>

      <!-- Form Ubah Kata Sandi -->
      <form id="changePasswordForm" action="<?= base_url('admin/ubah-kata-sandi') ?>" method="post" novalidate>
        <?= csrf_field() ?>

        <div class="modal-body py-3">
          <!-- Alert Sukses Ubah Kata Sandi Permanen -->
          <div id="passwordSuccessAlert" class="alert alert-success d-none mb-3 py-2 px-3 small" role="alert">
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-check-circle-fill text-success fs-5"></i>
              <span id="passwordSuccessMsg">Kata sandi berhasil diperbarui secara permanen.</span>
            </div>
          </div>

          <!-- Wrapper Kolom Input Kata Sandi -->
          <div id="passwordFieldsWrap">
            <!-- 1. Kata Sandi Saat Ini -->
            <div class="mb-3">
              <label for="current_password" class="form-label small fw-semibold text-dark mb-1">
                Kata Sandi Saat Ini <span class="text-danger">*</span>
              </label>
              <div class="input-password-wrap">
                <input type="password" class="form-control form-control-sm" id="current_password" name="current_password" placeholder="Masukkan kata sandi saat ini" autocomplete="current-password" required>
                <button type="button" class="btn-eye-toggle" data-target="current_password" aria-label="Tampilkan atau sembunyikan kata sandi">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
              <div id="current_password_error" class="invalid-feedback d-none small mt-1"></div>
            </div>

            <!-- 2. Kata Sandi Baru -->
            <div class="mb-3">
              <label for="new_password" class="form-label small fw-semibold text-dark mb-1">
                Kata Sandi Baru <span class="text-danger">*</span>
              </label>
              <div class="input-password-wrap">
                <input type="password" class="form-control form-control-sm" id="new_password" name="new_password" placeholder="Minimal 8 karakter" autocomplete="new-password" required>
                <button type="button" class="btn-eye-toggle" data-target="new_password" aria-label="Tampilkan atau sembunyikan kata sandi">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
              <div id="new_password_error" class="invalid-feedback d-none small mt-1"></div>
              <div class="form-text text-muted" style="font-size: 0.72rem;">
                Minimal 8 karakter dan tidak boleh sama dengan kata sandi saat ini.
              </div>
            </div>

            <!-- 3. Konfirmasi Kata Sandi Baru -->
            <div class="mb-2">
              <label for="confirm_password" class="form-label small fw-semibold text-dark mb-1">
                Konfirmasi Kata Sandi Baru <span class="text-danger">*</span>
              </label>
              <div class="input-password-wrap">
                <input type="password" class="form-control form-control-sm" id="confirm_password" name="confirm_password" placeholder="Ulangi kata sandi baru" autocomplete="new-password" required>
                <button type="button" class="btn-eye-toggle" data-target="confirm_password" aria-label="Tampilkan atau sembunyikan kata sandi">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
              <div id="confirm_password_error" class="invalid-feedback d-none small mt-1"></div>
            </div>
          </div>
        </div>

        <!-- Tombol Aksi Modal -->
        <div class="modal-footer pt-2" style="border-top: 1px solid #F1F5F9;">
          <div id="passwordModalActions" class="d-flex justify-content-end gap-2 w-100">
            <button type="button" class="btn-admin-outline btn-sm" data-bs-dismiss="modal">Batal</button>
            <button type="submit" id="btnSubmitPassword" class="btn-admin-primary btn-sm">Simpan Perubahan</button>
          </div>
          <div id="passwordSuccessDoneBtn" class="d-none w-100 text-end">
            <button type="button" class="btn-admin-primary btn-sm" data-bs-dismiss="modal">Selesai</button>
          </div>
        </div>

      </form>

    </div>
  </div>
</div>
