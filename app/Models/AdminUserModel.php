<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * AdminUserModel
 * 
 * Model untuk mengelola akun administrator pada tabel admin_users.
 */
class AdminUserModel extends Model
{
    protected $table            = 'admin_users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

    // Hanya field ini yang diizinkan untuk operasi insert/update
    protected $allowedFields    = [
        'username',
        'full_name',
        'password_hash',
        'role',
        'is_active',
        'auth_version',
        'last_login_at',
        'created_at',
        'updated_at',
    ];

    // Otomatisasi timestamp CodeIgniter
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Aturan validasi internal model
    protected $validationRules = [
        'username'  => 'required|max_length[100]|is_unique[admin_users.username,id,{id}]',
        'full_name' => 'required|max_length[150]',
        'role'      => 'required|in_list[admin]',
    ];

    /**
     * Cari akun berdasarkan username dengan normalisasi huruf kecil dan trim.
     */
    public function findByNormalizedUsername(string $username): ?array
    {
        $normalized = mb_strtolower(trim($username));
        if ($normalized === '') {
            return null;
        }

        return $this->where('username', $normalized)->first();
    }

    /**
     * Cari akun aktif dengan role admin berdasarkan ID (untuk filter sesi).
     */
    public function findActiveAdminById(int $id): ?array
    {
        return $this->where('id', $id)
            ->where('is_active', 1)
            ->where('role', 'admin')
            ->first();
    }

    /**
     * Perbarui timestamp last_login_at saat autentikasi berhasil.
     */
    public function recordLoginSuccess(int $id): bool
    {
        return $this->update($id, [
            'last_login_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Perbarui kata sandi dan inkremen auth_version secara atomik untuk menganulir sesi lama.
     */
    public function updatePasswordAndIncrementVersion(int $id, string $newPasswordHash, int $currentAuthVersion): bool
    {
        return $this->update($id, [
            'password_hash' => $newPasswordHash,
            'auth_version'  => $currentAuthVersion + 1,
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);
    }
}
