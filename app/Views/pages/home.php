<?php
/** Portada. */
$heroTitulo = (string) setting('hero_titulo', '');
$heroSub = (string) setting('hero_subtitulo', '');
$calc = calculadoras_disponibles();
?>
<section class="hero">
  <div class="contenedor hero__interior">
    <div>
      <span class="hero__etiqueta"><span class="punto"></span> Broker técnico de minerales · México</span>
      <h1><?= $heroTitulo !== '' ? e($heroTitulo) : 'Arcillas y minerales no ferrosos, con la <em>ficha técnica resuelta</em>.' ?></h1>
      <p class="hero__bajada"><?= $heroSub !== '' ? e($heroSub) : 'Del requerimiento a la entrega: comparamos composición, granulometría y disponibilidad de proveedores verificados, y calculamos la dosificación o el balance que tu proceso necesita.' ?></p>

      <form class="buscador-hero" action="<?= url('/materiales') ?>" method="get" role="search">
        <label class="oculto" for="q-hero">Buscar mineral</label>
        <input id="q-hero" type="search" name="q" placeholder="Ej. caolín cerámico, bentonita…" autocomplete="off">
        <label class="oculto" for="cat-hero">Categoría</label>
        <select id="cat-hero" name="categoria">
          <option value="">Todas las categorías</option>
          <?php foreach ($categorias as $cat): ?>
            <option value="<?= e($cat['slug']) ?>"><?= e($cat['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
        <button class="boton boton--primario" type="submit"><?= icon('search') ?> Buscar</button>
      </form>
      <p class="hero__pista">Búsqueda por propiedad, aplicación o proveedor. También puedes <a href="<?= url('/calculadoras') ?>">usar una calculadora técnica</a>.</p>

      <div class="hero__datos">
        <div class="hero__dato"><strong><?= num($totales['materiales']) ?></strong><span>Materiales</span></div>
        <div class="hero__dato"><strong><?= num($totales['proveedores']) ?></strong><span>Proveedores</span></div>
        <div class="hero__dato"><strong><?= num($totales['industrias']) ?></strong><span>Industrias</span></div>
        <div class="hero__dato"><strong><?= num($totales['calculadoras']) ?></strong><span>Calculadoras</span></div>
      </div>
    </div>

    <div class="hero__visual" aria-hidden="true">
      <div class="hero__tarjeta">
        <div class="hero__tarjeta-encabezado">
          <span class="hero__tarjeta-titulo">Ficha técnica · comparativa</span>
          <span class="badge badge--ok">Verificada</span>
        </div>
        <table class="hero__tabla">
          <thead><tr><th>Material</th><th>Al₂O₃</th><th>Fe₂O₃</th><th>Humedad</th></tr></thead>
          <tbody>
            <tr><td>Caolín cerámico</td><td>37.4 %</td><td>0.72 %</td><td>1.2 %</td></tr>
            <tr><td>Bentonita sódica</td><td>15.8 %</td><td>2.9 %</td><td>10.5 %</td></tr>
            <tr><td>Ball clay</td><td>27.1 %</td><td>1.4 %</td><td>2.0 %</td></tr>
            <tr><td>Arcilla refractaria</td><td>33.6 %</td><td>1.9 %</td><td>1.6 %</td></tr>
          </tbody>
        </table>
        <div class="hero__nota"><?= icon('info') ?><span>Datos de referencia del catálogo. Cada ficha incluye especificaciones, disponibilidad y proveedores.</span></div>
      </div>
      <span class="hero__sello">Muestra · catálogo</span>
    </div>
  </div>
</section>

<section class="seccion seccion--ajustada seccion--papel">
  <div class="contenedor">
    <div class="fila fila--separada" style="margin-bottom:1.1rem">
      <div>
        <span class="eyebrow">Por industria</span>
        <h2 style="font-size:1.5rem;margin:0">¿Para qué proceso lo necesitas?</h2>
      </div>
      <a class="enlace-flecha" href="<?= url('/industrias') ?>">Ver todas <?= icon('arrow-right') ?></a>
    </div>
    <div class="grid grid--4">
      <?php foreach ($industrias as $ind): ?>
        <a class="tarjeta tarjeta-industria" href="<?= url('/industrias/' . $ind['slug']) ?>">
          <span class="tarjeta-industria__icono"><?= icon(industria_icono($ind['slug'])) ?></span>
          <span>
            <h3><?= e($ind['nombre']) ?></h3>
            <p><?= e(truncate((string) $ind['descripcion'], 88)) ?></p>
            <span class="tarjeta-industria__total"><?= (int) $ind['total'] ?> materiales</span>
          </span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="seccion">
  <div class="contenedor">
    <div class="encabezado-seccion">
      <div class="encabezado-seccion__texto">
        <span class="eyebrow">Directorio de materiales</span>
        <h2>Minerales destacados</h2>
        <p>Fichas con composición química, propiedades físicas, granulometría, presentación y proveedores disponibles.</p>
      </div>
      <a class="boton boton--contorno" href="<?= url('/materiales') ?>"><?= icon('layers') ?> Ver catálogo completo</a>
    </div>
    <div class="grid grid--3">
      <?php foreach ($destacados as $material): ?>
        <?php
        $claves = $clavesDestacados[(int) $material['id']] ?? [];
        require APP_BASE . '/app/Views/partials/material-card.php';
        ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="seccion seccion--ajustada seccion--papel">
  <div class="contenedor">
    <div class="encabezado-seccion">
      <div class="encabezado-seccion__texto">
        <span class="eyebrow">Catálogo por familia</span>
        <h2>Explora por categoría</h2>
      </div>
      <a class="enlace-flecha" href="<?= url('/materiales') ?>">Todo el catálogo <?= icon('arrow-right') ?></a>
    </div>
    <div class="lista-categorias">
      <?php foreach ($categorias as $cat): ?>
        <a class="categoria-pildora" href="<?= url('/materiales?categoria=' . $cat['slug']) ?>">
          <span><?= e($cat['nombre']) ?></span><span><?= (int) $cat['total'] ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="seccion">
  <div class="contenedor">
    <div class="encabezado-seccion">
      <div class="encabezado-seccion__texto">
        <span class="eyebrow">Herramientas técnicas</span>
        <h2>Calcula antes de comprar</h2>
        <p>Ocho calculadoras de formulación, proceso y comercio para arcillas y minerales no ferrosos. Gratis y sin registro.</p>
      </div>
      <a class="boton boton--contorno" href="<?= url('/calculadoras') ?>"><?= icon('calculator') ?> Abrir calculadoras</a>
    </div>
    <div class="grid grid--4">
      <?php foreach (array_slice($calc, 0, 8) as $c): ?>
        <a class="tarjeta tarjeta-calc" href="<?= url('/calculadoras/' . $c->slug()) ?>">
          <span class="tarjeta-calc__icono"><?= icon('calculator') ?></span>
          <h3><?= e($c->nombre()) ?></h3>
          <p><?= e(truncate($c->descripcion(), 96)) ?></p>
          <span class="tarjeta-calc__meta"><span><?= e($c->categoria()) ?></span><?= icon('arrow-right') ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="seccion seccion--tinta">
  <div class="contenedor">
    <div class="grid grid--2" style="gap:clamp(2rem,5vw,4rem);align-items:start">
      <div>
        <span class="eyebrow eyebrow--claro">Cómo funciona</span>
        <h2>Intermediación con criterio técnico, no solo contactos</h2>
        <p class="texto-muted" style="color:#c5bdb0">Validamos la especificación contra la aplicación, identificamos la mina o planta adecuada y acompañamos muestra, calidad y logística. Tú compras con trazabilidad; el proveedor cobra sin perseguir clientes.</p>
        <div class="fila" style="margin-top:1.4rem">
          <a class="boton boton--claro" href="<?= url('/como-funciona') ?>">Ver el proceso completo <?= icon('arrow-right') ?></a>
          <a class="boton boton--fantasma" style="color:#e8dfd2" href="<?= url('/soy-comprador') ?>">Solicitar cotización</a>
        </div>
      </div>
      <div class="grid grid--2" style="gap:.9rem">
        <?php
        $pasos = [
            ['01', 'Requerimiento', 'Mineral, volumen, especificación, destino y frecuencia.'],
            ['02', 'Validación', 'Contrastamos la ficha contra tu proceso y detectamos riesgos.'],
            ['03', 'Muestra y cotización', 'Coordinamos muestra representativa y oferta con proveedor.'],
            ['04', 'Suministro', 'Programamos entrega, documentación y seguimiento de calidad.'],
        ];
        foreach ($pasos as [$n, $t, $d]): ?>
          <div class="paso" style="background:#3a352c;border-color:#4a443a">
            <span class="texto-mono" style="color:#c69a76"><?= $n ?></span>
            <h3 style="color:#f7f1e5;margin-top:.4rem"><?= e($t) ?></h3>
            <p style="color:#b8b0a2"><?= e($d) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<section class="seccion">
  <div class="contenedor">
    <div class="encabezado-seccion">
      <div class="encabezado-seccion__texto">
        <span class="eyebrow">Red de suministro</span>
        <h2>Proveedores en el directorio</h2>
        <p>Minas, plantas y distribuidores. La ficha de cada material indica quién lo suministra y en qué condiciones.</p>
      </div>
      <a class="boton boton--contorno" href="<?= url('/proveedores') ?>"><?= icon('users') ?> Ver proveedores</a>
    </div>
    <div class="grid grid--4">
      <?php foreach ($proveedores as $prov): ?>
        <a class="tarjeta" href="<?= url('/proveedores/' . $prov['slug']) ?>" style="display:block;padding:1.15rem;text-decoration:none;color:inherit">
          <div class="fila" style="justify-content:space-between">
            <span class="proveedor-mini__logo"><?= e(mb_substr($prov['nombre'], 0, 2)) ?></span>
            <?= (int) $prov['verificado'] === 1 ? '<span class="badge badge--ok">Verificado</span>' : '<span class="badge badge--ghost">Demo</span>' ?>
          </div>
          <h3 style="font-size:.98rem;margin:.8rem 0 .3rem"><?= e($prov['nombre']) ?></h3>
          <p class="texto-pequeno texto-muted sin-margen"><?= e(ucfirst($prov['tipo'])) ?> · <?= e($prov['estado'] ?: 'México') ?></p>
          <p class="texto-mono" style="margin:.5rem 0 0;color:var(--tierra-oscura)"><?= (int) $prov['total'] ?> materiales</p>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php if ($ultimos !== []): ?>
<section class="seccion seccion--ajustada seccion--papel">
  <div class="contenedor">
    <div class="encabezado-seccion" style="margin-bottom:1.2rem">
      <div class="encabezado-seccion__texto">
        <span class="eyebrow">Actualizaciones</span>
        <h2 style="font-size:1.6rem">Recién agregados</h2>
      </div>
      <a class="enlace-flecha" href="<?= url('/materiales?orden=recientes') ?>">Ver novedades <?= icon('arrow-right') ?></a>
    </div>
    <div class="tabla-envoltura">
      <table class="tabla">
        <thead><tr><th>Material</th><th>Categoría</th><th>Disponibilidad</th><th>Región</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($ultimos as $mat): ?>
          <tr>
            <td><a href="<?= url('/materiales/' . $mat['slug']) ?>"><strong><?= e($mat['nombre']) ?></strong></a></td>
            <td><?= e($mat['categoria_nombre']) ?></td>
            <td><?= badge_disponibilidad($mat['disponibilidad']) ?></td>
            <td><?= e($mat['region'] ?: 'México') ?></td>
            <td><a class="enlace-flecha" href="<?= url('/materiales/' . $mat['slug']) ?>">Ficha <?= icon('arrow-right') ?></a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="seccion">
  <div class="contenedor">
    <div class="tarjeta" style="padding:clamp(1.8rem,4vw,3rem);background:linear-gradient(135deg,#efe8da,#f9f5ec);border-color:#ded2bd">
      <div class="grid grid--2" style="align-items:center;gap:2rem">
        <div>
          <span class="eyebrow">Compra o vende con soporte técnico</span>
          <h2 style="font-size:clamp(1.6rem,3vw,2.2rem)">¿Tienes un requerimiento o una mina que ofrecer?</h2>
          <p class="texto-muted">Cuéntanos mineral, volumen, especificación y destino. Respondemos en menos de 24 horas hábiles con alternativas concretas.</p>
        </div>
        <div class="fila" style="justify-content:flex-end">
          <a class="boton boton--primario boton--lg" href="<?= url('/soy-comprador') ?>"><?= icon('file-text') ?> Soy comprador</a>
          <a class="boton boton--contorno boton--lg" href="<?= url('/soy-proveedor') ?>"><?= icon('factory') ?> Soy proveedor</a>
        </div>
      </div>
    </div>
  </div>
</section>
