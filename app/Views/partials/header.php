<?php
/** Cabecera pública. */
$ruta = App\Core\Request::path();
$links = [
    ['/materiales', 'Materiales'],
    ['/industrias', 'Industrias'],
    ['/proveedores', 'Proveedores'],
    ['/calculadoras', 'Calculadoras'],
    ['/como-funciona', 'Cómo funciona'],
    ['/nosotros', 'Nosotros'],
];
?>
<div class="barra-superior">
  <div class="contenedor">
    <div class="barra-superior__grupo barra-superior__grupo--secundario">
      <span><?= icon('pin') ?> <?= e(setting('empresa_ciudad', 'Ciudad de México, México')) ?></span>
      <span><?= icon('clock') ?> Respuesta en <?= e(setting('respuesta_horas', '24')) ?> h hábiles</span>
    </div>
    <div class="barra-superior__grupo">
      <a href="tel:<?= e(preg_replace('/\s+/', '', (string) setting('empresa_telefono', ''))) ?>"><?= icon('phone') ?><?= e(setting('empresa_telefono', '+52 55 0000 0000')) ?></a>
      <a href="<?= e(whatsapp_url('Hola, quiero cotizar un mineral.')) ?>" target="_blank" rel="noopener"><?= icon('whatsapp') ?>WhatsApp</a>
      <a href="<?= url('/contacto') ?>"><?= icon('mail') ?>Contacto</a>
    </div>
  </div>
</div>
<header class="sitio-header" id="sitio-header">
  <div class="contenedor sitio-header__interior">
    <a class="marca" href="<?= url('/') ?>" aria-label="Arcillas MX, inicio">
      <img class="marca__simbolo" src="<?= url('/assets/img/marca.svg') ?>" alt="" width="42" height="42">
      <span class="marca__texto">
        <span class="marca__nombre">Arcillas MX</span>
        <span class="marca__bajada">Minerales industriales</span>
      </span>
    </a>
    <nav class="nav-principal" aria-label="Navegación principal">
      <?php foreach ($links as [$href, $label]): ?>
        <a class="<?= is_active($href) ?>" href="<?= url($href) ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="header-acciones">
      <a class="boton boton--fantasma boton--sm" href="<?= url('/materiales') ?>"><?= icon('search') ?><span class="oculto-movil">Buscar</span></a>
      <a class="boton boton--primario boton--sm" href="<?= url('/soy-comprador') ?>"><?= icon('file-text') ?> Cotizar</a>
      <button class="boton-menu" type="button" aria-label="Abrir menú" aria-expanded="false" aria-controls="nav-movil" data-menu-toggle>
        <?= icon('menu') ?>
      </button>
    </div>
  </div>
  <div class="nav-movil" id="nav-movil">
    <?php foreach ($links as [$href, $label]): ?>
      <a href="<?= url($href) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
    <a href="<?= url('/soy-proveedor') ?>">Soy proveedor</a>
    <a class="boton boton--primario" href="<?= url('/soy-comprador') ?>"><?= icon('file-text') ?> Solicitar cotización</a>
  </div>
</header>
