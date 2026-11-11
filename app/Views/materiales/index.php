<?php
/** Catálogo / buscador de materiales. */
$claves = App\Models\Material::propiedadesClave(array_column($resultado['items'], 'id'));
$activos = [];
if (($filtros['q'] ?? '') !== '') {
    $activos[] = ['q', 'Buscar: “' . $filtros['q'] . '”'];
}
foreach ([
    'categoria' => 'Categoría',
    'industria' => 'Industria',
    'disponibilidad' => 'Disponibilidad',
] as $clave => $etiqueta) {
    if (!empty($filtros[$clave])) {
        $activos[] = [$clave, $etiqueta . ': ' . $filtros[$clave]];
    }
}
foreach (['al2o3_min' => 'Al₂O₃ ≥', 'fe2o3_max' => 'Fe₂O₃ ≤', 'blancura_min' => 'Blancura ≥', 'humedad_max' => 'Humedad ≤'] as $clave => $etiqueta) {
    if (($filtros[$clave] ?? '') !== '') {
        $activos[] = [$clave, $etiqueta . ' ' . $filtros[$clave] . ' %'];
    }
}
?>
<div class="contenedor">
  <nav class="migajas" aria-label="Ruta">
    <a href="<?= url('/') ?>">Inicio</a><span class="sep">/</span><span>Materiales</span>
  </nav>
</div>

