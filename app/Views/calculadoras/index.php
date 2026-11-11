<?php
/** Índice de calculadoras. */
$iconosCategoria = [
    'Cerámica'   => 'flask',
    'Arcillas'   => 'layers',
    'Metalurgia' => 'scale',
    'Procesos'   => 'gauge',
    'Comercial'  => 'chart',
];
?>
<section class="seccion seccion--ajustada" style="padding-top:1.6rem">
  <div class="contenedor">
    <div class="tarjeta" style="padding:clamp(1.8rem,4vw,2.8rem);background:linear-gradient(135deg,#2f2b24,#3d372d);color:#f3efe7;border:0;margin-bottom:2rem;position:relative;overflow:hidden">
      <div style="position:relative;z-index:1;max-width:720px">
        <span class="eyebrow eyebrow--claro">Herramientas técnicas gratuitas</span>
        <h1 style="color:#fbf7ef;font-size:clamp(1.9rem,3.6vw,2.8rem);margin-bottom:.5rem">Calculadoras de arcillas y minerales no ferrosos</h1>
        <p style="color:#c9c1b2;margin-bottom:1.4rem">Formulación de esmaltes, mezcla de arcillas, contracción, balance metalúrgico de concentrados, dosificación de bentonita, granulometría, precio normalizado y fórmula estructural. Sin registro y con las fórmulas explicadas.</p>
        <div class="fila">
          <a class="boton boton--claro" href="#calculadoras-lista">Ver las 8 calculadoras <?= icon('arrow-right') ?></a>
          <a class="boton boton--fantasma" style="color:#e8dfd2" href="<?= url('/materiales') ?>">Buscar material</a>
        </div>
        <div class="fila" style="margin-top:1.4rem;gap:1.2rem">
          <span class="texto-mono" style="color:#b8b0a2"><?= num($usos) ?> cálculos realizados</span>
          <span class="texto-mono" style="color:#b8b0a2">Fórmulas auditables</span>
        </div>
      </div>
    </div>

    <div class="encabezado-seccion" id="calculadoras-lista">
      <div class="encabezado-seccion__texto">
        <span class="eyebrow">Elige una herramienta</span>
        <h2>Las 8 calculadoras</h2>
      </div>
    </div>

    <div class="grid grid--3">
      <?php foreach ($calculadoras as $calc): ?>
        <a class="tarjeta tarjeta-calc" href="<?= url('/calculadoras/' . $calc->slug()) ?>">
          <span class="tarjeta-calc__icono"><?= icon($iconosCategoria[$calc->categoria()] ?? 'calculator') ?></span>
          <h3><?= e($calc->nombre()) ?></h3>
          <p><?= e($calc->descripcion()) ?></p>
          <span class="tarjeta-calc__meta"><span><?= e($calc->categoria()) ?></span><?= icon('arrow-right') ?></span>
        </a>
      <?php endforeach; ?>
    </div>

    <?php if ($populares !== []): ?>
      <div class="tarjeta" style="padding:1.3rem 1.4rem;margin-top:1.8rem;background:#f2eee4">
        <h3 style="font-size:.95rem;margin-bottom:.7rem">Las más usadas</h3>
        <div class="fila">
          <?php foreach ($populares as $pop): ?>
            <a class="chip" href="<?= url('/calculadoras/' . $pop['calculadora']) ?>"><?= icon('calculator') ?> <?= e($pop['calculadora']) ?> · <?= (int) $pop['usos'] ?> usos</a>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>
