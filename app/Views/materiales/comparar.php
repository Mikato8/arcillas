<?php
/** Comparador de materiales. */
$grupos = [
    'quimica'       => 'Composición química',
    'fisica'        => 'Propiedades físicas',
    'granulometria' => 'Granulometría y humedad',
    'presentacion'  => 'Presentación',
];
// Unión ordenada de claves presentes en los materiales comparados.
$claves = [];
foreach ($materiales as $mat) {
    foreach ($mat['props'] as $clave => $prop) {
        if (!isset($claves[$clave])) {
            $claves[$clave] = [
                'clave'  => $clave,
                'grupo'  => $prop['grupo'],
                'nombre' => $prop['nombre'],
                'unidad' => $prop['unidad'],
            ];
        }
    }
}
$porGrupo = [];
foreach ($claves as $clave => $info) {
    $porGrupo[$info['grupo']][$clave] = $info;
}
$slugs = array_column($materiales, 'slug');
?>
<div class="contenedor">
  <nav class="migajas" aria-label="Ruta">
    <a href="<?= url('/') ?>">Inicio</a><span class="sep">/</span>
    <a href="<?= url('/materiales') ?>">Materiales</a><span class="sep">/</span><span>Comparar</span>
  </nav>
</div>

<section class="seccion seccion--ajustada" style="padding-top:1.2rem">
  <div class="contenedor">
    <div class="encabezado-seccion">
      <div class="encabezado-seccion__texto">
        <span class="eyebrow">Comparador técnico</span>
        <h2>Compara hasta 4 materiales</h2>
        <p>Contrasta composición, propiedades físicas, precio de referencia y disponibilidad en una sola tabla.</p>
      </div>
      <a class="boton boton--contorno" href="<?= url('/materiales') ?>"><?= icon('plus') ?> Agregar materiales</a>
    </div>

    <?php if ($materiales === []): ?>
      <div class="tarjeta" style="padding:3rem 2rem;text-align:center">
        <span class="tarjeta-calc__icono" style="margin:0 auto 1rem"><?= icon('scale') ?></span>
        <h3>Aún no hay materiales en la comparación</h3>
        <p class="texto-muted">En el catálogo, usa el botón <strong>Comparar</strong> de cada tarjeta. Puedes seleccionar hasta cuatro materiales.</p>
        <div class="fila" style="justify-content:center">
          <a class="boton boton--primario" href="<?= url('/materiales') ?>"><?= icon('layers') ?> Ir al catálogo</a>
        </div>
      </div>
    <?php else: ?>
      <div class="tarjeta" style="padding:1.1rem 1.2rem;margin-bottom:1.4rem">
        <div class="comparar-cabecera">
          <div class="texto-pequeno texto-muted">Material</div>
          <?php for ($i = 0; $i < 4; $i++): ?>
            <?php if (isset($materiales[$i])): $m = $materiales[$i]; ?>
              <div class="comparar-tarjeta">
                <img src="<?= e(material_imagen($m)) ?>" alt="" width="300" height="225">
                <h3><a href="<?= url('/materiales/' . $m['slug']) ?>" style="text-decoration:none;color:inherit"><?= e($m['nombre']) ?></a></h3>
                <p class="texto-mono" style="color:var(--muted);margin:0"><?= e($m['categoria_nombre']) ?></p>
                <a class="filtro-chip" style="margin-top:.5rem;display:inline-flex" href="<?= url('/comparar?ids=' . implode(',', array_diff($slugs, [$m['slug']]))) ?>"><?= icon('x') ?> Quitar</a>
              </div>
            <?php else: ?>
              <a class="comparar-vacio" href="<?= url('/materiales') ?>">+ Agregar material</a>
            <?php endif; ?>
          <?php endfor; ?>
        </div>
      </div>

      <div class="tabla-envoltura">
        <table class="tabla tabla--comparar">
          <thead>
            <tr><th>Dato</th><?php foreach ($materiales as $m): ?><th><?= e($m['nombre']) ?></th><?php endforeach; ?></tr>
          </thead>
          <tbody>
            <tr><td>Categoría</td><?php foreach ($materiales as $m): ?><td><?= e($m['categoria_nombre']) ?></td><?php endforeach; ?></tr>
            <tr><td>Disponibilidad</td><?php foreach ($materiales as $m): ?><td><?= badge_disponibilidad((string) $m['disponibilidad']) ?></td><?php endforeach; ?></tr>
            <tr><td>Precio de referencia</td><?php foreach ($materiales as $m): ?><td><?= e(money($m['precio_ref'], (string) $m['moneda'])) ?><?= !empty($m['precio_ref']) ? ' /' . e($m['unidad']) : '' ?></td><?php endforeach; ?></tr>
            <tr><td>Región</td><?php foreach ($materiales as $m): ?><td><?= e($m['region'] ?: 'Nacional') ?></td><?php endforeach; ?></tr>
            <tr><td>Presentación</td><?php foreach ($materiales as $m): ?><td><?= e($m['presentacion'] ?: 'A convenir') ?></td><?php endforeach; ?></tr>
            <?php foreach ($grupos as $grupo => $etiqueta): ?>
              <?php if (!empty($porGrupo[$grupo])): ?>
                <tr><td colspan="<?= count($materiales) + 1 ?>" style="background:var(--fondo-alt);font-family:var(--mono);font-size:.7rem;text-transform:uppercase;letter-spacing:.1em;color:var(--muted)"><?= e($etiqueta) ?></td></tr>
                <?php foreach ($porGrupo[$grupo] as $clave => $info): ?>
                  <tr>
                    <td><?= e($info['nombre']) ?><?= $info['unidad'] ? ' <span class="texto-mono">(' . e($info['unidad']) . ')</span>' : '' ?></td>
                    <?php foreach ($materiales as $m): ?>
                      <td style="font-family:var(--mono);font-size:.82rem"><?= isset($m['props'][$clave]) ? e(prop_valor($m['props'][$clave])) : '—' ?></td>
                    <?php endforeach; ?>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="tarjeta" style="padding:1.2rem 1.4rem;margin-top:1.4rem;background:#f2eee4">
        <div class="fila fila--separada">
          <div>
            <h3 style="font-size:1rem;margin-bottom:.2rem">¿Quieres muestras de estos materiales?</h3>
            <p class="texto-pequeno texto-muted sin-margen">Coordinamos una sola solicitud con muestras representativas de cada proveedor.</p>
          </div>
          <a class="boton boton--primario" href="<?= url('/solicitud?tipo=muestra&items=' . implode(',', array_map('urlencode', $slugs))) ?>"><?= icon('box') ?> Solicitar muestras</a>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>
