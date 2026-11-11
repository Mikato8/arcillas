<?php
/**
 * Paginación.
 * @var int $pagina @var int $paginas
 */
if (($paginas ?? 1) <= 1) {
    return;
}
$rango = 2;
$desde = max(1, $pagina - $rango);
$hasta = min($paginas, $pagina + $rango);
?>
<nav class="paginacion" aria-label="Paginación">
  <?php if ($pagina > 1): ?>
    <a href="<?= e(q(['pagina' => $pagina - 1])) ?>" aria-label="Página anterior"><?= icon('arrow-left', '') ?></a>
  <?php else: ?>
    <span class="vacio"><?= icon('arrow-left') ?></span>
  <?php endif; ?>

  <?php if ($desde > 1): ?>
    <a href="<?= e(q(['pagina' => 1])) ?>">1</a>
    <?php if ($desde > 2): ?><span class="vacio">…</span><?php endif; ?>
  <?php endif; ?>

  <?php for ($i = $desde; $i <= $hasta; $i++): ?>
    <?php if ($i === $pagina): ?>
      <span class="actual" aria-current="page"><?= $i ?></span>
    <?php else: ?>
      <a href="<?= e(q(['pagina' => $i])) ?>"><?= $i ?></a>
    <?php endif; ?>
  <?php endfor; ?>

  <?php if ($hasta < $paginas): ?>
    <?php if ($hasta < $paginas - 1): ?><span class="vacio">…</span><?php endif; ?>
    <a href="<?= e(q(['pagina' => $paginas])) ?>"><?= $paginas ?></a>
  <?php endif; ?>

  <?php if ($pagina < $paginas): ?>
    <a href="<?= e(q(['pagina' => $pagina + 1])) ?>" aria-label="Página siguiente"><?= icon('arrow-right') ?></a>
  <?php else: ?>
    <span class="vacio"><?= icon('arrow-right') ?></span>
  <?php endif; ?>
</nav>
