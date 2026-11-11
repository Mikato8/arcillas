<?php
/** Ficha de material. */
$gruposEtiquetas = [
    'quimica'       => 'Composición química',
    'fisica'        => 'Propiedades físicas',
    'granulometria' => 'Granulometría y humedad',
    'presentacion'  => 'Presentación',
];
$calcRelacionadas = calculadoras_relacionadas((string) $material['categoria_slug']);
$urlBase = url('/materiales/' . $material['slug']);
?>
<div class="contenedor">
  <nav class="migajas" aria-label="Ruta">
    <a href="<?= url('/') ?>">Inicio</a><span class="sep">/</span>
    <a href="<?= url('/materiales') ?>">Materiales</a><span class="sep">/</span>
    <a href="<?= url('/materiales?categoria=' . $material['categoria_slug']) ?>"><?= e($material['categoria_nombre']) ?></a><span class="sep">/</span>
    <span><?= e($material['nombre']) ?></span>
  </nav>
</div>

<section class="ficha-hero">
  <div class="contenedor ficha-hero__grid">
    <div>
      <div class="ficha-hero__etiquetas">
        <a class="badge badge--accent" href="<?= url('/materiales?categoria=' . $material['categoria_slug']) ?>"><?= e($material['categoria_nombre']) ?></a>
        <?= badge_disponibilidad((string) $material['disponibilidad']) ?>
        <?php if (!empty($material['es_demo'])): ?><span class="badge badge--demo">Datos demo</span><?php endif; ?>
      </div>
      <h1><?= e($material['nombre']) ?></h1>
      <p class="ficha-hero__resumen"><?= e($material['resumen']) ?></p>
      <div class="ficha-hero__datos">
        <div class="dato-tecnico"><span>Origen</span><strong><?= e($material['origen'] ?: 'México') ?></strong></div>
        <div class="dato-tecnico"><span>Región de suministro</span><strong><?= e($material['region'] ?: 'Nacional') ?></strong></div>
        <div class="dato-tecnico"><span>Presentación</span><strong><?= e(truncate((string) ($material['presentacion'] ?: 'A convenir'), 42)) ?></strong></div>
      </div>
    </div>
    <div>
      <div class="ficha-hero__imagen">
        <img src="<?= e(material_imagen($material)) ?>" alt="Textura ilustrativa de <?= e($material['nombre']) ?>" width="800" height="600">
      </div>
      <p class="ficha-hero__imagen-pie">Imagen ilustrativa de familia mineral · ficha técnica verificable</p>
    </div>
  </div>
</section>

