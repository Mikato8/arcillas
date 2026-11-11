<?php /** Aviso de privacidad. */ ?>
<div class="contenedor contenedor--estrecho">
  <nav class="migajas" aria-label="Ruta"><a href="<?= url('/') ?>">Inicio</a><span class="sep">/</span><span>Aviso de privacidad</span></nav>
</div>
<section class="seccion seccion--ajustada" style="padding-top:.8rem">
  <div class="contenedor contenedor--estrecho">
    <span class="eyebrow">Legal</span>
    <h1 style="font-size:clamp(1.9rem,3.4vw,2.6rem)">Aviso de privacidad integral</h1>
    <p class="texto-muted">Última actualización: <?= date('d/m/Y') ?>.</p>

    <div class="nota-legal" style="margin:1.4rem 0">
      <strong>Nota para revisión legal:</strong> este documento es una base conforme a la LFPDPPP y debe ser revisado y, en su caso, adaptado por un abogado antes de su publicación definitiva. Los datos entre corchetes son marcadores que deben completarse.
    </div>

    <div class="prosa">
      <h2>1. Responsable de los datos personales</h2>
      <p><strong><?= e(empresa_nombre()) ?></strong> (“Arcillas MX”), con domicilio en <?= e(setting('empresa_direccion', '[domicilio fiscal completo]')) ?>, es responsable del tratamiento de los datos personales que recaba a través de este sitio web y de sus formularios de contacto, cotización, muestras y registro de proveedores.</p>
      <p>Correo de contacto del responsable: <a href="mailto:<?= e(setting('empresa_email', 'contacto@arcillas.mx')) ?>"><?= e(setting('empresa_email', 'contacto@arcillas.mx')) ?></a>.</p>

      <h2>2. Datos personales que recabamos</h2>
      <p>Para las finalidades señaladas en este aviso podemos recabar:</p>
      <ul>
        <li>Datos de identificación: nombre, cargo y empresa.</li>
        <li>Datos de contacto: correo electrónico, teléfono y WhatsApp.</li>
        <li>Datos de la operación: mineral de interés, especificaciones, volumen, destino, frecuencia de compra y documentación técnica o comercial que nos compartas.</li>
        <li>Datos de navegación: dirección IP, tipo de navegador y páginas visitadas, con fines de seguridad y estadística.</li>
      </ul>
      <p>No recabamos datos personales sensibles.</p>

      <h2>3. Finalidades del tratamiento</h2>
      <p><strong>Finalidades primarias</strong> (necesarias para la relación):</p>
      <ul>
        <li>Atender solicitudes de cotización, muestras, contacto y registro de proveedores.</li>
        <li>Validar especificaciones técnicas y contactar proveedores para integrar una propuesta.</li>
        <li>Dar seguimiento a la operación, documentación y logística.</li>
        <li>Facturación y cumplimiento de obligaciones fiscales, cuando aplique.</li>
      </ul>
      <p><strong>Finalidades secundarias</strong> (puedes oponerte):</p>
      <ul>
        <li>Envío de información sobre materiales, calculadoras y contenidos técnicos.</li>
        <li>Estadísticas internas y mejora del sitio.</li>
      </ul>
      <p>Si no deseas que tus datos se traten para finalidades secundarias, escríbenos a <?= e(setting('empresa_email', 'contacto@arcillas.mx')) ?> indicando tu oposición.</p>

      <h2>4. Transferencias</h2>
      <p>Para atender tu solicitud, podemos compartir los datos estrictamente necesarios con proveedores o aliados involucrados en la operación (minas, plantas, transportistas o laboratorios). En esos casos se informa el nombre del proveedor y se limita el uso a la operación. No realizamos transferencias adicionales sin tu consentimiento, salvo las excepciones previstas en el artículo 37 de la LFPDPPP.</p>

      <h2>5. Plazo de conservación</h2>
      <p>Conservamos los datos mientras exista la relación comercial y durante los plazos legales aplicables (fiscales, mercantiles y de responsabilidad). Posteriormente se bloquean y eliminan de forma segura.</p>

      <h2>6. Derechos ARCO y revocación del consentimiento</h2>
      <p>Puedes ejercer tus derechos de acceso, rectificación, cancelación y oposición, así como revocar tu consentimiento, mediante solicitud al correo <a href="mailto:<?= e(setting('empresa_email', 'contacto@arcillas.mx')) ?>"><?= e(setting('empresa_email', 'contacto@arcillas.mx')) ?></a>, indicando:</p>
      <ul>
        <li>Nombre completo y medio para recibir respuesta.</li>
        <li>Documento que acredite tu identidad o representación.</li>
        <li>Descripción clara del derecho que deseas ejercer.</li>
      </ul>
      <p>Responderemos en los plazos previstos por la ley. También puedes acudir al Instituto Nacional de Transparencia, Acceso a la Información y Protección de Datos Personales (INAI).</p>

      <h2>7. Cookies y tecnologías de rastreo</h2>
      <p>Este sitio utiliza cookies técnicas necesarias para la sesión y, en su caso, herramientas de analítica. Puedes deshabilitar las cookies en tu navegador; algunas funciones podrían dejar de operar correctamente. [Completar con el inventario de cookies y herramientas de analítica utilizadas.]</p>

      <h2>8. Seguridad</h2>
      <p>Implementamos medidas administrativas, técnicas y físicas razonables para proteger los datos: transmisión cifrada (HTTPS), control de acceso al panel, validación de formularios y registro de acciones. Ningún sistema es infalible; si ocurriera un incidente, se notificará conforme a la ley.</p>

      <h2>9. Cambios al aviso</h2>
      <p>Podemos modificar este aviso. La versión vigente estará publicada en esta página con su fecha de actualización. Los cambios sustanciales se comunicarán por los medios de contacto disponibles.</p>

      <h2>10. Consentimiento</h2>
      <p>Al enviar cualquiera de nuestros formularios y marcar la casilla de aceptación, manifiestas que has leído y comprendido este aviso de privacidad y otorgas tu consentimiento para el tratamiento de tus datos conforme a las finalidades descritas.</p>
    </div>
  </div>
</section>
