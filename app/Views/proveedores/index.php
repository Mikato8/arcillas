<?php /** Directorio de proveedores. */ ?>
<div class="contenedor">
  <nav class="migajas" aria-label="Ruta"><a href="<?= url('/') ?>">Inicio</a><span class="sep">/</span><span>Proveedores</span></nav>
</div>
<section class="seccion seccion--ajustada" style="padding-top:1.2rem">
  <div class="contenedor">
    <div class="encabezado-seccion">
      <div class="encabezado-seccion__texto">
        <span class="eyebrow">Red de suministro</span>
        <h2>Minas, plantas y distribuidores</h2>
        <p>Proveedores de arcillas, minerales industriales y concentrados no ferrosos. La información de contacto se gestiona a través de una solicitud para proteger a ambas partes.</p>
      </div>
    </div>
    <div class="aviso-info" style="margin-bottom:1.4rem">
      <?= icon('info') ?> <?= e((string) setting('catalogo_nota_demo', 'Las fichas marcadas como Demo son datos de demostración del catálogo inicial: se sustituyen por proveedores reales verificados antes de operar comercialmente.')) ?>
    </div>
    <div class="grid grid--3">
      <?php foreach ($proveedores as $prov): ?>
        <a class="tarjeta tarjeta--enlace" href="<?= url('/proveedores/' . $prov['slug']) ?>" style="display:block;padding:1.3rem;text-decoration:none;color:inherit">
          <div class="fila fila--separada">
            <span class="proveedor-mini__logo" style="width:48px;height:48px"><?= e(mb_substr($prov['nombre'], 0, 2)) ?></span>
            <?php if ((int) $prov['verificado'] === 1): ?><span class="badge badge--ok">Verificado</span><?php elseif ((int) $prov['es_demo'] === 1): ?><span class="badge badge--demo">Demo</span><?php else: ?><span class="badge badge--ghost">Por verificar</span><?php endif; ?>
          </div>
          <h3 style="font-size:1.05rem;margin:.9rem 0 .2rem"><?= e($prov['nombre']) ?></h3>
          <p class="texto-pequeno texto-muted" style="margin:0"><?= e(ucfirst($prov['tipo'])) ?> · <?= e($prov['estado'] ?: 'México') ?></p>
          <p style="font-size:.85rem;color:var(--tinta-suave);margin:.7rem 0 0;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden"><?= e((string) $prov['descripcion']) ?></p>
          <div class="fila" style="margin-top:.9rem;justify-content:space-between">
            <span class="texto-mono" style="color:var(--tierra-oscura)"><?= (int) $prov['total'] ?> materiales</span>
            <span class="enlace-flecha texto-pequeno">Ver perfil <?= icon('arrow-right') ?></span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
