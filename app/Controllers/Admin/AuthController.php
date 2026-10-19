<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\DummyDataService;
use App\Models\AdminUserModel;
use Throwable;

/**
 * AuthController
 * 
 * Mengelola autentikasi akun administrator nyata berbasis database lokal:
 * login, logout, sesi server, rate limiting, dan perubahan kata sandi permanen.
 */
class AuthController extends BaseController
{
    private DummyDataService $dummyData;

    public function __construct()
    {
        $this->dummyData = new DummyDataService();
    }

    /**
     * Pintu masuk /admin: arahkan ke dashboard jika sudah login, atau ke login form jika belum.
     */
    public function index()
    {
        if (session()->get('admin_id')) {
            return redirect()->to(base_url('admin/dashboard'));
        }
        return redirect()->to(base_url('admin/login'));
    }

    /**
     * Halaman Login Admin
     */
    public function login()
    {
        if (session()->get('admin_id')) {
            return redirect()->to(base_url('admin/dashboard'));
        }

        $meta = $this->dummyData->getSystemMeta();

        $data = [
            'meta'      => $meta,
            'title'     => 'Login Admin - ' . $meta['app_name'],
            'errors'    => session()->getFlashdata('errors') ?? [],
            'oldInput'  => session()->getFlashdata('oldInput') ?? [],
            'authError' => session()->getFlashdata('authError') ?? null,
            'success'   => session()->getFlashdata('success') ?? null,
        ];

        // Cegah caching pada halaman login
        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $this->response->setHeader('Pragma', 'no-cache');
        $this->response->setHeader('Expires', '0');

        return view('admin/auth/login', $data);
    }