<div class="contenedor layout-ficha">
  <div>
    <section class="ficha-seccion">
      <h2>Descripción</h2>
      <div class="prosa"><?= nl2br(e((string) $material['descripcion'])) ?></div>
    </section>

    <?php if (!empty($material['aplicaciones'])): ?>
    <section class="ficha-seccion">
      <h2>Aplicaciones industriales</h2>
      <div class="prosa"><p><?= nl2br(e((string) $material['aplicaciones'])) ?></p></div>
      <?php if ($industrias !== []): ?>
        <div class="fila" style="margin-top:.6rem">
          <?php foreach ($industrias as $ind): ?>
            <a class="badge badge--info" href="<?= url('/industrias/' . $ind['slug']) ?>"><?= e($ind['nombre']) ?></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
    <?php endif; ?>

    <section class="ficha-seccion" id="propiedades">
      <h2>Propiedades técnicas</h2>
      <p class="texto-muted texto-pequeno">Valores típicos de referencia del proveedor. La especificación contractual se acuerda por lote y se respalda con análisis de laboratorio.</p>
      <?php foreach ($gruposEtiquetas as $grupo => $etiqueta): ?>
        <?php if (!empty($propiedades[$grupo])): ?>
          <div class="propiedades-grupo">
            <h3><?= e($etiqueta) ?></h3>
            <div class="tabla-envoltura">
              <table class="tabla tabla--compacta tabla--propiedades">
                <tbody>
                <?php foreach ($propiedades[$grupo] as $prop): ?>
                  <tr>
                    <td><?= e($prop['nombre']) ?></td>
                    <td><?= e(prop_valor($prop)) ?></td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
      <?php if (empty($propiedades['quimica']) && empty($propiedades['fisica'])): ?>
        <p class="texto-muted">Solicita la ficha técnica completa para ver las propiedades de este material.</p>
      <?php endif; ?>
      <div class="fila" style="margin-top:.8rem">
        <a class="boton boton--contorno boton--sm" href="<?= $urlBase ?>/ficha-tecnica"><?= icon('file-text') ?> Ver ficha imprimible</a>
        <?php if ($proveedores !== []): ?>
          <a class="boton boton--contorno boton--sm" href="<?= url('/solicitud?tipo=cotizacion&material=' . $material['slug']) ?>"><?= icon('download') ?> Solicitar ficha y análisis</a>
        <?php endif; ?>
      </div>
    </section>

    <section class="ficha-seccion" id="proveedores">
      <h2>Proveedores de este material</h2>
      <?php if ($proveedores === []): ?>
        <p class="texto-muted">Aún no hay proveedores publicados para esta ficha. Podemos buscarlo en la red: <a href="<?= url('/soy-comprador?material=' . $material['slug']) ?>">envíanos tu requerimiento</a>.</p>
      <?php else: ?>
        <?php foreach ($proveedores as $prov): ?>
          <div class="tarjeta" style="padding:1.1rem 1.2rem;margin-bottom:.8rem">
            <div class="fila fila--separada" style="align-items:flex-start">
              <div>
                <h3 style="font-size:1rem;margin-bottom:.2rem">
                  <a href="<?= url('/proveedores/' . $prov['slug']) ?>" style="text-decoration:none;color:inherit"><?= e($prov['nombre']) ?></a>
                  <?php if ((int) $prov['verificado'] === 1): ?><span class="badge badge--ok" style="margin-left:.4rem">Verificado</span><?php endif; ?>
                  <?php if ((int) $prov['es_demo'] === 1): ?><span class="badge badge--demo" style="margin-left:.4rem">Demo</span><?php endif; ?>
                </h3>
                <p class="texto-pequeno texto-muted sin-margen"><?= e(ucfirst($prov['tipo'])) ?> · <?= e($prov['estado'] ?: 'México') ?> · <?= e($prov['capacidad'] ?: 'Capacidad a convenir') ?></p>
                <div class="fila" style="margin-top:.5rem;gap:.4rem">
                  <?= badge_disponibilidad((string) $prov['disponibilidad_oferta']) ?>
                  <?php if (!empty($prov['precio_oferta'])): ?>
                    <span class="chip"><b><?= e(money($prov['precio_oferta'], (string) $prov['moneda_oferta'])) ?></b> /<?= e($prov['unidad_oferta']) ?></span>
                  <?php endif; ?>
                  <?php if (!empty($prov['minimo'])): ?><span class="chip">Mín. <?= e($prov['minimo']) ?></span><?php endif; ?>
                </div>
              </div>
              <a class="boton boton--primario boton--sm" href="<?= url('/solicitud?tipo=cotizacion&material=' . $material['slug']) ?>"><?= icon('file-text') ?> Cotizar</a>
            </div>
            <?php if (!empty($prov['notas'])): ?><p class="texto-pequeno texto-muted" style="margin:.7rem 0 0"><?= e($prov['notas']) ?></p><?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>

    <?php if ($documentos !== []): ?>
    <section class="ficha-seccion">
      <h2>Documentos técnicos</h2>
      <div class="tabla-envoltura">
        <table class="tabla tabla--compacta">
          <tbody>
          <?php foreach ($documentos as $doc): ?>
            <tr>
              <td><?= icon('file-text') ?> <?= e($doc['nombre']) ?></td>
              <td style="text-align:right"><a class="enlace-flecha" href="<?= url('/uploads/' . $doc['archivo']) ?>" target="_blank" rel="noopener">Descargar <?= icon('download') ?></a></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
    <?php endif; ?>

    <div class="aviso-info" style="margin-top:.5rem">
      <strong>Nota técnica:</strong> los valores son típicos y no constituyen una garantía contractual. Antes de producir, solicita muestra representativa y análisis del lote. Podemos coordinar ambos con el proveedor.
    </div>
  </div>

  <aside class="layout-ficha__lateral no-print">
    <div class="caja-lateral">
      <h3>Precio de referencia</h3>
      <p style="font-size:1.7rem;font-weight:650;margin:0;font-family:var(--serif)"><?= e(money($material['precio_ref'], (string) $material['moneda'])) ?></p>
      <p class="texto-pequeno texto-muted"><?= !empty($material['precio_ref']) ? 'Por ' . e($material['unidad']) . '. Sujeto a volumen, especificación y logística.' : 'Precio a consultar según volumen y especificación.' ?></p>
      <?php if (!empty($material['precio_ref']) && ($notaPrecios = trim((string) setting('precios_nota', ''))) !== ''): ?>
        <p class="texto-mono" style="font-size:.68rem;color:var(--muted);margin-top:-.3rem"><?= e(truncate($notaPrecios, 150)) ?></p>
      <?php endif; ?>
      <a class="boton boton--primario boton--bloque" href="<?= url('/solicitud?tipo=cotizacion&material=' . $material['slug']) ?>"><?= icon('file-text') ?> Solicitar cotización</a>
      <a class="boton boton--contorno boton--bloque" href="<?= url('/solicitud?tipo=muestra&material=' . $material['slug']) ?>"><?= icon('box') ?> Pedir muestra</a>
      <a class="boton boton--fantasma boton--bloque" href="<?= e(whatsapp_url('Hola, me interesa ' . $material['nombre'] . ' (ficha: ' . url('/materiales/' . $material['slug']) . ')')) ?>" target="_blank" rel="noopener"><?= icon('whatsapp') ?> Consultar por WhatsApp</a>
    </div>

    <div class="caja-lateral">
      <h3>Datos de suministro</h3>
      <table class="tabla tabla--compacta" style="font-size:.82rem">
        <tbody>
          <tr><td class="texto-muted">Disponibilidad</td><td style="text-align:right"><?= badge_disponibilidad((string) $material['disponibilidad']) ?></td></tr>
          <tr><td class="texto-muted">Unidad</td><td style="text-align:right"><?= e($material['unidad']) ?></td></tr>
          <tr><td class="texto-muted">Región</td><td style="text-align:right"><?= e($material['region'] ?: 'Nacional') ?></td></tr>
          <tr><td class="texto-muted">Proveedores</td><td style="text-align:right"><?= count($proveedores) ?></td></tr>
        </tbody>
      </table>
    </div>

    <?php if ($calcRelacionadas !== []): ?>
    <div class="caja-lateral">
      <h3>Calculadoras útiles</h3>
      <div style="display:flex;flex-direction:column;gap:.6rem">
        <?php foreach ($calcRelacionadas as $c): ?>
          <a class="enlace-flecha texto-pequeno" href="<?= url('/calculadoras/' . $c->slug()) ?>"><?= icon('calculator') ?> <?= e($c->nombre()) ?></a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="caja-lateral" style="background:#efe8da">
      <h3>¿Necesitas otra especificación?</h3>
      <p class="texto-pequeno">Si tu proceso requiere otra granulometría, pureza o presentación, lo buscamos con la red de proveedores.</p>
      <a class="enlace-flecha texto-pequeno" href="<?= url('/soy-comprador?material=' . $material['slug']) ?>">Enviar requerimiento <?= icon('arrow-right') ?></a>
    </div>
  </aside>
</div>

<?php require APP_BASE . '/app/Views/partials/cta-movil.php'; ?>

<?php if ($relacionados !== []): ?>
<section class="seccion seccion--ajustada seccion--papel no-print">
  <div class="contenedor">
    <div class="encabezado-seccion" style="margin-bottom:1.4rem">
      <div class="encabezado-seccion__texto">
        <span class="eyebrow">Sigue explorando</span>
        <h2 style="font-size:1.55rem">Materiales relacionados</h2>
      </div>
      <a class="enlace-flecha" href="<?= url('/materiales?categoria=' . $material['categoria_slug']) ?>">Ver categoría <?= icon('arrow-right') ?></a>
    </div>
    <div class="grid grid--3">
      <?php foreach ($relacionados as $material): $claves = []; require APP_BASE . '/app/Views/partials/material-card.php'; endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
