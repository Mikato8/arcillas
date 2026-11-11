<?php
/**
 * Tarjeta de material.
 * @var array<string,mixed> $material
 * @var array<int,array<string,mixed>> $claves (opcional) propiedades clave
 */
$claves = $claves ?? [];
$urlMaterial = url('/materiales/' . $material['slug']);
$props = [];
foreach ($claves as $prop) {
    if ((int) $prop['material_id'] === (int) $material['id']) {
        $props[] = $prop;
    }
}
?>
<article class="tarjeta tarjeta--enlace tarjeta-material">
  <a class="tarjeta-material__media" href="<?= e($urlMaterial) ?>" tabindex="-1" aria-hidden="true">
    <img src="<?= e(material_imagen($material)) ?>" alt="" loading="lazy" width="640" height="400">
    <span class="tarjeta-material__etiqueta">
      <span class="tarjeta-material__categoria"><?= e($material['categoria_nombre'] ?? '') ?></span>
      <?php if (!empty($material['destacado'])): ?><span class="tarjeta-material__destacado">Destacado</span><?php endif; ?>
    </span>
  </a>
  <div class="tarjeta-material__cuerpo">
    <h3 class="tarjeta-material__titulo"><a href="<?= e($urlMaterial) ?>"><?= e($material['nombre']) ?></a></h3>
    <p class="tarjeta-material__resumen"><?= e(truncate((string) $material['resumen'], 120)) ?></p>
    <?php if ($props !== []): ?>
      <div class="chips-propiedades">
        <?php foreach (array_slice($props, 0, 3) as $prop): ?>
          <span class="chip"><b><?= e($prop['nombre']) ?></b> <?= e(prop_valor($prop)) ?></span>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
  <div class="tarjeta-material__pie">
    <span class="precio-ref">
      <?= e(money($material['precio_ref'] ?? null, (string) ($material['moneda'] ?? 'MXN'))) ?>
      <?php if (!empty($material['precio_ref'])): ?><span>/<?= e($material['unidad']) ?></span><?php endif; ?>
    </span>
    <span class="fila" style="gap:.4rem">
      <?= badge_disponibilidad((string) ($material['disponibilidad'] ?? 'inmediata')) ?>
      <?php if (!empty($mostrarComparar)): ?>
        <button class="chip" type="button" data-comparar="<?= e($material['slug']) ?>" data-nombre="<?= e($material['nombre']) ?>" title="Agregar al comparador"><?= icon('scale') ?> Comparar</button>
      <?php elseif (!empty($material['proveedores'])): ?>
        <span class="texto-mono" style="color:var(--muted)"><?= (int) $material['proveedores'] ?> prov.</span>
      <?php endif; ?>
    </span>
  </div>
</article>
