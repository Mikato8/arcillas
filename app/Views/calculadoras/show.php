<?php
/** Vista genérica de calculadora. */
$campos = $campos ?? [];
$gruposCampos = [];
foreach ($campos as $campo) {
    $grupo = $campo['grupo'] ?? 'Datos';
    $gruposCampos[$grupo][] = $campo;
}
?>
<div class="contenedor">
  <nav class="migajas" aria-label="Ruta">
    <a href="<?= url('/') ?>">Inicio</a><span class="sep">/</span>
    <a href="<?= url('/calculadoras') ?>">Calculadoras</a><span class="sep">/</span><span><?= e($calc->nombre()) ?></span>
  </nav>
</div>

<section class="seccion seccion--ajustada" style="padding-top:.8rem">
  <div class="contenedor">
    <div class="encabezado-seccion" style="margin-bottom:1.6rem">
      <div class="encabezado-seccion__texto">
        <span class="eyebrow"><?= e($calc->categoria()) ?></span>
        <h1 style="font-size:clamp(1.8rem,3.4vw,2.6rem);margin-bottom:.4rem"><?= e($calc->nombre()) ?></h1>
        <p style="max-width:70ch"><?= e($calc->descripcion()) ?></p>
      </div>
    </div>

    <div class="calc-layout" data-calculadora="<?= e($calc->slug()) ?>" data-endpoint="<?= url('/api/calculadoras/' . $calc->slug()) ?>">
      <form class="calc-panel" id="form-calculadora" method="post" action="<?= e(url('/api/calculadoras/' . $calc->slug())) ?>">
        <?= csrf_field() ?>
        <?php if ($gruposCampos === []): ?>
          <p class="texto-muted">Esta calculadora aún no tiene campos definidos.</p>
        <?php endif; ?>
        <?php foreach ($gruposCampos as $grupo => $lista): ?>
          <h2 style="font-size:1rem;margin:1.2rem 0 .9rem;<?= $grupo === array_key_first($gruposCampos) ? 'margin-top:0' : '' ?>"><?= e($grupo) ?></h2>
          <div class="campos-grid">
            <?php foreach ($lista as $campo): ?>
              <?php
              $clave = (string) ($campo['clave'] ?? '');
              $tipo = (string) ($campo['tipo'] ?? 'number');
              $requerido = !empty($campo['requerido']);
              $defecto = $campo['defecto'] ?? '';
              $unidad = (string) ($campo['unidad'] ?? '');
              $ancho = !empty($campo['ancho']) || $tipo === 'textarea' ? ' campo--ancho' : '';
              ?>
              <div class="campo<?= $ancho ?>" data-campo="<?= e($clave) ?>">
                <label for="campo-<?= e($clave) ?>"><?= e($campo['etiqueta'] ?? $clave) ?><?= $requerido ? ' *' : '' ?></label>
                <?php if ($tipo === 'select'): ?>
                  <select id="campo-<?= e($clave) ?>" name="<?= e($clave) ?>" <?= $requerido ? 'required' : '' ?>>
                    <?php foreach (($campo['opciones'] ?? []) as $valor => $etiqueta): ?>
                      <?php
                      if (is_array($etiqueta)) {
                          $v = (string) ($etiqueta['valor'] ?? '');
                          $texto = (string) ($etiqueta['etiqueta'] ?? $v);
                      } else {
                          $v = (string) (is_int($valor) ? $etiqueta : $valor);
                          $texto = (string) $etiqueta;
                      }
                      ?>
                      <option value="<?= e($v) ?>" <?= (string) $defecto === $v ? 'selected' : '' ?>><?= e($texto) ?></option>
                    <?php endforeach; ?>
                  </select>
                <?php elseif ($tipo === 'textarea'): ?>
                  <textarea id="campo-<?= e($clave) ?>" name="<?= e($clave) ?>" rows="4"><?= e((string) $defecto) ?></textarea>
                <?php elseif ($tipo === 'number'): ?>
                  <div class="campo__unidad">
                    <input id="campo-<?= e($clave) ?>" name="<?= e($clave) ?>" type="number"
                           value="<?= e((string) $defecto) ?>"
                           <?= isset($campo['min']) ? 'min="' . e((string) $campo['min']) . '"' : '' ?>
                           <?= isset($campo['max']) ? 'max="' . e((string) $campo['max']) . '"' : '' ?>
                           step="<?= e((string) ($campo['paso'] ?? 'any')) ?>"
                           <?= $requerido ? 'required' : '' ?>>
                    <?php if ($unidad !== ''): ?><span><?= e($unidad) ?></span><?php endif; ?>
                  </div>
                <?php else: ?>
                  <input id="campo-<?= e($clave) ?>" name="<?= e($clave) ?>" type="text" value="<?= e((string) $defecto) ?>" <?= $requerido ? 'required' : '' ?>>
                <?php endif; ?>
                <?php if (!empty($campo['ayuda'])): ?><span class="ayuda"><?= e($campo['ayuda']) ?></span><?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>

        <div class="fila" style="margin-top:1.4rem">
          <button class="boton boton--primario" type="submit" id="boton-calcular"><?= icon('calculator') ?> Calcular</button>
          <button class="boton boton--contorno" type="reset">Limpiar</button>
          <?php if (!empty($ejemplo)): ?>
            <button class="boton boton--fantasma" type="button" id="cargar-ejemplo" data-ejemplo="<?= e(json_encode($ejemplo, JSON_UNESCAPED_UNICODE)) ?>"><?= icon('sparkles') ?> Cargar ejemplo</button>
          <?php endif; ?>
          <span class="texto-pequeno texto-muted" id="calc-estado"></span>
        </div>
        <div class="aviso-error oculto" id="calc-error" style="margin-top:1rem"></div>
      </form>

      <aside class="calc-panel calc-panel--resultado" id="calc-resultado" aria-live="polite">
        <h2>Resultado</h2>
        <div class="resultado-vacio" id="calc-vacio">
          <?= icon('gauge') ?>
          <p class="sin-margen">Completa los datos y presiona <strong>Calcular</strong>.<br>El resultado aparece aquí sin recargar la página.</p>
        </div>
        <div id="calc-salida" class="oculto">
          <p class="texto-mono" style="color:#b8b0a2;margin:0 0 .4rem" id="calc-resumen"></p>
          <div class="resultado-lista" id="calc-lista"></div>
          <div class="advertencias oculto" id="calc-advertencias"></div>
          <details class="formulas" id="calc-formulas">
            <summary>Fórmulas utilizadas</summary>
            <div id="calc-formulas-lista"></div>
          </details>
        </div>
        <p class="texto-pequeno" style="color:#a9a193;margin-top:1.2rem">Los resultados son orientativos y no sustituyen pruebas de laboratorio ni una cotización formal.</p>
      </aside>
    </div>

    <div class="tarjeta" style="padding:1.2rem 1.4rem;margin-top:1.6rem;background:#f2eee4">
      <div class="fila fila--separada">
        <div>
          <h3 style="font-size:1rem;margin-bottom:.2rem">¿Necesitas el material para ejecutar el cálculo?</h3>
          <p class="texto-pequeno texto-muted sin-margen">Busca por composición y solicita muestra o cotización con un clic.</p>
        </div>
        <a class="boton boton--primario" href="<?= url('/materiales') ?>"><?= icon('layers') ?> Explorar materiales</a>
      </div>
    </div>

    <?php if ($relacionadas !== []): ?>
      <div style="margin-top:2.2rem">
        <span class="eyebrow">Sigue calculando</span>
        <div class="grid grid--3">
          <?php foreach (array_slice($relacionadas, 0, 3) as $c): ?>
            <a class="tarjeta tarjeta-calc" href="<?= url('/calculadoras/' . $c->slug()) ?>">
              <span class="tarjeta-calc__icono"><?= icon('calculator') ?></span>
              <h3><?= e($c->nombre()) ?></h3>
              <p><?= e(truncate($c->descripcion(), 90)) ?></p>
              <span class="tarjeta-calc__meta"><span><?= e($c->categoria()) ?></span><?= icon('arrow-right') ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>
