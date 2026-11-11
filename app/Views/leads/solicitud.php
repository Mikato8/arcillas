<?php
/** Formulario de solicitud (cotización / muestra). */
$val = static fn (string $k, string $defecto = ''): string => e((string) ($old[$k] ?? $defecto));
$err = static fn (string $k): string => isset($errores[$k]) ? ' campo--error' : '';
$msg = static fn (string $k): string => isset($errores[$k]) ? '<span class="mensaje-error">' . e($errores[$k]) . '</span>' : '';
$tipo = $tipoLead ?: 'cotizacion';
$items = $items ?? [];
?>
<div class="contenedor contenedor--estrecho">
  <nav class="migajas" aria-label="Ruta">
    <a href="<?= url('/') ?>">Inicio</a><span class="sep">/</span><span><?= $tipo === 'muestra' ? 'Solicitar muestra' : 'Solicitar cotización' ?></span>
  </nav>
</div>

<section class="seccion seccion--ajustada" style="padding-top:.8rem">
  <div class="contenedor contenedor--estrecho">
    <div class="encabezado-seccion" style="margin-bottom:1.2rem">
      <div class="encabezado-seccion__texto">
        <span class="eyebrow">Respuesta en <?= e(setting('respuesta_horas', '24')) ?> h hábiles</span>
        <h1 style="font-size:clamp(1.7rem,3vw,2.4rem);margin-bottom:.4rem"><?= $tipo === 'muestra' ? 'Solicitar muestra representativa' : 'Solicitar cotización' ?></h1>
        <p>Cuéntanos qué necesitas y para qué proceso. Validamos la especificación y te respondemos con alternativas concretas de proveedores.</p>
      </div>
    </div>

    <?php if (!empty($flash_error)): ?><div class="aviso-error" style="margin-bottom:1rem"><?= e($flash_error) ?></div><?php endif; ?>
    <?php if ($errores !== []): ?>
      <div class="errores-formulario" style="margin-bottom:1rem">
        Revisa los campos marcados:
        <ul><?php foreach ($errores as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>

    <?php if ($material !== null || $items !== []): ?>
      <div class="tarjeta" style="padding:1rem 1.2rem;margin-bottom:1.2rem;background:#f2eee4">
        <h3 style="font-size:.85rem;text-transform:uppercase;letter-spacing:.1em;color:var(--muted);margin-bottom:.5rem">Material(es) de interés</h3>
        <?php if ($material !== null): ?>
          <p style="margin:0;font-weight:600"><?= e($material['nombre']) ?> <span class="texto-muted">· <?= e($material['categoria_nombre']) ?></span></p>
        <?php endif; ?>
        <?php if ($items !== []): ?>
          <ul style="margin:.4rem 0 0;padding-left:1.1rem">
            <?php foreach ($items as $item): ?><li><?= e($item['nombre']) ?></li><?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <form class="formulario tarjeta" style="padding:clamp(1.3rem,3vw,1.8rem)" method="post" action="<?= url('/solicitud') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="tipo" value="<?= e($tipo) ?>">
      <input type="hidden" name="material_texto" value="<?= e(($old['material_texto'] ?? '') !== '' ? $old['material_texto'] : ($material['nombre'] ?? implode(', ', array_column($items, 'nombre')))) ?>">
      <?php if ($material !== null): ?><input type="hidden" name="material_id" value="<?= (int) $material['id'] ?>"><?php endif; ?>
      <?php foreach ($items as $indice => $item): ?>
        <input type="hidden" name="items[<?= (int) $indice ?>][material_id]" value="<?= (int) $item['material_id'] ?>">
      <?php endforeach; ?>
      <div class="honeypot"><label>Sitio web<input type="text" name="sitio_web" tabindex="-1" autocomplete="off"></label></div>

      <div class="fila-campos">
        <div class="campo<?= $err('nombre') ?>">
          <label for="nombre">Nombre completo *</label>
          <input id="nombre" name="nombre" type="text" value="<?= $val('nombre') ?>" required>
          <?= $msg('nombre') ?>
        </div>
        <div class="campo<?= $err('empresa') ?>">
          <label for="empresa">Empresa *</label>
          <input id="empresa" name="empresa" type="text" value="<?= $val('empresa') ?>" required>
          <?= $msg('empresa') ?>
        </div>
        <div class="campo<?= $err('email') ?>">
          <label for="email">Correo corporativo *</label>
          <input id="email" name="email" type="email" value="<?= $val('email') ?>" required>
          <?= $msg('email') ?>
        </div>
        <div class="campo">
          <label for="telefono">Teléfono</label>
          <input id="telefono" name="telefono" type="tel" value="<?= $val('telefono') ?>">
        </div>
        <div class="campo<?= $err('volumen') ?>">
          <label for="volumen">Volumen estimado *</label>
          <input id="volumen" name="volumen" type="text" value="<?= $val('volumen') ?>" placeholder="Ej. 20 toneladas / mes" required>
          <?= $msg('volumen') ?>
        </div>
        <div class="campo">
          <label for="unidad">Unidad</label>
          <select id="unidad" name="unidad">
            <?php foreach (['tonelada', 'kg', 'saco', 'big bag', 'pieza', 'contenedor'] as $u): ?>
              <option value="<?= $u ?>" <?= ($old['unidad'] ?? '') === $u ? 'selected' : '' ?>><?= ucfirst($u) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="campo">
          <label for="destino">Destino / planta</label>
          <input id="destino" name="destino" type="text" value="<?= $val('destino') ?>" placeholder="Ciudad o estado de entrega">
        </div>
        <div class="campo">
          <label for="frecuencia">Frecuencia</label>
          <select id="frecuencia" name="frecuencia">
            <?php foreach (['Única', 'Mensual', 'Trimestral', 'Semestral', 'Anual', 'Por definir'] as $f): ?>
              <option value="<?= $f ?>" <?= ($old['frecuencia'] ?? '') === $f ? 'selected' : '' ?>><?= $f ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="campo campo--completo">
        <label for="especificaciones">Especificaciones técnicas</label>
        <textarea id="especificaciones" name="especificaciones" rows="3" placeholder="Composición, granulometría, humedad máxima, blancura, aplicación…"><?= $val('especificaciones') ?></textarea>
        <span class="ayuda">Si no conoces la especificación exacta, describe el proceso y te ayudamos a definirla.</span>
      </div>
      <div class="campo campo--completo">
        <label for="mensaje">Mensaje adicional</label>
        <textarea id="mensaje" name="mensaje" rows="4" placeholder="Cuéntanos sobre tu proceso, fechas o requisitos de entrega."><?= $val('mensaje') ?></textarea>
        <?= $msg('mensaje') ?>
      </div>
      <div class="consentimiento">
        <label class="check<?= $err('consentimiento') ?>">
          <input type="checkbox" name="consentimiento" value="1" <?= !empty($old['consentimiento']) ? 'checked' : '' ?> required>
          <span>He leído y acepto el <a href="<?= url('/aviso-de-privacidad') ?>" target="_blank" rel="noopener">aviso de privacidad</a> y el tratamiento de mis datos para atender esta solicitud. *</span>
        </label>
        <?= $msg('consentimiento') ?>
      </div>
      <div class="fila">
        <button class="boton boton--primario boton--lg" type="submit"><?= $tipo === 'muestra' ? 'Solicitar muestra' : 'Enviar solicitud' ?> <?= icon('arrow-right') ?></button>
        <a class="boton boton--fantasma" href="<?= e(whatsapp_url('Hola, quiero cotizar un mineral.')) ?>" target="_blank" rel="noopener"><?= icon('whatsapp') ?> Prefiero WhatsApp</a>
      </div>
      <p class="texto-pequeno texto-muted">Tus datos se usan solo para atender la solicitud. No compartimos información con terceros ajenos a la operación.</p>
    </form>
  </div>
</section>