<section class="seccion seccion--ajustada" style="padding-top:1rem">
  <div class="contenedor">
    <div class="encabezado-seccion" style="margin-bottom:1.4rem">
      <div class="encabezado-seccion__texto">
        <span class="eyebrow">Directorio técnico</span>
        <h2 style="font-size:clamp(1.6rem,3vw,2.2rem);margin-bottom:.4rem"><?= e($titulo) ?></h2>
        <p>Filtra por composición, disponibilidad o aplicación. Cada ficha incluye propiedades, proveedores y documentos.</p>
      </div>
    </div>

    <div class="layout-busqueda">
      <aside class="panel-filtros" id="panel-filtros" aria-label="Filtros de búsqueda">
        <form method="get" action="<?= url('/materiales') ?>" id="form-filtros">
          <h3>Palabra clave</h3>
          <div class="filtro-rango">
            <input type="search" name="q" value="<?= e($filtros['q'] ?? '') ?>" placeholder="Ej. caolín, bentonita…" aria-label="Palabra clave">
          </div>

          <h3>Aplicación / industria</h3>
          <select name="industria" class="campo" style="width:100%;padding:.55rem;border:1px solid var(--linea-fuerte);border-radius:8px;background:#fff">
            <option value="">Todas las industrias</option>
            <?php foreach ($industrias as $ind): ?>
              <option value="<?= e($ind['slug']) ?>" <?= ($filtros['industria'] ?? '') === $ind['slug'] ? 'selected' : '' ?>><?= e($ind['nombre']) ?></option>
            <?php endforeach; ?>
          </select>

          <h3>Familia de material</h3>
          <div class="grupo-filtro">
            <label><input type="radio" name="categoria" value="" <?= empty($filtros['categoria']) ? 'checked' : '' ?>> Todas</label>
            <?php foreach ($categorias as $cat): ?>
              <label><input type="radio" name="categoria" value="<?= e($cat['slug']) ?>" <?= ($filtros['categoria'] ?? '') === $cat['slug'] ? 'checked' : '' ?>> <?= e($cat['nombre']) ?> <span class="texto-mono" style="color:var(--muted)">(<?= (int) $cat['total'] ?>)</span></label>
            <?php endforeach; ?>
          </div>

          <h3>Disponibilidad</h3>
          <select name="disponibilidad" style="width:100%;padding:.55rem;border:1px solid var(--linea-fuerte);border-radius:8px;background:#fff">
            <option value="">Cualquiera</option>
            <?php foreach (['inmediata' => 'Inmediata', 'programada' => 'Programada', 'bajo_pedido' => 'Bajo pedido'] as $valor => $etiqueta): ?>
              <option value="<?= $valor ?>" <?= ($filtros['disponibilidad'] ?? '') === $valor ? 'selected' : '' ?>><?= $etiqueta ?></option>
            <?php endforeach; ?>
          </select>

          <h3>Propiedades técnicas</h3>
          <div style="display:grid;gap:.5rem">
            <label class="texto-pequeno" style="color:var(--muted)">Al₂O₃ mínimo (%)
              <input type="number" step="0.1" name="al2o3_min" value="<?= e((string) ($filtros['al2o3_min'] ?? '')) ?>" placeholder="ej. 30" style="width:100%;padding:.5rem .6rem;border:1px solid var(--linea-fuerte);border-radius:8px;margin-top:.2rem">
            </label>
            <label class="texto-pequeno" style="color:var(--muted)">Fe₂O₃ máximo (%)
              <input type="number" step="0.01" name="fe2o3_max" value="<?= e((string) ($filtros['fe2o3_max'] ?? '')) ?>" placeholder="ej. 1.0" style="width:100%;padding:.5rem .6rem;border:1px solid var(--linea-fuerte);border-radius:8px;margin-top:.2rem">
            </label>
            <label class="texto-pequeno" style="color:var(--muted)">Blancura mínima (%)
              <input type="number" step="1" name="blancura_min" value="<?= e((string) ($filtros['blancura_min'] ?? '')) ?>" placeholder="ej. 80" style="width:100%;padding:.5rem .6rem;border:1px solid var(--linea-fuerte);border-radius:8px;margin-top:.2rem">
            </label>
            <label class="texto-pequeno" style="color:var(--muted)">Humedad máxima (%)
              <input type="number" step="0.1" name="humedad_max" value="<?= e((string) ($filtros['humedad_max'] ?? '')) ?>" placeholder="ej. 5" style="width:100%;padding:.5rem .6rem;border:1px solid var(--linea-fuerte);border-radius:8px;margin-top:.2rem">
            </label>
          </div>

          <input type="hidden" name="orden" value="<?= e($filtros['orden'] ?? 'relevancia') ?>">
          <div class="fila" style="margin-top:1.2rem">
            <button class="boton boton--primario boton--bloque" type="submit"><?= icon('filter') ?> Aplicar filtros</button>
          </div>
          <div class="fila" style="margin-top:.6rem">
            <a class="enlace-flecha texto-pequeno" href="<?= url('/materiales') ?>">Limpiar filtros</a>
          </div>
        </form>
      </aside>

      <div>
        <div class="resultados-cabecera">
          <p><strong><?= num($resultado['total']) ?></strong> materiales encontrados<?= $resultado['paginas'] > 1 ? ' · página ' . $resultado['pagina'] . ' de ' . $resultado['paginas'] : '' ?></p>
          <form method="get" action="<?= url('/materiales') ?>" class="orden-select">
            <?php foreach ($filtros as $nombreFiltro => $valorFiltro): ?>
              <?php if ($nombreFiltro === 'orden' || $nombreFiltro === 'pagina' || $valorFiltro === '' || $valorFiltro === null) continue; ?>
              <input type="hidden" name="<?= e($nombreFiltro) ?>" value="<?= e((string) $valorFiltro) ?>">
            <?php endforeach; ?>
            <label for="orden">Ordenar por</label>
            <select id="orden" name="orden" data-auto-enviar>
              <?php foreach (['relevancia' => 'Relevancia', 'nombre' => 'Nombre (A-Z)', 'recientes' => 'Más recientes', 'precio_asc' => 'Precio: menor a mayor', 'precio_desc' => 'Precio: mayor a menor', 'vistos' => 'Más vistos'] as $valor => $etiqueta): ?>
                <option value="<?= $valor ?>" <?= ($filtros['orden'] ?? 'relevancia') === $valor ? 'selected' : '' ?>><?= $etiqueta ?></option>
              <?php endforeach; ?>
            </select>
          </form>
        </div>

        <?php if ($activos !== []): ?>
          <div class="filtros-activos">
            <?php foreach ($activos as [$clave, $texto]): ?>
              <a class="filtro-chip" href="<?= e(q([$clave => null, 'pagina' => null])) ?>"><?= e($texto) ?> <?= icon('x') ?></a>
            <?php endforeach; ?>
            <a class="filtro-chip" style="background:#f1e7da;color:#7a4a1c" href="<?= url('/materiales') ?>">Limpiar todo</a>
          </div>
        <?php endif; ?>

        <?php if ($resultado['items'] === []): ?>
          <div class="tarjeta" style="padding:2.4rem;text-align:center">
            <span class="tarjeta-calc__icono" style="margin:0 auto 1rem"><?= icon('search') ?></span>
            <h3>Sin resultados con esos filtros</h3>
            <p class="texto-muted">Prueba con menos criterios o cuéntanos qué necesitas: buscamos el material en la red de proveedores.</p>
            <div class="fila" style="justify-content:center">
              <a class="boton boton--contorno" href="<?= url('/materiales') ?>">Limpiar filtros</a>
              <a class="boton boton--primario" href="<?= url('/soy-comprador') ?>"><?= icon('file-text') ?> Pedir búsqueda</a>
            </div>
          </div>
        <?php else: ?>
          <div class="grid grid--3">
            <?php foreach ($resultado['items'] as $material): ?>
              <?php $clavesMaterial = $claves[(int) $material['id']] ?? []; $claves = $clavesMaterial; $mostrarComparar = true; ?>
              <?php require APP_BASE . '/app/Views/partials/material-card.php'; ?>
            <?php endforeach; ?>
          </div>
          <?php $pagina = $resultado['pagina']; $paginas = $resultado['paginas']; require APP_BASE . '/app/Views/partials/paginacion.php'; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<div class="barra-comparar oculto" id="barra-comparar" aria-live="polite">
  <span class="texto-pequeno">Comparar:</span>
  <span class="chips" id="comparar-chips"></span>
  <span class="fila" style="gap:.4rem">
    <button class="boton boton--fantasma boton--sm" type="button" style="color:#e8dfd2" data-comparar-limpiar>Limpiar</button>
    <a class="boton boton--claro boton--sm" id="comparar-ir" href="<?= url('/comparar') ?>">Comparar <?= icon('arrow-right') ?></a>
  </span>
</div>
