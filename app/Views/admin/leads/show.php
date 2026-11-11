<?php /** Admin · detalle de lead. */ ?>
<div class="grid grid--2" style="gap:1.2rem;align-items:start">
  <div class="admin-panel">
    <div class="fila fila--separada" style="margin-bottom:1rem">
      <h2 style="margin:0">Lead #<?= (int) $lead['id'] ?> · <?= e(tipo_lead_label($lead['tipo'])) ?></h2>
      <?= badge_lead($lead['estado']) ?>
    </div>
    <table class="tabla-admin">
      <tbody>
        <tr><td class="texto-muted">Fecha</td><td><?= e(fmt_date($lead['creado_en'], 'd/m/Y H:i')) ?></td></tr>
        <tr><td class="texto-muted">Nombre</td><td><strong><?= e($lead['nombre']) ?></strong></td></tr>
        <tr><td class="texto-muted">Empresa</td><td><?= e($lead['empresa'] ?: '—') ?></td></tr>
        <tr><td class="texto-muted">Cargo</td><td><?= e($lead['cargo'] ?: '—') ?></td></tr>
        <tr><td class="texto-muted">Correo</td><td><a href="mailto:<?= e($lead['email']) ?>"><?= e($lead['email']) ?></a></td></tr>
        <tr><td class="texto-muted">Teléfono</td><td><?= e($lead['telefono'] ?: '—') ?></td></tr>
        <tr><td class="texto-muted">WhatsApp</td><td><?= e($lead['whatsapp'] ?: '—') ?></td></tr>
        <tr><td class="texto-muted">Material</td><td><?= e($lead['material_nombre'] ?? $lead['material_texto'] ?? '—') ?></td></tr>
        <tr><td class="texto-muted">Volumen</td><td><?= e(trim(($lead['volumen'] ?? '') . ' ' . ($lead['unidad'] ?? ''))) ?: '—' ?></td></tr>
        <tr><td class="texto-muted">Destino</td><td><?= e($lead['destino'] ?: '—') ?></td></tr>
        <tr><td class="texto-muted">Frecuencia</td><td><?= e($lead['frecuencia'] ?: '—') ?></td></tr>
        <tr><td class="texto-muted">Fuente</td><td><?= e($lead['fuente'] ?: '—') ?></td></tr>
        <tr><td class="texto-muted">IP</td><td class="texto-mono"><?= e($lead['ip'] ?: '—') ?></td></tr>
        <tr><td class="texto-muted">Consentimiento</td><td><?= (int) $lead['consentimiento'] === 1 ? 'Sí' : 'No' ?></td></tr>
      </tbody>
    </table>
    <?php if (!empty($lead['especificaciones'])): ?>
      <h3 style="font-size:.8rem;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);margin-top:1.2rem">Especificaciones</h3>
      <p class="texto-pequeno"><?= nl2br(e((string) $lead['especificaciones'])) ?></p>
    <?php endif; ?>
    <?php if (!empty($lead['mensaje'])): ?>
      <h3 style="font-size:.8rem;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);margin-top:1rem">Mensaje</h3>
      <p class="texto-pequeno"><?= nl2br(e((string) $lead['mensaje'])) ?></p>
    <?php endif; ?>
    <?php if ($items !== []): ?>
      <h3 style="font-size:.8rem;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);margin-top:1rem">Materiales solicitados</h3>
      <ul class="texto-pequeno"><?php foreach ($items as $item): ?><li><?= e($item['nombre']) ?><?= $item['cantidad'] ? ' · ' . e($item['cantidad']) . ' ' . e((string) $item['unidad']) : '' ?></li><?php endforeach; ?></ul>
    <?php endif; ?>
    <div class="fila" style="margin-top:1.2rem">
      <a class="boton boton--primario boton--sm" href="mailto:<?= e($lead['email']) ?>?subject=<?= rawurlencode('Respuesta a tu solicitud #' . $lead['id'] . ' · Arcillas MX') ?>"><?= icon('mail') ?> Responder por correo</a>
      <?php if (!empty($lead['whatsapp']) || !empty($lead['telefono'])): ?>
        <a class="boton boton--contorno boton--sm" target="_blank" rel="noopener" href="https://wa.me/<?= e(preg_replace('/\D+/', '', (string) ($lead['whatsapp'] ?: $lead['telefono']))) ?>"><?= icon('whatsapp') ?> WhatsApp</a>
      <?php endif; ?>
    </div>
  </div>

  <div>
    <form class="admin-panel" method="post" action="<?= url('/admin/leads/' . $lead['id'] . '/actualizar') ?>" style="margin-bottom:1.2rem">
      <?= csrf_field() ?>
      <h2>Seguimiento</h2>
      <div class="campo" style="margin-bottom:.8rem">
        <label for="estado">Estado</label>
        <select id="estado" name="estado">
          <?php foreach (['nuevo', 'contactado', 'cotizado', 'ganado', 'perdido', 'spam'] as $e): ?>
            <option value="<?= $e ?>" <?= $lead['estado'] === $e ? 'selected' : '' ?>><?= ucfirst($e) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="campo" style="margin-bottom:.8rem">
        <label for="notas_internas">Notas internas</label>
        <textarea id="notas_internas" name="notas_internas" rows="5"><?= e((string) $lead['notas_internas']) ?></textarea>
      </div>
      <button class="boton boton--primario boton--sm" type="submit"><?= icon('save') ?> Guardar seguimiento</button>
    </form>

    <?php if (App\Core\Auth::is('admin')): ?>
    <form class="admin-panel" method="post" action="<?= url('/admin/leads/' . $lead['id'] . '/eliminar') ?>" onsubmit="return confirm('¿Eliminar definitivamente este lead? Esta acción no se puede deshacer.')">
      <?= csrf_field() ?>
      <h2 style="color:var(--danger)">Zona delicada</h2>
      <p class="texto-pequeno texto-muted">Elimina el lead y sus ítems de forma permanente. Normalmente basta con marcarlo como spam o perdido.</p>
      <button class="boton boton--peligro boton--sm" type="submit"><?= icon('trash') ?> Eliminar lead</button>
    </form>
    <?php endif; ?>
  </div>
</div>
<p style="margin-top:1rem"><a class="enlace-flecha texto-pequeno" href="<?= url('/admin/leads') ?>"><?= icon('arrow-left') ?> Volver a la bandeja</a></p>
