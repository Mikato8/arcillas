<?php
/** Layout del panel. @var string $content */
$meta = $meta ?? [];
$usuario = $usuario ?? [];
$ruta = App\Core\Request::path();
$nav = [
    ['/admin', 'home', 'Resumen', true],
    ['/admin/materiales', 'cube', 'Materiales'],
    ['/admin/categorias', 'layers', 'Categorías'],
    ['/admin/industrias', 'factory', 'Industrias'],
    ['/admin/proveedores', 'users', 'Proveedores'],
    ['/admin/leads', 'mail', 'Leads'],
    ['/admin/calculadoras', 'calculator', 'Calculadoras'],
];
$navAdmin = [
    ['/admin/configuracion', 'settings', 'Configuración'],
    ['/admin/usuarios', 'shield', 'Usuarios'],
];
$activo = static function (string $base) use ($ruta): string {
    if ($base === '/admin') {
        return $ruta === '/admin' ? 'is-active' : '';
    }
    return str_starts_with($ruta, $base) ? 'is-active' : '';
};
?>
<!doctype html>
<html lang="es-MX">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($meta['titulo'] ?? 'Panel · Arcillas MX') ?></title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="icon" href="<?= url('/favicon.svg') ?>" type="image/svg+xml">
  <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="admin-body">
  <div class="admin-shell">
    <aside class="admin-sidebar">
      <a class="marca" href="<?= url('/admin') ?>">
        <img class="marca__simbolo" src="<?= url('/assets/img/marca.svg') ?>" alt="" width="42" height="42">
        <span class="marca__texto">
          <span class="marca__nombre">Arcillas MX</span>
          <span class="marca__bajada">Panel de control</span>
        </span>
      </a>
      <nav class="admin-nav" aria-label="Navegación del panel">
        <?php foreach ($nav as $item): ?>
          <a class="<?= $activo($item[0]) ?>" href="<?= url($item[0]) ?>"><?= icon($item[1]) ?> <?= e($item[2]) ?></a>
        <?php endforeach; ?>
        <?php if (App\Core\Auth::is('admin')): ?>
          <span class="admin-nav__grupo">Administración</span>
          <?php foreach ($navAdmin as $item): ?>
            <a class="<?= $activo($item[0]) ?>" href="<?= url($item[0]) ?>"><?= icon($item[1]) ?> <?= e($item[2]) ?></a>
          <?php endforeach; ?>
        <?php endif; ?>
      </nav>
      <div class="admin-sidebar__pie">
        <p class="sin-margen"><strong style="color:#f0e8d9"><?= e($usuario['nombre'] ?? 'Usuario') ?></strong></p>
        <p class="sin-margen"><?= e($usuario['email'] ?? '') ?> · <?= e(ucfirst((string) ($usuario['rol'] ?? ''))) ?></p>
        <div class="fila" style="margin-top:.7rem">
          <a href="<?= url('/') ?>" target="_blank" rel="noopener">Ver sitio</a>
          <form method="post" action="<?= url('/admin/logout') ?>" style="margin:0">
            <?= csrf_field() ?>
            <button class="boton boton--fantasma boton--sm" type="submit"><?= icon('logout') ?> Salir</button>
          </form>
        </div>
      </div>
    </aside>
    <div class="admin-main">
      <header class="admin-topbar">
        <h1><?= e($titulo ?? $meta['titulo'] ?? 'Panel') ?></h1>
        <div class="admin-topbar__acciones">
          <?php if (App\Core\Auth::is('admin')): ?>
            <a class="boton boton--contorno boton--sm" href="<?= url('/admin/materiales/nuevo') ?>"><?= icon('plus') ?> Nuevo material</a>
          <?php endif; ?>
          <form method="post" action="<?= url('/admin/logout') ?>" class="admin-salir-movil" style="margin:0">
            <?= csrf_field() ?>
            <button class="boton boton--contorno boton--sm" type="submit" aria-label="Cerrar sesión"><?= icon('logout') ?> Salir</button>
          </form>
        </div>
      </header>
      <div class="admin-contenido">
        <?php if (!empty($flash_ok)): ?><div class="aviso-exito" style="margin-bottom:1.2rem"><?= e($flash_ok) ?></div><?php endif; ?>
        <?php if (!empty($flash_error)): ?><div class="aviso-error" style="margin-bottom:1.2rem"><?= e($flash_error) ?></div><?php endif; ?>
        <?= $content ?>
      </div>
    </div>
  </div>
</body>
</html>
