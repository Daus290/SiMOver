<?php

namespace App\Controllers;

use App\Libraries\AtcsCctvService;
use CodeIgniter\HTTP\ResponseInterface;
use Config\AiIntegration;

/**
 * Host terisolasi untuk core deteksi Rizkiya (dimuat di dalam iframe Live CCTV).
 *
 * Isi core disisipkan byte-per-byte dari app/ThirdParty/RizkiyaCore setelah
 * SHA256-nya diverifikasi. Host hanya menambahkan dua hal di LUAR blok core:
 * sumber video HLS untuk elemen #cctv-video dan adapter pengamat keluaran DOM.
 * Tidak ada fungsi, ukuran input, threshold atau interval core yang diubah.
 */
class RizkiyaCoreHost extends BaseController
{
    public function index(): ResponseInterface
    {
        /** @var AiIntegration $ai */
        $ai = config('AiIntegration');

        $core = is_file($ai->rizkiyaCorePath) ? file_get_contents($ai->rizkiyaCorePath) : false;
        $hash = $core !== false ? hash('sha256', $core) : null;

        if ($core === false || !hash_equals($ai->rizkiyaCoreSha256, (string) $hash)) {
            return $this->response
                ->setStatusCode(409)
                ->setBody(view('ai_core/host_blocked', [
                    'expected' => strtoupper($ai->rizkiyaCoreSha256),
                    'found'    => $hash !== null ? strtoupper($hash) : 'file tidak ditemukan',
                ]));
        }

        $camera = (new AtcsCctvService())->getAiTestCamera();

        return $this->response
            ->setHeader('Cache-Control', 'no-store')
            ->setBody(view('ai_core/host', [
                'core'       => $core,
                'coreSha256' => strtoupper($hash),
                'streamUrl'  => $camera['stream_url'],
                'cameraCode' => $camera['code'],
            ]));
    }
}
