<?php

namespace App\Filters;

use App\Models\AdminUserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

/**
 * AdminAuthFilter
 * 
 * Melindungi rute-rute admin dengan memverifikasi keberadaan sesi akun nyata,
 * validasi ke database (akun aktif, role admin, auth_version cocok), serta
 * mencegah browser caching pada area sensitif.
 */
class AdminAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $adminId = $session->get('admin_id');
        $authVersion = $session->get('auth_version');

        $isAjax = $request->isAJAX();

        // 1. Sesi harus memiliki admin_id dan auth_version dari database
        if (empty($adminId) || empty($authVersion)) {
            $this->clearAdminSession();

            if ($isAjax) {
                return service('response')
                    ->setStatusCode(401)
                    ->setJSON([
                        'success'    => false,
                        'message'    => 'Sesi Anda telah berakhir. Silakan muat ulang dan masuk kembali.',
                        'csrf_token' => csrf_token(),
                        'csrf_hash'  => csrf_hash(),
                    ]);
            }

            return redirect()->to(base_url('admin/login'))
                ->with('error', 'Silakan masuk terlebih dahulu untuk mengakses halaman admin.');
        }

        // 2. Verifikasi akun nyata di database (Fail Closed)
        try {
            $model = new AdminUserModel();
            $user = $model->findActiveAdminById((int) $adminId);

            // Akun tidak ditemukan atau tidak aktif atau role bukan admin
            if (!$user) {
                $this->clearAdminSession();

                if ($isAjax) {
                    return service('response')
                        ->setStatusCode(401)
                        ->setJSON([
                            'success'    => false,
                            'message'    => 'Akun tidak valid atau telah dinonaktifkan.',
                            'csrf_token' => csrf_token(),
                            'csrf_hash'  => csrf_hash(),
                        ]);
                }

                return redirect()->to(base_url('admin/login'))
                    ->with('authError', 'Akun tidak valid atau telah dinonaktifkan.');
            }

            // 3. auth_version di sesi harus cocok dengan database (menganulir sesi lama ketika kata sandi diubah)
            if ((int) $user['auth_version'] !== (int) $authVersion) {
                $this->clearAdminSession();

                if ($isAjax) {
                    return service('response')
                        ->setStatusCode(401)
                        ->setJSON([
                            'success'    => false,
                            'message'    => 'Kata sandi telah diperbarui dari sesi lain. Silakan masuk kembali.',
                            'csrf_token' => csrf_token(),
                            'csrf_hash'  => csrf_hash(),
                        ]);
                }

                return redirect()->to(base_url('admin/login'))
                    ->with('authError', 'Kata sandi akun telah diperbarui. Silakan masuk kembali dengan kata sandi baru.');
            }
        } catch (Throwable $e) {
            // Fail closed jika terjadi kesalahan teknis database
            log_message('error', 'AdminAuthFilter DB Error: {message}', ['message' => $e->getMessage()]);
            $this->clearAdminSession();

            if ($isAjax) {
                return service('response')
                    ->setStatusCode(503)
                    ->setJSON([
                        'success' => false,
                        'message' => 'Layanan autentikasi database sedang tidak tersedia.',
                    ]);
            }

            return redirect()->to(base_url('admin/login'))
                ->with('authError', 'Layanan autentikasi sedang tidak dapat diakses saat ini.');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Cegah caching browser untuk seluruh halaman admin yang dilindungi
        $response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->setHeader('Pragma', 'no-cache');
        $response->setHeader('Expires', '0');

        return $response;
    }

    private function clearAdminSession(): void
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
    }
}
