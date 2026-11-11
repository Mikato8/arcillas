<?php
/** Layout público principal. @var string $content */
$meta = $meta ?? [];
$titulo = $meta['titulo'] ?? (isset($titulo) ? $titulo . ' · Arcillas MX' : 'Arcillas MX');
$descripcion = $meta['descripcion'] ?? 'Directorio técnico de arcillas y minerales no ferrosos para la industria mexicana.';
$canonical = $meta['canonical'] ?? canonical();
$imagen = $meta['imagen'] ?? url('/assets/img/og-arcillas.png');
$noindex = !empty($meta['noindex']);
$tipo = $meta['tipo'] ?? 'website';
$jsonLd = $meta['json_ld'] ?? [];
?>
<!doctype html>
<html lang="es-MX">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($titulo) ?></title>
  <meta name="description" content="<?= e($descripcion) ?>">
  <link rel="canonical" href="<?= e($canonical) ?>">
  <meta name="robots" content="<?= $noindex ? 'noindex,nofollow' : 'index,follow,max-image-preview:large' ?>">
  <meta name="theme-color" content="#2b2621">
  <meta property="og:locale" content="es_MX">
  <meta property="og:type" content="<?= e($tipo) ?>">
  <meta property="og:site_name" content="Arcillas MX">
  <meta property="og:title" content="<?= e($titulo) ?>">
  <meta property="og:description" content="<?= e($descripcion) ?>">
  <meta property="og:url" content="<?= e($canonical) ?>">
  <meta property="og:image" content="<?= e($imagen) ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= e($titulo) ?>">
  <meta name="twitter:description" content="<?= e($descripcion) ?>">
  <link rel="icon" href="<?= url('/favicon.svg') ?>" type="image/svg+xml">
  <link rel="apple-touch-icon" href="<?= url('/favicon.svg') ?>">
  <link rel="preload" href="<?= asset('css/app.css') ?>" as="style">
  <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
  <?php foreach ($jsonLd as $schema): ?>
  <?= json_ld($schema) ?>
  <?php endforeach; ?>
</head>
<body class="sitio">
  <a class="saltar-contenido" href="#contenido">Saltar al contenido</a>
  <?php require APP_BASE . '/app/Views/partials/header.php'; ?>
  <main id="contenido">
    <?= $content ?>
  </main>
  <?php require APP_BASE . '/app/Views/partials/footer.php'; ?>
  <a class="boton-flotante-wa" href="<?= e(whatsapp_url('Hola, quiero información sobre arcillas y minerales.')) ?>"
     target="_blank" rel="noopener" aria-label="Escribir por WhatsApp">
    <?= icon('whatsapp') ?>
  </a>
  <script src="<?= asset('js/app.js') ?>" defer></script>
</body>
</html>
