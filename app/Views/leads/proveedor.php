<?php
/** Soy proveedor · oferta. */
$val = static fn (string $k, string $defecto = ''): string => e((string) ($old[$k] ?? $defecto));
$err = static fn (string $k): string => isset($errores[$k]) ? ' campo--error' : '';
$msg = static fn (string $k): string => isset($errores[$k]) ? '<span class="mensaje-error">' . e($errores[$k]) . '</span>' : '';
?>
<div class="contenedor">
  <nav class="migajas" aria-label="Ruta"><a href="<?= url('/') ?>">Inicio</a><span class="sep">/</span><span>Soy proveedor</span></nav>
</div>
<section class="seccion seccion--ajustada" style="padding-top:.8rem">
  <div class="contenedor">
    <div class="grid grid--2" style="gap:2.4rem;align-items:start">
      <div>
        <span class="eyebrow">Oferta de suministro</span>
        <h1 style="font-size:clamp(1.8rem,3.4vw,2.6rem);margin-bottom:.6rem">Soy proveedor</h1>
        <p style="max-width:56ch">Si tienes mina, planta o inventario de arcillas o minerales no ferrosos, intégrate al directorio. Publicamos tu material con ficha técnica y te conectamos con compradores industriales calificados.</p>
        <div class="tarjeta" style="padding:1.3rem;margin-top:1.4rem">
          <h3 style="font-size:1rem">Qué revisamos</h3>
          <ul style="margin:.5rem 0 0;padding-left:1.1rem;font-size:.88rem;color:var(--tinta-suave)">
            <li>Ubicación y capacidad de mina o planta.</li>
            <li>Análisis químicos y granulometría del material.</li>
            <li>Documentación: permisos, pedimento, certificados de calidad.</li>
            <li>Condiciones comerciales: mínimo, empaque, entrega, crédito.</li>
          </ul>
        </div>
        <div class="aviso-info" style="margin-top:1rem">No publicamos tu información de contacto ni tus precios sin autorización.</div>
      </div>

      <form class="formulario tarjeta" style="padding:1.6rem" method="post" action="<?= url('/soy-proveedor') ?>">
        <?= csrf_field() ?>
        <div class="honeypot"><label>Sitio web<input type="text" name="sitio_web" tabindex="-1" autocomplete="off"></label></div>
        <div class="fila-campos">
          <div class="campo<?= $err('nombre') ?>"><label for="nombre">Nombre *</label><input id="nombre" name="nombre" type="text" value="<?= $val('nombre') ?>" required><?= $msg('nombre') ?></div>
          <div class="campo<?= $err('empresa') ?>"><label for="empresa">Empresa *</label><input id="empresa" name="empresa" type="text" value="<?= $val('empresa') ?>" required><?= $msg('empresa') ?></div>
          <div class="campo"><label for="cargo">Cargo</label><input id="cargo" name="cargo" type="text" value="<?= $val('cargo') ?>"></div>
          <div class="campo<?= $err('email') ?>"><label for="email">Correo *</label><input id="email" name="email" type="email" value="<?= $val('email') ?>" required><?= $msg('email') ?></div>
          <div class="campo"><label for="telefono">Teléfono</label><input id="telefono" name="telefono" type="tel" value="<?= $val('telefono') ?>"></div>
          <div class="campo"><label for="whatsapp">WhatsApp</label><input id="whatsapp" name="whatsapp" type="tel" value="<?= $val('whatsapp') ?>"></div>
        </div>
        <div class="campo<?= $err('material_texto') ?>">
          <label for="material_texto">Mineral o material que ofreces *</label>
          <input id="material_texto" name="material_texto" type="text" value="<?= $val('material_texto') ?>" placeholder="Ej. bentonita sódica, caolín cerámico, concentrado de cobre" required>
          <?= $msg('material_texto') ?>
        </div>
        <div class="fila-campos">
          <div class="campo<?= $err('destino') ?>"><label for="destino">Ubicación de mina/planta *</label><input id="destino" name="destino" type="text" value="<?= $val('destino') ?>" placeholder="Municipio, estado" required><?= $msg('destino') ?></div>
          <div class="campo"><label for="volumen">Capacidad mensual</label><input id="volumen" name="volumen" type="text" value="<?= $val('volumen') ?>" placeholder="Ej. 2,000 t/mes"></div>
          <div class="campo"><label for="especificaciones">Especificaciones</label><textarea id="especificaciones" name="especificaciones" rows="2" placeholder="Leyes, granulometría, humedad, presentación"><?= $val('especificaciones') ?></textarea></div>
          <div class="campo"><label for="unidad">Documentación disponible</label><select id="unidad" name="unidad">
            <option value="">Selecciona…</option>
            <?php foreach (['Análisis de laboratorio', 'Permisos y concesiones', 'Certificado de calidad', 'Pedimentos de importación', 'Ninguna por ahora'] as $d): ?>
              <option value="<?= $d ?>" <?= ($old['unidad'] ?? '') === $d ? 'selected' : '' ?>><?= $d ?></option>
            <?php endforeach; ?></select>
          </div>
        </div>
        <div class="campo campo--completo<?= $err('mensaje') ?>">
          <label for="mensaje">Cuéntanos de tu operación *</label>
          <textarea id="mensaje" name="mensaje" rows="4" placeholder="Tipo de yacimiento, proceso, clientes actuales, condiciones de entrega." required><?= $val('mensaje') ?></textarea>
          <?= $msg('mensaje') ?>
        </div>
        <div class="consentimiento">
          <label class="check<?= $err('consentimiento') ?>"><input type="checkbox" name="consentimiento" value="1" <?= !empty($old['consentimiento']) ? 'checked' : '' ?> required><span>Acepto el <a href="<?= url('/aviso-de-privacidad') ?>" target="_blank" rel="noopener">aviso de privacidad</a>. *</span></label>
          <?= $msg('consentimiento') ?>
        </div>
        <button class="boton boton--primario boton--lg" type="submit">Enviar oferta <?= icon('arrow-right') ?></button>
      </form>
    </div>
  </div>
</section>
