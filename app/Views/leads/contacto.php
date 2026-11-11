<?php
/** Contacto. */
$val = static fn (string $k, string $defecto = ''): string => e((string) ($old[$k] ?? $defecto));
$err = static fn (string $k): string => isset($errores[$k]) ? ' campo--error' : '';
$msg = static fn (string $k): string => isset($errores[$k]) ? '<span class="mensaje-error">' . e($errores[$k]) . '</span>' : '';
?>
<div class="contenedor">
  <nav class="migajas" aria-label="Ruta"><a href="<?= url('/') ?>">Inicio</a><span class="sep">/</span><span>Contacto</span></nav>
</div>
<section class="seccion seccion--ajustada" style="padding-top:.8rem">
  <div class="contenedor">
    <div class="encabezado-seccion" style="margin-bottom:1.6rem">
      <div class="encabezado-seccion__texto">
        <span class="eyebrow">Hablemos</span>
        <h1 style="font-size:clamp(1.8rem,3.4vw,2.6rem);margin-bottom:.4rem">Contacto</h1>
        <p>Cuéntanos qué mineral necesitas o qué material ofreces. Respondemos en menos de <?= e(setting('respuesta_horas', '24')) ?> horas hábiles.</p>
      </div>
    </div>
    <div class="grid grid--2" style="gap:2rem;align-items:start">
      <form class="formulario tarjeta" style="padding:1.6rem" method="post" action="<?= url('/contacto') ?>">
        <?= csrf_field() ?>
        <div class="honeypot"><label>Sitio web<input type="text" name="sitio_web" tabindex="-1" autocomplete="off"></label></div>
        <div class="fila-campos">
          <div class="campo<?= $err('nombre') ?>">
            <label for="nombre">Nombre *</label>
            <input id="nombre" name="nombre" type="text" value="<?= $val('nombre') ?>" required>
            <?= $msg('nombre') ?>
          </div>
          <div class="campo<?= $err('email') ?>">
            <label for="email">Correo *</label>
            <input id="email" name="email" type="email" value="<?= $val('email') ?>" required>
            <?= $msg('email') ?>
          </div>
          <div class="campo">
            <label for="empresa">Empresa</label>
            <input id="empresa" name="empresa" type="text" value="<?= $val('empresa') ?>">
          </div>
          <div class="campo">
            <label for="telefono">Teléfono</label>
            <input id="telefono" name="telefono" type="tel" value="<?= $val('telefono') ?>">
          </div>
        </div>
        <div class="campo campo--completo">
          <label for="material_texto">Mineral o tema</label>
          <input id="material_texto" name="material_texto" type="text" value="<?= $val('material_texto') ?>" placeholder="Ej. caolín, bentonita, información general">
        </div>
        <div class="campo campo--completo<?= $err('mensaje') ?>">
          <label for="mensaje">Mensaje *</label>
          <textarea id="mensaje" name="mensaje" rows="5" required><?= $val('mensaje') ?></textarea>
          <?= $msg('mensaje') ?>
        </div>
        <div class="consentimiento">
          <label class="check<?= $err('consentimiento') ?>">
            <input type="checkbox" name="consentimiento" value="1" <?= !empty($old['consentimiento']) ? 'checked' : '' ?> required>
            <span>Acepto el <a href="<?= url('/aviso-de-privacidad') ?>" target="_blank" rel="noopener">aviso de privacidad</a>. *</span>
          </label>
          <?= $msg('consentimiento') ?>
        </div>
        <button class="boton boton--primario boton--lg" type="submit">Enviar mensaje <?= icon('arrow-right') ?></button>
      </form>

      <div style="display:flex;flex-direction:column;gap:1rem">
        <div class="tarjeta" style="padding:1.4rem">
          <h3 style="font-size:1rem">Datos de contacto</h3>
          <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:.8rem;font-size:.9rem">
            <li class="fila" style="gap:.6rem"><?= icon('mail') ?><a href="mailto:<?= e(setting('empresa_email', 'contacto@arcillas.mx')) ?>"><?= e(setting('empresa_email', 'contacto@arcillas.mx')) ?></a></li>
            <li class="fila" style="gap:.6rem"><?= icon('phone') ?><a href="tel:<?= e(preg_replace('/\s+/', '', (string) setting('empresa_telefono', ''))) ?>"><?= e(setting('empresa_telefono', '+52 55 0000 0000')) ?></a></li>
            <li class="fila" style="gap:.6rem"><?= icon('whatsapp') ?><a href="<?= e(whatsapp_url('Hola, quiero información.')) ?>" target="_blank" rel="noopener">WhatsApp directo</a></li>
            <li class="fila" style="gap:.6rem"><?= icon('pin') ?><span><?= e(setting('empresa_direccion', 'Ciudad de México, México')) ?></span></li>
            <li class="fila" style="gap:.6rem"><?= icon('clock') ?><span>Lunes a viernes, 9:00–18:00 h (CDMX)</span></li>
          </ul>
        </div>
        <div class="tarjeta" style="padding:1.4rem;background:#f2eee4">
          <h3 style="font-size:1rem">¿Buscas cotizar o vender?</h3>
          <p class="texto-pequeno texto-muted">Usa los formularios especializados para que podamos responder con datos técnicos y comerciales completos.</p>
          <div class="fila">
            <a class="boton boton--primario boton--sm" href="<?= url('/soy-comprador') ?>">Soy comprador</a>
            <a class="boton boton--contorno boton--sm" href="<?= url('/soy-proveedor') ?>">Soy proveedor</a>
          </div>
        </div>
        <div class="tarjeta" style="padding:1.4rem">
          <h3 style="font-size:1rem">Datos fiscales</h3>
          <p class="texto-pequeno texto-muted sin-margen"><?= e(empresa_nombre()) ?><br>RFC <?= e(setting('empresa_rfc', 'APD200702M63')) ?><br>Régimen: RESICO Personas Morales</p>
        </div>
      </div>
    </div>
  </div>
</section>
