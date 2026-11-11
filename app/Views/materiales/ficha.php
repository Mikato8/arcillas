<?php
/** Ficha técnica imprimible. @var array<string,mixed> $material */
$gruposEtiquetas = [
    'quimica'       => 'Composición química',
    'fisica'        => 'Propiedades físicas',
    'granulometria' => 'Granulometría y humedad',
    'presentacion'  => 'Presentación y empaque',
];
?>
<div style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;border-bottom:2px solid #2b2621;padding-bottom:14px;margin-bottom:22px">
  <div>
    <div style="font-family:var(--mono);font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:#6f665a">Ficha técnica de material</div>
    <h1 style="font-size:26px;margin:6px 0 4px"><?= e($material['nombre']) ?></h1>
    <p style="margin:0;font-size:12px;color:#6f665a"><?= e($material['categoria_nombre']) ?> · <?= e($material['region'] ?: 'México') ?> · Emitida el <?= date('d/m/Y') ?></p>
  </div>
  <div style="text-align:right;font-size:11px;color:#6f665a">
    <strong style="color:#2b2621">ARCILLAS MX</strong><br>
    <?= e(empresa_nombre()) ?><br>
    RFC <?= e(setting('empresa_rfc', 'APD200702M63')) ?><br>
    <?= e(setting('empresa_email', 'contacto@arcillas.mx')) ?>
  </div>
</div>

<p style="font-size:13px;line-height:1.6"><?= nl2br(e((string) $material['descripcion'])) ?></p>

<?php if (!empty($material['aplicaciones'])): ?>
  <h2 style="font-size:14px;text-transform:uppercase;letter-spacing:.08em;margin-top:20px;border-bottom:1px solid #e3dccd;padding-bottom:4px">Aplicaciones</h2>
  <p style="font-size:12px;line-height:1.6"><?= nl2br(e((string) $material['aplicaciones'])) ?></p>
<?php endif; ?>

<?php foreach ($gruposEtiquetas as $grupo => $etiqueta): ?>
  <?php if (!empty($propiedades[$grupo])): ?>
    <h2 style="font-size:14px;text-transform:uppercase;letter-spacing:.08em;margin-top:20px;border-bottom:1px solid #e3dccd;padding-bottom:4px"><?= e($etiqueta) ?></h2>
    <table style="width:100%;border-collapse:collapse;font-size:12px">
      <?php foreach ($propiedades[$grupo] as $prop): ?>
        <tr>
          <td style="padding:5px 8px;border-bottom:1px solid #eee7da;color:#6f665a;width:58%"><?= e($prop['nombre']) ?></td>
          <td style="padding:5px 8px;border-bottom:1px solid #eee7da;text-align:right;font-family:var(--mono)"><?= e(prop_valor($prop)) ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
<?php endforeach; ?>

<h2 style="font-size:14px;text-transform:uppercase;letter-spacing:.08em;margin-top:20px;border-bottom:1px solid #e3dccd;padding-bottom:4px">Datos comerciales</h2>
<table style="width:100%;border-collapse:collapse;font-size:12px">
  <tr><td style="padding:5px 8px;color:#6f665a;width:58%">Precio de referencia</td><td style="padding:5px 8px;text-align:right;font-family:var(--mono)"><?= e(money($material['precio_ref'], (string) $material['moneda'])) ?></td></tr>
  <tr><td style="padding:5px 8px;color:#6f665a">Unidad</td><td style="padding:5px 8px;text-align:right"><?= e($material['unidad']) ?></td></tr>
  <tr><td style="padding:5px 8px;color:#6f665a">Presentación</td><td style="padding:5px 8px;text-align:right"><?= e($material['presentacion'] ?: 'A convenir') ?></td></tr>
  <tr><td style="padding:5px 8px;color:#6f665a">Disponibilidad</td><td style="padding:5px 8px;text-align:right"><?= e(ucfirst(str_replace('_', ' ', (string) $material['disponibilidad']))) ?></td></tr>
</table>

<?php if ($proveedores !== []): ?>
  <h2 style="font-size:14px;text-transform:uppercase;letter-spacing:.08em;margin-top:20px;border-bottom:1px solid #e3dccd;padding-bottom:4px">Proveedores en el directorio</h2>
  <table style="width:100%;border-collapse:collapse;font-size:12px">
    <?php foreach ($proveedores as $prov): ?>
      <tr>
        <td style="padding:5px 8px;border-bottom:1px solid #eee7da"><?= e($prov['nombre']) ?></td>
        <td style="padding:5px 8px;border-bottom:1px solid #eee7da"><?= e(ucfirst($prov['tipo'])) ?> · <?= e($prov['estado'] ?: 'México') ?></td>
        <td style="padding:5px 8px;border-bottom:1px solid #eee7da;text-align:right"><?= e(ucfirst(str_replace('_', ' ', (string) $prov['disponibilidad_oferta']))) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>

<div style="margin-top:26px;padding-top:12px;border-top:1px solid #e3dccd;font-size:10px;color:#6f665a;line-height:1.6">
  <strong>Nota:</strong> los valores son típicos de referencia y no constituyen garantía contractual. La especificación definitiva se acuerda por lote y se respalda con análisis de laboratorio. Solicita muestra representativa antes de producción. Documento generado desde arcillas.mx el <?= date('d/m/Y H:i') ?>.
</div>
