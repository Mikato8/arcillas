<?php
/** Admin · formulario de material. */
$m = $material;
$val = static fn (string $k, $defecto = ''): string => e((string) ($old[$k] ?? $m[$k] ?? $defecto));
$sel = static fn (string $k, string $v): string => ((string) ($old[$k] ?? $m[$k] ?? '') === $v) ? 'selected' : '';
$chk = static fn (string $k): string => !empty($old[$k] ?? $m[$k]) ? 'checked' : '';
?>
<?php if ($errores !== []): ?>
  <div class="aviso-error" style="margin-bottom:1rem"><?= icon('alert') ?> Corrige: <?= e(implode(' ', $errores)) ?></div>
<?php endif; ?>

<form class="form-admin" method="post" action="<?= url('/admin/materiales/guardar') ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">

  <div class="admin-panel">
    <h2>Datos generales</h2>
    <div class="fila-campos">
      <div class="campo"><label for="nombre">Nombre *</label><input id="nombre" name="nombre" type="text" value="<?= $val('nombre') ?>" required></div>
      <div class="campo"><label for="slug">Slug (URL)</label><input id="slug" name="slug" type="text" value="<?= $val('slug') ?>" placeholder="se genera del nombre si lo dejas vacío"></div>
      <div class="campo"><label for="category_id">Categoría *</label>
        <select id="category_id" name="category_id" required>
          <option value="">Selecciona…</option>
          <?php foreach ($categorias as $cat): ?>
            <option value="<?= (int) $cat['id'] ?>" <?= (int) ($old['category_id'] ?? $m['category_id']) === (int) $cat['id'] ? 'selected' : '' ?>><?= e($cat['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="campo"><label for="origen">Origen</label><input id="origen" name="origen" type="text" value="<?= $val('origen') ?>" placeholder="Ej. Guanajuato, México"></div>
      <div class="campo campo--completo"><label for="resumen">Resumen (máx. 160 caracteres, aparece en tarjetas y SEO)</label><textarea id="resumen" name="resumen" rows="2" maxlength="320"><?= $val('resumen') ?></textarea></div>
      <div class="campo campo--completo"><label for="descripcion">Descripción</label><textarea id="descripcion" name="descripcion" rows="5"><?= $val('descripcion') ?></textarea></div>
      <div class="campo campo--completo"><label for="aplicaciones">Aplicaciones industriales</label><textarea id="aplicaciones" name="aplicaciones" rows="3"><?= $val('aplicaciones') ?></textarea></div>
    </div>
  </div>

  <div class="admin-panel">
    <h2>Datos comerciales</h2>
    <div class="fila-campos">
      <div class="campo"><label for="precio_ref">Precio de referencia</label><input id="precio_ref" name="precio_ref" type="number" step="0.01" value="<?= $val('precio_ref') ?>"></div>
      <div class="campo"><label for="moneda">Moneda</label><select id="moneda" name="moneda"><option value="MXN" <?= $sel('moneda', 'MXN') ?>>MXN</option><option value="USD" <?= $sel('moneda', 'USD') ?>>USD</option></select></div>
      <div class="campo"><label for="unidad">Unidad</label><select id="unidad" name="unidad"><?php foreach (['tonelada', 'kg', 'saco', 'big bag', 'pieza', 'contenedor'] as $u): ?><option value="<?= $u ?>" <?= $sel('unidad', $u) ?>><?= ucfirst($u) ?></option><?php endforeach; ?></select></div>
      <div class="campo"><label for="disponibilidad">Disponibilidad</label>
        <select id="disponibilidad" name="disponibilidad"><?php foreach (['inmediata' => 'Inmediata', 'programada' => 'Programada', 'bajo_pedido' => 'Bajo pedido', 'agotado' => 'Agotado'] as $k => $v): ?><option value="<?= $k ?>" <?= $sel('disponibilidad', $k) ?>><?= $v ?></option><?php endforeach; ?></select>
      </div>
      <div class="campo"><label for="presentacion">Presentación</label><input id="presentacion" name="presentacion" type="text" value="<?= $val('presentacion') ?>" placeholder="Sacos 25 kg, big bag 1 t, granel"></div>
      <div class="campo"><label for="region">Región de suministro</label><input id="region" name="region" type="text" value="<?= $val('region') ?>"></div>
      <div class="campo"><label for="imagen">Imagen (JPG/PNG/WEBP/SVG, máx 3 MB)</label><input id="imagen" name="imagen" type="file" accept="image/*"></div>
      <div class="campo">
        <label>Visibilidad</label>
        <label class="check"><input type="checkbox" name="activo" value="1" <?= $chk('activo') ?>> Activo en el sitio</label>
        <label class="check"><input type="checkbox" name="destacado" value="1" <?= $chk('destacado') ?>> Destacado en portada</label>
        <label class="check"><input type="checkbox" name="es_demo" value="1" <?= $chk('es_demo') ?>> Marcar como dato demo</label>
      </div>
    </div>
  </div>

  <div class="admin-panel">
    <div class="fila fila--separada" style="margin-bottom:.8rem">
      <h2 style="margin:0">Propiedades técnicas</h2>
      <button class="boton boton--contorno boton--sm" type="button" data-agregar-prop><?= icon('plus') ?> Agregar fila</button>
    </div>
    <p class="texto-pequeno texto-muted" style="margin-top:0">Claves sugeridas: sio2, al2o3, fe2o3, tio2, k2o, na2o, cao, mgo, loi, ph, densidad, blancura, plasticidad, mbi, humedad, d50, malla_200, malla_325, refractario, mohs, hinchamiento.</p>
    <div id="props-lista">
      <?php foreach ($props as $i => $prop): ?>
        <div class="propiedad-fila" data-prop-fila>
          <select name="props[<?= (int) $i ?>][grupo]" aria-label="Grupo">
            <?php foreach (['quimica' => 'Química', 'fisica' => 'Física', 'granulometria' => 'Granulometría', 'presentacion' => 'Presentación'] as $k => $v): ?>
              <option value="<?= $k ?>" <?= (string) ($prop['grupo'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
            <?php endforeach; ?>
          </select>
          <input name="props[<?= (int) $i ?>][clave]" placeholder="clave" value="<?= e((string) ($prop['clave'] ?? '')) ?>" aria-label="Clave">
          <input name="props[<?= (int) $i ?>][nombre]" placeholder="Nombre legible" value="<?= e((string) ($prop['nombre'] ?? '')) ?>" aria-label="Nombre">
          <input name="props[<?= (int) $i ?>][valor]" type="number" step="any" placeholder="Valor" value="<?= e((string) ($prop['valor'] ?? '')) ?>" aria-label="Valor">
          <input name="props[<?= (int) $i ?>][unidad]" placeholder="Unidad" value="<?= e((string) ($prop['unidad'] ?? '')) ?>" aria-label="Unidad">
          <input name="props[<?= (int) $i ?>][valor_texto]" placeholder="Texto" value="<?= e((string) ($prop['valor_texto'] ?? '')) ?>" aria-label="Valor texto">
          <button type="button" class="quitar" data-quitar-prop aria-label="Quitar fila">×</button>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="admin-panel">
    <h2>Proveedores e industrias</h2>
    <div class="grid grid--2" style="gap:1.4rem">
      <div>
        <h3 style="font-size:.8rem;text-transform:uppercase;letter-spacing:.08em;color:var(--muted)">Proveedores</h3>
        <?php foreach ($proveedores as $prov): ?>
          <label class="check" style="margin-bottom:.35rem"><input type="checkbox" name="suppliers[]" value="<?= (int) $prov['id'] ?>" <?= in_array((int) $prov['id'], array_map('intval', $supplier_ids), true) ? 'checked' : '' ?>> <?= e($prov['nombre']) ?> <small class="texto-muted">(<?= e($prov['estado'] ?: 'México') ?>)</small></label>
        <?php endforeach; ?>
        <?php if ($proveedores === []): ?><p class="texto-pequeno texto-muted">Crea proveedores primero.</p><?php endif; ?>
      </div>
      <div>
        <h3 style="font-size:.8rem;text-transform:uppercase;letter-spacing:.08em;color:var(--muted)">Industrias</h3>
        <?php foreach ($industrias as $ind): ?>
          <label class="check" style="margin-bottom:.35rem"><input type="checkbox" name="industrias[]" value="<?= (int) $ind['id'] ?>" <?= in_array((int) $ind['id'], array_map('intval', $industria_ids), true) ? 'checked' : '' ?>> <?= e($ind['nombre']) ?></label>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="admin-panel">
    <h2>SEO</h2>
    <div class="fila-campos">
      <div class="campo campo--completo"><label for="meta_title">Meta título</label><input id="meta_title" name="meta_title" type="text" value="<?= $val('meta_title') ?>"></div>
      <div class="campo campo--completo"><label for="meta_description">Meta descripción</label><textarea id="meta_description" name="meta_description" rows="2"><?= $val('meta_description') ?></textarea></div>
    </div>
  </div>

  <div class="fila">
    <button class="boton boton--primario" type="submit"><?= icon('save') ?> Guardar material</button>
    <a class="boton boton--contorno" href="<?= url('/admin/materiales') ?>">Cancelar</a>
  </div>
</form>

<template id="prop-plantilla">
  <div class="propiedad-fila" data-prop-fila>
    <select name="props[__IDX__][grupo]" aria-label="Grupo">
      <option value="quimica">Química</option><option value="fisica">Física</option><option value="granulometria">Granulometría</option><option value="presentacion">Presentación</option>
    </select>
    <input name="props[__IDX__][clave]" placeholder="clave" aria-label="Clave">
    <input name="props[__IDX__][nombre]" placeholder="Nombre legible" aria-label="Nombre">
    <input name="props[__IDX__][valor]" type="number" step="any" placeholder="Valor" aria-label="Valor">
    <input name="props[__IDX__][unidad]" placeholder="Unidad" aria-label="Unidad">
    <input name="props[__IDX__][valor_texto]" placeholder="Texto" aria-label="Valor texto">
    <button type="button" class="quitar" data-quitar-prop aria-label="Quitar fila">×</button>
  </div>
</template>
