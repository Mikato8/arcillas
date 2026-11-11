<?php /** Confirmación. */ ?>
<div class="contenedor">
  <div class="gracias">
    <div class="gracias__icono"><?= icon('check') ?></div>
    <span class="eyebrow" style="justify-content:center">Solicitud recibida</span>
    <h1 style="font-size:clamp(1.7rem,3vw,2.3rem)">Gracias, ya tenemos tu solicitud</h1>
    <p class="texto-muted">Un especialista revisará el requerimiento y te contactará en menos de <?= e(setting('respuesta_horas', '24')) ?> horas hábiles. Si es urgente, escríbenos por WhatsApp.</p>
    <div class="fila">
      <a class="boton boton--primario" href="<?= e(whatsapp_url('Hola, acabo de enviar una solicitud desde arcillas.mx')) ?>" target="_blank" rel="noopener"><?= icon('whatsapp') ?> WhatsApp</a>
      <a class="boton boton--contorno" href="<?= url('/materiales') ?>">Seguir explorando</a>
    </div>
    <p class="texto-pequeno texto-muted" style="margin-top:1.6rem">Mientras tanto, puedes calcular dosificación o balance con nuestras <a href="<?= url('/calculadoras') ?>">calculadoras técnicas</a>.</p>
  </div>
</div>
