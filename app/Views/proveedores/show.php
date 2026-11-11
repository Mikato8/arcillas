<?php /** Perfil de proveedor. */ ?>
<div class="contenedor">
  <nav class="migajas" aria-label="Ruta">
    <a href="<?= url('/') ?>">Inicio</a><span class="sep">/</span>
    <a href="<?= url('/proveedores') ?>">Proveedores</a><span class="sep">/</span><span><?= e($proveedor['nombre']) ?></span>
  </nav>
</div>
<section class="seccion seccion--ajustada" style="padding-top:.6rem">
  <div class="contenedor">
    <div class="tarjeta" style="padding:clamp(1.5rem,3vw,2.4rem);margin-bottom:1.8rem">
      <div class="fila" style="gap:1.3rem;align-items:flex-start">
        <span class="proveedor-mini__logo" style="width:64px;height:64px;font-size:1.3rem"><?= e(mb_substr($proveedor['nombre'], 0, 2)) ?></span>
        <div style="flex:1;min-width:240px">
          <div class="fila" style="gap:.4rem;margin-bottom:.3rem">
            <span class="badge badge--accent"><?= e(ucfirst($proveedor['tipo'])) ?></span>
            <?php if ((int) $proveedor['verificado'] === 1): ?><span class="badge badge--ok">Verificado</span><?php endif; ?>
            <?php if ((int) $proveedor['es_demo'] === 1): ?><span class="badge badge--demo">Proveedor demo</span><?php endif; ?>
          </div>
          <h1 style="font-size:clamp(1.7rem,3vw,2.4rem);margin-bottom:.4rem"><?= e($proveedor['nombre']) ?></h1>
          <p style="color:var(--tinta-suave);max-width:70ch;margin:0"><?= e($proveedor['descripcion']) ?></p>
        </div>
        <a class="boton boton--primario" href="<?= url('/contacto?proveedor=' . urlencode($proveedor['nombre'])) ?>"><?= icon('mail') ?> Contactar</a>
      </div>
      <div class="grid grid--4" style="margin-top:1.6rem">
        <div class="dato-tecnico"><span>Ubicación</span><strong><?= e($proveedor['estado'] ?: 'México') ?><?= $proveedor['ciudad'] ? ' · ' . e($proveedor['ciudad']) : '' ?></strong></div>
        <div class="dato-tecnico"><span>Capacidad</span><strong><?= e($proveedor['capacidad'] ?: 'A convenir') ?></strong></div>
        <div class="dato-tecnico"><span>Certificaciones</span><strong><?= e($proveedor['certificaciones'] ?: 'Por documentar') ?></strong></div>
        <div class="dato-tecnico"><span>Materiales</span><strong><?= count($materiales) ?> en catálogo</strong></div>
      </div>
    </div>

    <div class="encabezado-seccion" style="margin-bottom:1.2rem">
      <div class="encabezado-seccion__texto">
        <span class="eyebrow">Catálogo</span>
        <h2 style="font-size:1.55rem">Materiales que suministra</h2>
      </div>
    </div>
    <?php if ($materiales === []): ?>
      <div class="tarjeta" style="padding:2rem;text-align:center"><p class="texto-muted sin-margen">Este proveedor aún no tiene materiales publicados.</p></div>
    <?php else: ?>
      <div class="grid grid--3">
        <?php foreach ($materiales as $material): $claves = []; require APP_BASE . '/app/Views/partials/material-card.php'; endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
