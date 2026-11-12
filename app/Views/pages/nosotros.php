<?php /** Nosotros. */ ?>
<div class="contenedor">
  <nav class="migajas" aria-label="Ruta"><a href="<?= url('/') ?>">Inicio</a><span class="sep">/</span><span>Nosotros</span></nav>
</div>
<section class="seccion seccion--ajustada" style="padding-top:.8rem">
  <div class="contenedor">
    <div class="grid grid--2" style="gap:3rem;align-items:start">
      <div>
        <span class="eyebrow">Quiénes somos</span>
        <h1 style="font-size:clamp(1.9rem,3.4vw,2.7rem)">Un broker técnico, no un catálogo de PDFs</h1>
        <div class="prosa" style="max-width:none">
          <p><?= e(empresa_nombre()) ?> es una empresa mexicana dedicada a la intermediación y al desarrollo de proveeduría de arcillas y minerales no metálicos. Trabajamos con compradores industriales que necesitan certeza técnica y con minas y plantas que necesitan llegar a clientes calificados.</p>
          <p>Nuestro valor no está en tener el inventario más grande, sino en entender la especificación, verificar el lote y sostener la operación. Por eso cada material del directorio incluye propiedades técnicas, presentación, disponibilidad y proveedores, y por eso construimos calculadoras públicas: para que las decisiones de compra se tomen con números, no con promesas.</p>
          <h2 style="font-size:1.2rem;margin-top:1.8rem">Enfoque de trabajo</h2>
          <ul>
            <li><strong>Transparencia técnica:</strong> los valores son típicos de referencia y se respaldan con análisis del lote.</li>
            <li><strong>Confidencialidad:</strong> protegemos la identidad y especificación del comprador hasta que autorice el contacto.</li>
            <li><strong>Criterio de proceso:</strong> no cotizamos un material que no encaja con la aplicación, aunque haya existencias.</li>
            <li><strong>Documentación:</strong> permisos, pedimentos y certificados se revisan antes de comprometer una entrega.</li>
          </ul>
          <h2 style="font-size:1.2rem;margin-top:1.8rem">Datos de la empresa</h2>
          <p><strong><?= e(empresa_nombre()) ?></strong><br>
          RFC <?= e(setting('empresa_rfc', 'APD200702M63')) ?><br>
          Régimen fiscal: RESICO Personas Morales<br>
          <?= e(setting('empresa_direccion', 'Guadalajara, Jalisco, México')) ?></p>
        </div>
      </div>
      <div style="display:flex;flex-direction:column;gap:1rem">
        <div class="tarjeta" style="padding:1.5rem;background:linear-gradient(135deg,#efe8da,#f9f5ec)">
          <span class="eyebrow">En números</span>
          <div class="grid grid--2" style="gap:1rem;margin-top:.6rem">
            <div><strong style="font-size:2rem;font-family:var(--serif)">8</strong><p class="texto-pequeno texto-muted sin-margen">calculadoras técnicas públicas</p></div>
            <div><strong style="font-size:2rem;font-family:var(--serif)">12</strong><p class="texto-pequeno texto-muted sin-margen">familias de minerales</p></div>
            <div><strong style="font-size:2rem;font-family:var(--serif)">10</strong><p class="texto-pequeno texto-muted sin-margen">industrias atendidas</p></div>
            <div><strong style="font-size:2rem;font-family:var(--serif)">24 h</strong><p class="texto-pequeno texto-muted sin-margen">tiempo de respuesta hábil</p></div>
          </div>
        </div>
        <div class="tarjeta" style="padding:1.5rem">
          <h3 style="font-size:1rem">Nuestro compromiso</h3>
          <p class="texto-pequeno texto-muted">Cada operación se documenta: origen, lote, análisis, condiciones de entrega y responsable. Si algo no cuadra, lo decimos antes de que el material salga de la mina.</p>
          <a class="enlace-flecha texto-pequeno" href="<?= url('/como-funciona') ?>">Ver el proceso <?= icon('arrow-right') ?></a>
        </div>
        <div class="tarjeta" style="padding:1.5rem">
          <h3 style="font-size:1rem">¿Trabajamos juntos?</h3>
          <div class="fila" style="margin-top:.6rem">
            <a class="boton boton--primario boton--sm" href="<?= url('/soy-comprador') ?>">Soy comprador</a>
            <a class="boton boton--contorno boton--sm" href="<?= url('/soy-proveedor') ?>">Soy proveedor</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
