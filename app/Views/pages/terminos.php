<?php /** Términos y condiciones. */ ?>
<div class="contenedor contenedor--estrecho">
  <nav class="migajas" aria-label="Ruta"><a href="<?= url('/') ?>">Inicio</a><span class="sep">/</span><span>Términos y condiciones</span></nav>
</div>
<section class="seccion seccion--ajustada" style="padding-top:.8rem">
  <div class="contenedor contenedor--estrecho">
    <span class="eyebrow">Legal</span>
    <h1 style="font-size:clamp(1.9rem,3.4vw,2.6rem)">Términos y condiciones de uso</h1>
    <p class="texto-muted">Última actualización: <?= date('d/m/Y') ?>.</p>

    <div class="nota-legal" style="margin:1.4rem 0">
      <strong>Nota para revisión legal:</strong> documento base. Debe validarse con el área jurídica antes de su publicación definitiva; los datos entre corchetes deben completarse.
    </div>

    <div class="prosa">
      <h2>1. Objeto del sitio</h2>
      <p>arcillas.mx es un sitio de información técnica y comercial operado por <?= e(empresa_nombre()) ?>. Su función es publicar fichas de materiales, conectar compradores industriales con proveedores y ofrecer herramientas de cálculo orientativas. El sitio no es una tienda en línea ni una plataforma de pagos.</p>

      <h2>2. Información técnica</h2>
      <p>Los datos de composición, propiedades, granulometría, disponibilidad y precios de referencia son orientativos, típicos o estimados, y no constituyen una garantía contractual ni una oferta vinculante. La especificación aplicable será la que se acuerde por escrito entre las partes y se respalde con análisis de laboratorio del lote.</p>
      <p>Las calculadoras proporcionan resultados estimados con base en las fórmulas publicadas y los datos que el usuario captura. No sustituyen pruebas de laboratorio, estudios de ingeniería ni la validación de un especialista.</p>

      <h2>3. Intermediación</h2>
      <p>Salvo que se indique lo contrario por escrito, <?= e(empresa_nombre()) ?> actúa como intermediario entre comprador y proveedor. La relación de compraventa, garantías, entregas y responsabilidades se rigen por el contrato o pedido entre las partes. La empresa no es fabricante ni propietaria de las minas, salvo declaración expresa.</p>

      <h2>4. Registro y formularios</h2>
      <p>Al enviar un formulario, el usuario declara que la información es veraz y que está facultado para compartirla. Nos reservamos el derecho de rechazar solicitudes incompletas, falsas, duplicadas o que puedan constituir spam o uso indebido.</p>

      <h2>5. Propiedad intelectual</h2>
      <p>Los textos, fichas, ilustraciones, calculadoras y código del sitio son propiedad de <?= e(empresa_nombre()) ?> o se usan con autorización. Se prohíbe su reproducción total o parcial sin autorización escrita, salvo citas con atribución.</p>

      <h2>6. Enlaces de terceros</h2>
      <p>El sitio puede incluir enlaces a sitios de terceros (proveedores, organismos, fuentes técnicas). No somos responsables de su contenido ni de sus prácticas de privacidad.</p>

      <h2>7. Limitación de responsabilidad</h2>
      <p>En la medida permitida por la ley, <?= e(empresa_nombre()) ?> no será responsable por daños indirectos, lucro cesante o decisiones de producción tomadas con base en información orientativa del sitio. El usuario es responsable de validar el material antes de su uso industrial.</p>

      <h2>8. Disponibilidad del sitio</h2>
      <p>Procuramos mantener el sitio disponible y actualizado, pero puede haber interrupciones por mantenimiento, seguridad o causas ajenas. No garantizamos disponibilidad ininterrumpida.</p>

      <h2>9. Legislación aplicable</h2>
      <p>Estos términos se rigen por las leyes de los Estados Unidos Mexicanos. Cualquier controversia se someterá a los tribunales competentes de [ciudad], México, renunciando a cualquier otro fuero que pudiera corresponder.</p>

      <h2>10. Contacto</h2>
      <p>Para dudas sobre estos términos: <a href="mailto:<?= e(setting('empresa_email', 'contacto@arcillas.mx')) ?>"><?= e(setting('empresa_email', 'contacto@arcillas.mx')) ?></a>.</p>
    </div>
  </div>
</section>
