<?php
/** Pie de página público. */
$calculadorasFooter = array_slice(calculadoras_disponibles(), 0, 4);
$anio = date('Y');
?>
<footer class="sitio-footer">
  <div class="contenedor">
    <div class="sitio-footer__principal">
      <div>
        <a class="marca" href="<?= url('/') ?>">
          <img class="marca__simbolo" src="<?= url('/assets/img/marca.svg') ?>" alt="" width="42" height="42">
          <span class="marca__texto">
            <span class="marca__nombre">Arcillas MX</span>
            <span class="marca__bajada">Minerales industriales</span>
          </span>
        </a>
        <p style="margin-top:1rem;max-width:38ch">Directorio técnico y comercial de arcillas, minerales industriales y concentrados no ferrosos. Conectamos compradores industriales con minas, plantas y distribuidores en México.</p>
        <p class="texto-mono" style="color:#8f8677">RFC <?= e(setting('empresa_rfc', 'APD200702M63')) ?></p>
      </div>
      <div>
        <h4>Explora</h4>
        <ul>
          <li><a href="<?= url('/materiales') ?>">Todos los materiales</a></li>
          <li><a href="<?= url('/industrias') ?>">Por industria</a></li>
          <li><a href="<?= url('/proveedores') ?>">Proveedores</a></li>
          <li><a href="<?= url('/comparar') ?>">Comparar materiales</a></li>
          <li><a href="<?= url('/soy-comprador') ?>">Soy comprador</a></li>
          <li><a href="<?= url('/soy-proveedor') ?>">Soy proveedor</a></li>
        </ul>
      </div>
      <div>
        <h4>Herramientas</h4>
        <ul>
          <li><a href="<?= url('/calculadoras') ?>">Todas las calculadoras</a></li>
          <?php foreach ($calculadorasFooter as $calc): ?>
            <li><a href="<?= url('/calculadoras/' . $calc->slug()) ?>"><?= e($calc->nombre()) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div>
        <h4>Contacto</h4>
        <ul class="sitio-footer__contacto">
          <li><?= icon('mail') ?><a href="mailto:<?= e(setting('empresa_email', 'contacto@arcillas.mx')) ?>"><?= e(setting('empresa_email', 'contacto@arcillas.mx')) ?></a></li>
          <li><?= icon('phone') ?><a href="tel:<?= e(preg_replace('/\s+/', '', (string) setting('empresa_telefono', ''))) ?>"><?= e(setting('empresa_telefono', '+52 55 0000 0000')) ?></a></li>
          <li><?= icon('whatsapp') ?><a href="<?= e(whatsapp_url('Hola, quiero información.')) ?>" target="_blank" rel="noopener">WhatsApp directo</a></li>
          <li><?= icon('pin') ?><span><?= e(setting('empresa_direccion', 'Ciudad de México, México')) ?></span></li>
        </ul>
      </div>
    </div>
    <div class="sitio-footer__legal">
      <span>© <?= $anio ?> <?= e(empresa_nombre()) ?>. Todos los derechos reservados.</span>
      <span class="fila">
        <a href="<?= url('/aviso-de-privacidad') ?>">Aviso de privacidad</a>
        <a href="<?= url('/terminos') ?>">Términos y condiciones</a>
        <a href="<?= url('/sitemap.xml') ?>">Sitemap</a>
      </span>
    </div>
  </div>
</footer>
