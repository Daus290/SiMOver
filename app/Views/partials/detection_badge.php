<?php
/**
 * Partial Badge Hasil Deteksi Publik & Bersama (Text-Only, Tanpa Ikon)
 * @var string $result 'Over Dimension' | 'Bukan OD' (dashboard AI) atau 'Normal' | 'Kontainer' (data contoh lama)
 */
$status = $result ?? 'Bukan OD';
?>
<?php if ($status === 'Over Dimension'): ?>
  <span class="badge-deteksi badge-overdim">Over Dimension</span>
<?php elseif ($status === 'Kontainer'): ?>
  <span class="badge-deteksi badge-kontainer">Kontainer</span>
<?php elseif ($status === 'Normal'): ?>
  <span class="badge-deteksi badge-normal">Normal</span>
<?php else: ?>
  <span class="badge-deteksi badge-normal">Bukan OD</span>
<?php endif; ?>
