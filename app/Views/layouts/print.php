<?php
/** Layout de impresión (fichas técnicas). @var string $content */
$meta = $meta ?? [];
?>
<!doctype html>
<html lang="es-MX">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($meta['titulo'] ?? 'Ficha técnica') ?></title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
  <style>
    @media print { .no-print { display: none !important; } }
    .ficha-doc { max-width: 900px; margin: 0 auto; padding: 32px 22px 60px; }
  </style>
</head>
<body class="sitio">
  <div class="ficha-doc">
    <div class="fila fila--separada no-print" style="margin-bottom:18px">
      <a class="enlace-flecha" href="<?= url('/materiales/' . ($material['slug'] ?? '')) ?>"><?= icon('arrow-left') ?> Volver a la ficha</a>
      <div class="fila">
        <button class="boton boton--primario boton--sm" onclick="window.print()"><?= icon('file-text') ?> Imprimir / PDF</button>
      </div>
    </div>
    <?= $content ?>
  </div>
</body>
</html>