    /**
     * Proses autentikasi login admin ke database lokal dengan rate limiting
     */
    public function doLogin()
    {
        $username = trim((string)$this->request->getPost('username'));
        $password = (string)$this->request->getPost('password');

        $errors = [];
        if ($username === '') {
            $errors['username'] = 'Username wajib diisi';
        }
        if ($password === '') {
            $errors['password'] = 'Kata sandi wajib diisi';
        }

        if (!empty($errors)) {
            return redirect()->to(base_url('admin/login'))
                ->with('errors', $errors)
                ->with('oldInput', ['username' => $username]);
        }

        // Pembatasan percobaan login (Rate Limiting) sisi server:
        // Maksimal 10 percobaan per 15 menit (900 detik) per IP + normalized username,
        // serta maksimal 30 percobaan per 15 menit per IP.
        $ip = $this->request->getIPAddress();
        $normalizedUsername = mb_strtolower($username);
        $userThrottleKey = 'login_user_' . md5($ip . '_' . $normalizedUsername);
        $ipThrottleKey = 'login_ip_' . md5($ip);

        $throttler = service('throttler');
        $isUserAllowed = $throttler->check($userThrottleKey, 10, 900);
        $isIpAllowed   = $throttler->check($ipThrottleKey, 30, 900);

        if (!$isUserAllowed || !$isIpAllowed) {
            $retryAfter = $throttler->getTokenTime();
            $waitMinutes = max(1, (int) ceil($retryAfter / 60));

            return redirect()->to(base_url('admin/login'))
                ->with('authError', "Terlalu banyak percobaan masuk yang gagal. Silakan tunggu {$waitMinutes} menit sebelum mencoba kembali.")
                ->with('oldInput', ['username' => $username]);
        }

        try {
            $model = new AdminUserModel();
            $user = $model->findByNormalizedUsername($username);

            // Mitigasi timing attack: lakukan dummy verification jika akun tidak ditemukan
            if (!$user) {
                $dummyHash = '$2y$10$abcdefghijklmnopqrstuuABCDEFGHIJKLMNOPQRSTUU1234567890';
                password_verify($password, $dummyHash);

                return redirect()->to(base_url('admin/login'))
                    ->with('authError', 'Username atau kata sandi tidak sesuai.')
                    ->with('oldInput', ['username' => $username]);
            }

            // Periksa status keaktifan akun dan role
            if ((int)$user['is_active'] !== 1 || $user['role'] !== 'admin') {
                return redirect()->to(base_url('admin/login'))
                    ->with('authError', 'Akun tidak valid atau telah dinonaktifkan.')
                    ->with('oldInput', ['username' => $username]);
            }

            // Verifikasi kata sandi dengan hash database
            if (!password_verify($password, $user['password_hash'])) {
                return redirect()->to(base_url('admin/login'))
                    ->with('authError', 'Username atau kata sandi tidak sesuai.')
                    ->with('oldInput', ['username' => $username]);
            }

            // Rehash jika algoritma default PHP telah diperbarui
            if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                $model->update($user['id'], [
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                ]);
            }

            // Catat waktu login berhasil
            $model->recordLoginSuccess((int) $user['id']);

            // Regenerasi ID sesi untuk mencegah session fixation
            session()->regenerate();

            // Simpan identitas autentikasi nyata dari database ke sesi
            session()->set([
                'is_admin_logged_in' => true,
                'admin_id'           => (int) $user['id'],
                'admin_user'         => $user['full_name'],
                'admin_username'     => $user['username'],
                'admin_role'         => ($user['role'] === 'admin') ? 'Administrator Sistem' : $user['role'],
                'auth_version'       => (int) $user['auth_version'],
                'logged_in_time'     => date('Y-m-d H:i:s'),
            ]);

            return redirect()->to(base_url('admin/dashboard'))
                ->with('success', 'Selamat datang kembali, ' . esc($user['full_name']) . '.');

        } catch (Throwable $e) {
            log_message('error', 'Login DB Error: {message}', ['message' => $e->getMessage()]);

            return redirect()->to(base_url('admin/login'))
                ->with('authError', 'Layanan autentikasi database sedang tidak dapat diakses. Silakan coba beberapa saat lagi.')
                ->with('oldInput', ['username' => $username]);
        }
    }

    /**
     * Proses logout admin via POST
     */
    public function logout()
    {
        session()->remove([
            'is_admin_logged_in',
            'admin_id',
            'admin_user',
            'admin_username',
            'admin_role',
            'auth_version',
            'logged_in_time',
        ]);
        session()->destroy();

        return redirect()->to(base_url('admin/login'))
            ->with('success', 'Anda telah berhasil keluar dari sistem admin.');
    }

    /**
     * Proses perubahan kata sandi permanen ke database lokal
     */
    public function changePassword()
    {
        $adminId = session()->get('admin_id');
        $authVersion = session()->get('auth_version');

        $isAjax = $this->request->isAJAX();

        if (empty($adminId)) {
            if ($isAjax) {
                return $this->response->setStatusCode(401)->setJSON([
                    'success'    => false,
                    'message'    => 'Sesi Anda telah berakhir. Silakan masuk kembali.',
                    'csrf_token' => csrf_token(),
                    'csrf_hash'  => csrf_hash(),
                ]);
            }
            return redirect()->to(base_url('admin/login'));
        }

        $currentPassword = (string)$this->request->getPost('current_password');
        $newPassword     = (string)$this->request->getPost('new_password');
        $confirmPassword = (string)$this->request->getPost('confirm_password');

        $errors = [];

        try {
            $model = new AdminUserModel();
            $user = $model->findActiveAdminById((int) $adminId);

            if (!$user) {
                if ($isAjax) {
                    return $this->response->setStatusCode(401)->setJSON([
                        'success'    => false,
                        'message'    => 'Akun tidak ditemukan atau tidak aktif.',
                        'csrf_token' => csrf_token(),
                        'csrf_hash'  => csrf_hash(),
                    ]);
                }
                return redirect()->to(base_url('admin/login'));
            }

            // 1. Validasi kata sandi saat ini terhadap hash di database
            if ($currentPassword === '') {
                $errors['current_password'] = 'Kata sandi saat ini wajib diisi';
            } elseif (!password_verify($currentPassword, $user['password_hash'])) {
                $errors['current_password'] = 'Kata sandi saat ini tidak sesuai';
            }

            // 2. Validasi kata sandi baru
            if ($newPassword === '') {
                $errors['new_password'] = 'Kata sandi baru wajib diisi';
            } elseif (mb_strlen($newPassword) < 8) {
                $errors['new_password'] = 'Kata sandi baru minimal 8 karakter';
            } elseif (strlen($newPassword) > 72) {
                $errors['new_password'] = 'Kata sandi baru maksimal 72 karakter';
            } elseif ($newPassword === $currentPassword) {
                $errors['new_password'] = 'Kata sandi baru tidak boleh sama dengan kata sandi saat ini';
            }

            // 3. Validasi konfirmasi kata sandi baru
            if ($confirmPassword === '') {
                $errors['confirm_password'] = 'Konfirmasi kata sandi baru wajib diisi';
            } elseif ($confirmPassword !== $newPassword) {
                $errors['confirm_password'] = 'Konfirmasi kata sandi tidak cocok';
            }

            if (!empty($errors)) {
                if ($isAjax) {
                    return $this->response->setJSON([
                        'success'    => false,
                        'errors'     => $errors,
                        'csrf_token' => csrf_token(),
                        'csrf_hash'  => csrf_hash(),
                    ]);
                }
                return redirect()->back()->with('password_errors', $errors);
            }

            // 4. Hash kata sandi baru secara aman dan simpan permanen
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $currentVer = (int)$user['auth_version'];

            $saved = $model->updatePasswordAndIncrementVersion((int)$user['id'], $newHash, $currentVer);

            if (!$saved) {
                if ($isAjax) {
                    return $this->response->setStatusCode(500)->setJSON([
                        'success'    => false,
                        'message'    => 'Gagal menyimpan perubahan kata sandi ke database.',
                        'csrf_token' => csrf_token(),
                        'csrf_hash'  => csrf_hash(),
                    ]);
                }
                return redirect()->back()->with('password_errors', ['general' => 'Gagal menyimpan perubahan kata sandi.']);
            }

            // 5. Perbarui auth_version di sesi saat ini dan regenerasi session ID
            session()->set('auth_version', $currentVer + 1);
            session()->regenerate();

            $successMsg = 'Kata sandi berhasil diperbarui secara permanen.';

            if ($isAjax) {
                return $this->response->setJSON([
                    'success'    => true,
                    'message'    => $successMsg,
                    'csrf_token' => csrf_token(),
                    'csrf_hash'  => csrf_hash(),
                ]);
            }

            return redirect()->back()->with('password_success', $successMsg);

        } catch (Throwable $e) {
            log_message('error', 'ChangePassword DB Error: {message}', ['message' => $e->getMessage()]);

            if ($isAjax) {
                return $this->response->setStatusCode(500)->setJSON([
                    'success'    => false,
                    'message'    => 'Terjadi gangguan saat menyimpan data ke database.',
                    'csrf_token' => csrf_token(),
                    'csrf_hash'  => csrf_hash(),
                ]);
            }

            return redirect()->back()->with('password_errors', ['general' => 'Terjadi gangguan layanan database.']);
        }
    }
}
