<?php
/** Soy comprador · requerimiento. */
$val = static fn (string $k, string $defecto = ''): string => e((string) ($old[$k] ?? $defecto));
$err = static fn (string $k): string => isset($errores[$k]) ? ' campo--error' : '';
$msg = static fn (string $k): string => isset($errores[$k]) ? '<span class="mensaje-error">' . e($errores[$k]) . '</span>' : '';
?>
<div class="contenedor">
  <nav class="migajas" aria-label="Ruta"><a href="<?= url('/') ?>">Inicio</a><span class="sep">/</span><span>Soy comprador</span></nav>
</div>
<section class="seccion seccion--ajustada" style="padding-top:.8rem">
  <div class="contenedor">
    <div class="grid grid--2" style="gap:2.4rem;align-items:start">
      <div>
        <span class="eyebrow">Requerimiento de compra</span>
        <h1 style="font-size:clamp(1.8rem,3.4vw,2.6rem);margin-bottom:.6rem">Soy comprador</h1>
        <p style="max-width:56ch">Dinos qué mineral necesitas, en qué volumen y para qué aplicación. Validamos la factibilidad con la red de proveedores y te respondemos con alternativas, muestras y precio de referencia.</p>
        <div class="grid grid--2" style="margin-top:1.6rem">
          <div class="paso" style="background:#f1ece1"><span class="texto-mono" style="color:var(--tierra)">01</span><h3 style="font-size:.95rem;margin-top:.3rem">Requerimiento</h3><p>Recibimos especificación, volumen y destino.</p></div>
          <div class="paso" style="background:#f1ece1"><span class="texto-mono" style="color:var(--tierra)">02</span><h3 style="font-size:.95rem;margin-top:.3rem">Validación</h3><p>Contrastamos contra el proceso y detectamos riesgos.</p></div>
          <div class="paso" style="background:#f1ece1"><span class="texto-mono" style="color:var(--tierra)">03</span><h3 style="font-size:.95rem;margin-top:.3rem">Muestra</h3><p>Coordinamos muestra representativa del lote.</p></div>
          <div class="paso" style="background:#f1ece1"><span class="texto-mono" style="color:var(--tierra)">04</span><h3 style="font-size:.95rem;margin-top:.3rem">Cotización</h3><p>Oferta con logística y documentación.</p></div>
        </div>
        <div class="aviso-info" style="margin-top:1.4rem"><?= icon('shield') ?> Tu información se usa solo para atender el requerimiento. No compartimos tu identidad con proveedores sin tu autorización.</div>
      </div>

      <form class="formulario tarjeta" style="padding:1.6rem" method="post" action="<?= url('/soy-comprador') ?>">
        <?= csrf_field() ?>
        <div class="honeypot"><label>Sitio web<input type="text" name="sitio_web" tabindex="-1" autocomplete="off"></label></div>
        <h2 style="font-size:1.05rem;margin:0">Datos de contacto</h2>
        <div class="fila-campos">
          <div class="campo<?= $err('nombre') ?>"><label for="nombre">Nombre *</label><input id="nombre" name="nombre" type="text" value="<?= $val('nombre') ?>" required><?= $msg('nombre') ?></div>
          <div class="campo<?= $err('empresa') ?>"><label for="empresa">Empresa *</label><input id="empresa" name="empresa" type="text" value="<?= $val('empresa') ?>" required><?= $msg('empresa') ?></div>
          <div class="campo"><label for="cargo">Cargo</label><input id="cargo" name="cargo" type="text" value="<?= $val('cargo') ?>"></div>
          <div class="campo<?= $err('email') ?>"><label for="email">Correo *</label><input id="email" name="email" type="email" value="<?= $val('email') ?>" required><?= $msg('email') ?></div>
          <div class="campo"><label for="telefono">Teléfono</label><input id="telefono" name="telefono" type="tel" value="<?= $val('telefono') ?>"></div>
          <div class="campo"><label for="whatsapp">WhatsApp</label><input id="whatsapp" name="whatsapp" type="tel" value="<?= $val('whatsapp') ?>"></div>
        </div>

        <h2 style="font-size:1.05rem;margin:.6rem 0 0">Requerimiento</h2>
        <div class="campo<?= $err('material_texto') ?>">
          <label for="material_texto">Mineral o material *</label>
          <input id="material_texto" name="material_texto" type="text" value="<?= $val('material_texto', $material['nombre'] ?? '') ?>" placeholder="Ej. caolín cerámico Al₂O₃ ≥ 36 %, bentonita sódica" required>
          <?= $msg('material_texto') ?>
        </div>
        <div class="fila-campos">
          <div class="campo<?= $err('volumen') ?>"><label for="volumen">Volumen estimado *</label><input id="volumen" name="volumen" type="text" value="<?= $val('volumen') ?>" placeholder="Ej. 50 t/mes" required><?= $msg('volumen') ?></div>
          <div class="campo"><label for="unidad">Unidad</label>
            <select id="unidad" name="unidad"><?php foreach (['tonelada', 'kg', 'saco', 'big bag', 'contenedor'] as $u): ?><option value="<?= $u ?>" <?= ($old['unidad'] ?? '') === $u ? 'selected' : '' ?>><?= ucfirst($u) ?></option><?php endforeach; ?></select>
          </div>
          <div class="campo"><label for="destino">Destino / planta</label><input id="destino" name="destino" type="text" value="<?= $val('destino') ?>"></div>
          <div class="campo"><label for="frecuencia">Frecuencia</label>
            <select id="frecuencia" name="frecuencia"><?php foreach (['Única', 'Mensual', 'Trimestral', 'Semestral', 'Anual', 'Por definir'] as $f): ?><option value="<?= $f ?>" <?= ($old['frecuencia'] ?? '') === $f ? 'selected' : '' ?>><?= $f ?></option><?php endforeach; ?></select>
          </div>
        </div>
        <div class="campo campo--completo">
          <label for="especificaciones">Especificaciones</label>
          <textarea id="especificaciones" name="especificaciones" rows="3" placeholder="Composición, granulometría, humedad, blancura, refractariedad, empaque…"><?= $val('especificaciones') ?></textarea>
        </div>
        <div class="campo campo--completo">
          <label for="mensaje">Contexto del proceso</label>
          <textarea id="mensaje" name="mensaje" rows="3" placeholder="Producto que fabricas, restricciones técnicas, fechas de entrega."><?= $val('mensaje') ?></textarea>
        </div>
        <div class="consentimiento">
          <label class="check<?= $err('consentimiento') ?>"><input type="checkbox" name="consentimiento" value="1" <?= !empty($old['consentimiento']) ? 'checked' : '' ?> required><span>Acepto el <a href="<?= url('/aviso-de-privacidad') ?>" target="_blank" rel="noopener">aviso de privacidad</a>. *</span></label>
          <?= $msg('consentimiento') ?>
        </div>
        <button class="boton boton--primario boton--lg" type="submit">Enviar requerimiento <?= icon('arrow-right') ?></button>
      </form>
    </div>
  </div>
</section>
