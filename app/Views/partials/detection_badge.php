<?php
/**
 * Partial Badge Hasil Deteksi Publik & Bersama (Text-Only, Tanpa Ikon)
 * @var string $result Nilai hasil deteksi ('Normal', 'Kontainer', 'Over Dimension')
 */
$status = $result ?? 'Normal';
?>
<?php if ($status === 'Normal'): ?>
  <span class="badge-deteksi badge-normal">Normal</span>
<?php elseif ($status === 'Kontainer'): ?>
  <span class="badge-deteksi badge-kontainer">Kontainer</span>
<?php else: ?>
  <span class="badge-deteksi badge-overdim">Over Dimension</span>
<?php endif; ?>
