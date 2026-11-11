<?php /** Cómo funciona. */ ?>
<div class="contenedor">
  <nav class="migajas" aria-label="Ruta"><a href="<?= url('/') ?>">Inicio</a><span class="sep">/</span><span>Cómo funciona</span></nav>
</div>
<section class="seccion seccion--ajustada" style="padding-top:.8rem">
  <div class="contenedor">
    <div class="encabezado-seccion">
      <div class="encabezado-seccion__texto">
        <span class="eyebrow">Proceso de intermediación</span>
        <h1 style="font-size:clamp(1.9rem,3.4vw,2.7rem)">Del requerimiento a la entrega, sin saltos a ciegas</h1>
        <p>Actuamos como broker técnico: no vendemos un catálogo fijo, sino que buscamos y validamos el material que tu proceso necesita. Esto es lo que pasa desde que envías tu solicitud.</p>
      </div>
    </div>

    <div class="grid grid--2" style="gap:2rem">
      <div>
        <span class="eyebrow">Para compradores</span>
        <div class="pasos" style="grid-template-columns:1fr 1fr">
          <?php $pasosComprador = [
            ['01', 'Envías tu requerimiento', 'Mineral, especificación, volumen, destino y frecuencia. Si no tienes la especificación cerrada, la definimos contigo.'],
            ['02', 'Validación técnica', 'Contrastamos la ficha contra tu proceso: granulometría, pureza, humedad, refractariedad y compatibilidad.'],
            ['03', 'Muestra y cotización', 'Coordinamos muestra representativa del lote y una oferta con precio, empaque, flete y tiempos.'],
            ['04', 'Suministro y seguimiento', 'Programamos entregas, verificamos documentación y damos seguimiento a la calidad por lote.'],
          ]; foreach ($pasosComprador as [$n, $t, $d]): ?>
            <div class="paso"><span class="texto-mono" style="color:var(--tierra)"><?= $n ?></span><h3 style="margin-top:.35rem"><?= e($t) ?></h3><p><?= e($d) ?></p></div>
          <?php endforeach; ?>
        </div>
        <a class="boton boton--primario" style="margin-top:1rem" href="<?= url('/soy-comprador') ?>"><?= icon('file-text') ?> Enviar requerimiento</a>
      </div>
      <div>
        <span class="eyebrow">Para proveedores</span>
        <div class="pasos" style="grid-template-columns:1fr 1fr">
          <?php $pasosProveedor = [
            ['01', 'Registras tu oferta', 'Mineral, ubicación de mina o planta, capacidad, especificaciones y documentación disponible.'],
            ['02', 'Verificación', 'Revisamos análisis, permisos y consistencia del material. Si falta documentación, la solicitamos.'],
            ['03', 'Publicación', 'Creamos la ficha del material con tus datos técnicos y lo vinculamos a compradores compatibles.'],
            ['04', 'Cierre comercial', 'Coordinamos cotización, muestra y logística. Tú te concentras en producir; nosotros en vender.'],
          ]; foreach ($pasosProveedor as [$n, $t, $d]): ?>
            <div class="paso"><span class="texto-mono" style="color:var(--oliva)"><?= $n ?></span><h3 style="margin-top:.35rem"><?= e($t) ?></h3><p><?= e($d) ?></p></div>
          <?php endforeach; ?>
        </div>
        <a class="boton boton--contorno" style="margin-top:1rem" href="<?= url('/soy-proveedor') ?>"><?= icon('factory') ?> Registrar oferta</a>
      </div>
    </div>

    <hr style="margin:3rem 0">

    <div class="grid grid--2" style="gap:2.4rem;align-items:start">
      <div>
        <span class="eyebrow">Preguntas frecuentes</span>
        <h2 style="font-size:1.6rem">Lo que suelen preguntarnos</h2>
        <p class="texto-muted">Si tu duda no está aquí, <a href="<?= url('/contacto') ?>">escríbenos</a>.</p>
      </div>
      <div style="display:flex;flex-direction:column;gap:.7rem">
        <?php $faqs = [
          ['¿Cobran por cotizar?', 'No. La cotización y la búsqueda de proveedor no tienen costo para el comprador; nuestra comisión se acuerda cuando hay una operación.'],
          ['¿Venden muestras?', 'Coordinamos muestras representativas con el proveedor. Algunos cobran la muestra y el envío, que suele descontarse de la compra.'],
          ['¿Manejan volúmenes pequeños?', 'Depende del material y del empaque. Indícalo en el formulario y buscamos alternativas: sacos, big bags o consolidación de carga.'],
          ['¿Puedo comprar directo al proveedor?', 'Nuestro modelo es de intermediación: negociamos, validamos y damos seguimiento. Si quieres una relación directa, podemos presentarte con condiciones acordadas.'],
          ['¿Qué pasa con mi información?', 'Se usa solo para atender la solicitud. No compartimos tu identidad ni tu especificación sin tu autorización. Consulta el aviso de privacidad.'],
        ]; foreach ($faqs as [$p, $r]): ?>
          <details class="tarjeta" style="padding:1rem 1.2rem">
            <summary style="cursor:pointer;font-weight:600"><?= e($p) ?></summary>
            <p class="texto-pequeno texto-muted" style="margin:.6rem 0 0"><?= e($r) ?></p>
          </details>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
