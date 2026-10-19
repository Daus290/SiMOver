<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\AdminUserModel;

/**
 * AdminBootstrap
 * 
 * Perintah CLI untuk inisialisasi satu akun administrator awal dari
 * variabel lingkungan sementara (.env) pada mode development lokal.
 */
class AdminBootstrap extends BaseCommand
{
    protected $group       = 'Admin';
    protected $name        = 'admin:bootstrap';
    protected $description = 'Membuat satu akun admin awal dari variabel lingkungan sementara di .env (lokal development).';

    public function run(array $params)
    {
        // 1. Validasi konteks eksekusi: hanya CLI dan hanya development
        if (!is_cli()) {
            CLI::error('Perintah ini hanya dapat dijalankan melalui CLI.');
            return;
        }

        if (ENVIRONMENT !== 'development') {
            CLI::error('Perintah bootstrap hanya diizinkan pada environment development.');
            return;
        }

        // 2. Baca kredensial dari environment variable sementara
        $username = trim((string) env('BOOTSTRAP_ADMIN_USERNAME', ''));
        $fullName = trim((string) env('BOOTSTRAP_ADMIN_NAME', ''));
        $password = (string) env('BOOTSTRAP_ADMIN_PASSWORD', '');

        if ($username === '' || $fullName === '' || $password === '') {
            CLI::error('Variabel BOOTSTRAP_ADMIN_USERNAME, BOOTSTRAP_ADMIN_NAME, atau BOOTSTRAP_ADMIN_PASSWORD tidak ditemukan di .env.');
            return;
        }

        if (mb_strlen($password) < 8) {
            CLI::error('Password awal minimal harus 8 karakter.');
            return;
        }

        $normalizedUsername = mb_strtolower($username);

        $model = new AdminUserModel();

        // 3. Verifikasi apakah akun sudah ada
        $existing = $model->where('username', $normalizedUsername)->first();
        if ($existing !== null) {
            CLI::write('Akun admin dengan username "' . $normalizedUsername . '" sudah ada di database. Tidak ada perubahan dilakukan.', 'yellow');
            return;
        }

        // 4. Hash kata sandi dengan PASSWORD_DEFAULT (bcrypt)
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // 5. Buat akun baru di database
        $inserted = $model->insert([
            'username'      => $normalizedUsername,
            'full_name'     => $fullName,
            'password_hash' => $hash,
            'role'          => 'admin',
            'is_active'     => 1,
            'auth_version'  => 1,
            'last_login_at' => null,
        ]);

        if (!$inserted) {
            CLI::error('Gagal membuat akun admin: ' . json_encode($model->errors()));
            return;
        }

        CLI::write('Akun admin awal berhasil dibuat secara aman di database monitoring_dishub!', 'green');
        CLI::write('Username   : ' . $normalizedUsername, 'white');
        CLI::write('Nama Akun  : ' . $fullName, 'white');
        CLI::write('Status     : Aktif (role: admin)', 'white');
        CLI::write('Peringatan : Hapus variabel BOOTSTRAP_ADMIN_* dari .env setelah selesai verifikasi.', 'yellow');
    }
}
