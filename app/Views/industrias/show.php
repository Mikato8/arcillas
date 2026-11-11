<?php
/** Detalle de industria. */
$claves = App\Models\Material::propiedadesClave(array_column($resultado['items'], 'id'));
?>
<div class="contenedor">
  <nav class="migajas" aria-label="Ruta">
    <a href="<?= url('/') ?>">Inicio</a><span class="sep">/</span>
    <a href="<?= url('/industrias') ?>">Industrias</a><span class="sep">/</span><span><?= e($industria['nombre']) ?></span>
  </nav>
</div>

<section class="seccion seccion--ajustada" style="padding-top:1rem">
  <div class="contenedor">
    <div class="tarjeta" style="padding:clamp(1.5rem,3vw,2.4rem);margin-bottom:1.8rem;background:linear-gradient(135deg,#f3eee3,#fbf7ef)">
      <div class="fila" style="gap:1.2rem;align-items:flex-start">
        <span class="tarjeta-industria__icono" style="width:58px;height:58px;background:#fff"><?= icon(industria_icono($industria['slug'])) ?></span>
        <div style="flex:1;min-width:240px">
          <span class="eyebrow">Industria</span>
          <h1 style="font-size:clamp(1.8rem,3.4vw,2.6rem);margin-bottom:.4rem"><?= e($industria['nombre']) ?></h1>
          <p style="max-width:70ch;color:var(--tinta-suave);margin:0"><?= e($industria['descripcion']) ?></p>
          <div class="fila" style="margin-top:1rem">
            <span class="chip"><b><?= num($resultado['total']) ?></b> materiales</span>
            <a class="chip" href="<?= url('/materiales?industria=' . $industria['slug']) ?>" style="text-decoration:none"><?= icon('filter') ?> Ver con filtros</a>
            <a class="chip" href="<?= url('/calculadoras') ?>" style="text-decoration:none"><?= icon('calculator') ?> Calculadoras</a>
          </div>
        </div>
      </div>
    </div>

    <?php if ($resultado['items'] === []): ?>
      <div class="tarjeta" style="padding:2.4rem;text-align:center">
        <h3>Sin materiales publicados para esta industria</h3>
        <p class="texto-muted">Cuéntanos tu requerimiento y lo buscamos en la red de proveedores.</p>
        <a class="boton boton--primario" href="<?= url('/soy-comprador') ?>">Enviar requerimiento</a>
      </div>
    <?php else: ?>
      <div class="grid grid--3">
        <?php foreach ($resultado['items'] as $material): $clavesMaterial = $claves[(int) $material['id']] ?? []; $claves = $clavesMaterial; require APP_BASE . '/app/Views/partials/material-card.php'; endforeach; ?>
      </div>
      <?php $pagina = $resultado['pagina']; $paginas = $resultado['paginas']; require APP_BASE . '/app/Views/partials/paginacion.php'; ?>
    <?php endif; ?>
  </div>
</section>
