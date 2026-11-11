<?php
/** Layout de autenticación. @var string $content */
$meta = $meta ?? [];
?>
<!doctype html>
<html lang="es-MX">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($meta['titulo'] ?? 'Acceso · Arcillas MX') ?></title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="icon" href="<?= url('/favicon.svg') ?>" type="image/svg+xml">
  <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="admin-login">
  <?= $content ?>
</body>
</html>
