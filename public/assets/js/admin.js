/**
 * Sistem Monitoring Over Dimension - Dishub Kota Banjarbaru
 * Script Interaktif Panel Admin (Tahap 5)
 */

document.addEventListener('DOMContentLoaded', function () {
  // 1. Toggle Sidebar Mobile & Tablet
  const sidebar = document.getElementById('adminSidebar');
  const sidebarToggle = document.getElementById('adminSidebarToggle');
  const sidebarBackdrop = document.getElementById('adminSidebarBackdrop');

  if (sidebarToggle && sidebar && sidebarBackdrop) {
    sidebarToggle.addEventListener('click', function () {
      sidebar.classList.toggle('show');
      sidebarBackdrop.classList.toggle('show');
    });

    sidebarBackdrop.addEventListener('click', function () {
      sidebar.classList.remove('show');
      sidebarBackdrop.classList.remove('show');
    });
  }

  // 2. Dropdown Akun Admin di Bawah Sidebar
  const accountBtn = document.getElementById('adminAccountBtn');
  const accountDropdown = document.getElementById('adminAccountDropdown');

  if (accountBtn && accountDropdown) {
    accountBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      const isOpen = accountDropdown.classList.contains('show');
      accountDropdown.classList.toggle('show');
      accountBtn.setAttribute('aria-expanded', (!isOpen).toString());
    });

    // Tutup jika klik di luar
    document.addEventListener('click', function (e) {
      if (!accountDropdown.contains(e.target) && !accountBtn.contains(e.target)) {
        accountDropdown.classList.remove('show');
        accountBtn.setAttribute('aria-expanded', 'false');
      }
    });

    // Tutup jika tekan tombol Escape
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && accountDropdown.classList.contains('show')) {
        accountDropdown.classList.remove('show');
        accountBtn.setAttribute('aria-expanded', 'false');
        accountBtn.focus();
      }
    });
  }

  // 3. Toggle Tampil / Sembunyikan Kata Sandi (Ikon Mata)
  document.querySelectorAll('.btn-eye-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const targetId = this.getAttribute('data-target');
      const input = document.getElementById(targetId);
      if (!input) return;

      const isPassword = input.type === 'password';
      input.type = isPassword ? 'text' : 'password';

      const icon = this.querySelector('i');
      if (icon) {
        if (isPassword) {
          icon.classList.remove('bi-eye');
          icon.classList.add('bi-eye-slash');
        } else {
          icon.classList.remove('bi-eye-slash');
          icon.classList.add('bi-eye');
        }
      }
    });
  });

  // 4. Modal Ubah Kata Sandi (Simulasi Demo & Validasi Lengkap)
  const changePasswordForm = document.getElementById('changePasswordForm');
  const changePasswordModalEl = document.getElementById('modalUbahKataSandi');

  if (changePasswordForm && changePasswordModalEl) {
    // Bersihkan form saat modal ditutup atau dibuka ulang
    changePasswordModalEl.addEventListener('hidden.bs.modal', function () {
      resetChangePasswordForm();
    });

    changePasswordForm.addEventListener('submit', function (e) {
      e.preventDefault();

      // Clear previous error states
      clearPasswordFormErrors();

      const currentPass = document.getElementById('current_password').value;
      const newPass = document.getElementById('new_password').value;
      const confirmPass = document.getElementById('confirm_password').value;

      // Client-side quick validation first
      let hasError = false;
      if (!currentPass) {
        showFieldError('current_password', 'Kata sandi saat ini wajib diisi');
        hasError = true;
      }
      if (!newPass) {
        showFieldError('new_password', 'Kata sandi baru wajib diisi');
        hasError = true;
      } else if (newPass.length < 8) {
        showFieldError('new_password', 'Kata sandi baru minimal 8 karakter');
        hasError = true;
      } else if (newPass === currentPass) {
        showFieldError('new_password', 'Kata sandi baru tidak boleh sama dengan kata sandi saat ini');
        hasError = true;
      }

      if (!confirmPass) {
        showFieldError('confirm_password', 'Konfirmasi kata sandi baru wajib diisi');
        hasError = true;
      } else if (confirmPass !== newPass) {
        showFieldError('confirm_password', 'Konfirmasi kata sandi tidak cocok');
        hasError = true;
      }

      if (hasError) return;

      // Submit via Fetch/AJAX ke server untuk validasi server-side
      const formData = new FormData(changePasswordForm);
      const submitBtn = document.getElementById('btnSubmitPassword');
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Menyimpan...';
      }

      fetch(changePasswordForm.action, {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
      })
      .then(response => {
        if (response.status === 401) {
          window.location.href = '/admin/login';
          return null;
        }
        return response.json();
      })
      .then(data => {
        if (!data) return;

        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = 'Simpan Perubahan';
        }

        // Perbarui CSRF token di seluruh form (termasuk form logout)
        if (data.csrf_token && data.csrf_hash) {
          updateCsrfTokens(data.csrf_token, data.csrf_hash);
        }

        if (data.success) {
          const alertBox = document.getElementById('passwordSuccessAlert');
          const alertMsg = document.getElementById('passwordSuccessMsg');
          if (alertBox && alertMsg) {
            alertMsg.textContent = data.message || 'Kata sandi berhasil diperbarui secara permanen.';
            alertBox.classList.remove('d-none');
          }
          // Sembunyikan field setelah berhasil
          document.getElementById('passwordFieldsWrap').classList.add('d-none');
          document.getElementById('passwordModalActions').classList.add('d-none');
          document.getElementById('passwordSuccessDoneBtn').classList.remove('d-none');
        } else if (data.errors) {
          for (const [field, msg] of Object.entries(data.errors)) {
            showFieldError(field, msg);
          }
        } else if (data.message) {
          showFieldError('current_password', data.message);
        }
      })
      .catch(error => {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = 'Simpan Perubahan';
        }
        console.error('Error ubah kata sandi:', error);
      });
    });
  }

  function showFieldError(fieldId, message) {
    const input = document.getElementById(fieldId);
    const feedback = document.getElementById(fieldId + '_error');
    if (input) {
      input.classList.add('is-invalid');
      input.focus();
    }
    if (feedback) {
      feedback.textContent = message;
      feedback.classList.remove('d-none');
    }
  }

  function clearPasswordFormErrors() {
    ['current_password', 'new_password', 'confirm_password'].forEach(function (id) {
      const input = document.getElementById(id);
      const feedback = document.getElementById(id + '_error');
      if (input) input.classList.remove('is-invalid');
      if (feedback) {
        feedback.textContent = '';
        feedback.classList.add('d-none');
      }
    });
  }

  function resetChangePasswordForm() {
    if (changePasswordForm) {
      changePasswordForm.reset();
    }
    clearPasswordFormErrors();

    const alertBox = document.getElementById('passwordSuccessAlert');
    if (alertBox) alertBox.classList.add('d-none');

    const fieldsWrap = document.getElementById('passwordFieldsWrap');
    if (fieldsWrap) fieldsWrap.classList.remove('d-none');

    const modalActions = document.getElementById('passwordModalActions');
    if (modalActions) modalActions.classList.remove('d-none');

    const doneBtn = document.getElementById('passwordSuccessDoneBtn');
    if (doneBtn) doneBtn.classList.add('d-none');
  }

  function updateCsrfTokens(tokenName, tokenHash) {
    document.querySelectorAll('input[name="' + tokenName + '"]').forEach(function (inp) {
      inp.value = tokenHash;
    });
  }
});
