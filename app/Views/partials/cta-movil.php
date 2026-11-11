<?php
/** Barra fija de conversión en móvil para fichas de material. */
$urlCotizar = url('/solicitud?tipo=cotizacion&material=' . $material['slug']);
$urlMuestra = url('/solicitud?tipo=muestra&material=' . $material['slug']);
?>
<div class="cta-movil no-print" role="region" aria-label="Acciones rápidas">
  <div class="cta-movil__precio">
    <span>Desde</span>
    <strong><?= e(money($material['precio_ref'] ?? null, (string) ($material['moneda'] ?? 'MXN'))) ?></strong>
  </div>
  <a class="boton boton--primario" href="<?= e($urlCotizar) ?>"><?= icon('file-text') ?> Cotizar</a>
  <a class="boton boton--contorno" href="<?= e($urlMuestra) ?>"><?= icon('box') ?> Muestra</a>
</div>
