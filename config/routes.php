<?php
/**
 * Rutas de Arcillas MX
 * Formato: $router->get('/ruta', [Controlador::class, 'metodo'], ['auth'=>true, 'roles'=>[...]])
 */
declare(strict_types=1);

use App\Controllers\Admin\AuthController;
use App\Controllers\Admin\CategoriaController;
use App\Controllers\Admin\CatalogosController;
use App\Controllers\Admin\ConfigController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\LeadController as AdminLeadController;
use App\Controllers\Admin\MaterialController as AdminMaterialController;
use App\Controllers\Admin\UsuarioController;
use App\Controllers\CalculadoraController;
use App\Controllers\HomeController;
use App\Controllers\IndustriaController;
use App\Controllers\LeadController;
use App\Controllers\MaterialController;
use App\Controllers\PageController;
use App\Controllers\ProveedorController;
use App\Controllers\SitemapController;
use App\Core\Router;

/** @var Router $router */

// ── Público ────────────────────────────────────────────────────────────
$router->get('/', [HomeController::class, 'index']);
$router->get('/materiales', [MaterialController::class, 'index']);
$router->get('/materiales/{slug}', [MaterialController::class, 'show']);
$router->get('/materiales/{slug}/ficha-tecnica', [MaterialController::class, 'ficha']);
$router->get('/comparar', [MaterialController::class, 'comparar']);
$router->get('/industrias', [IndustriaController::class, 'index']);
$router->get('/industrias/{slug}', [IndustriaController::class, 'show']);
$router->get('/proveedores', [ProveedorController::class, 'index']);
$router->get('/proveedores/{slug}', [ProveedorController::class, 'show']);
$router->get('/calculadoras', [CalculadoraController::class, 'index']);
$router->get('/calculadoras/{slug}', [CalculadoraController::class, 'show']);
$router->post('/api/calculadoras/{slug}', [CalculadoraController::class, 'calcular']);

$router->get('/como-funciona', [PageController::class, 'comoFunciona']);
$router->get('/nosotros', [PageController::class, 'nosotros']);
$router->get('/aviso-de-privacidad', [PageController::class, 'aviso']);
$router->get('/terminos', [PageController::class, 'terminos']);
$router->get('/buscar', [PageController::class, 'buscar']);
$router->get('/robots.txt', [PageController::class, 'robots']);
$router->get('/sitemap.xml', [SitemapController::class, 'index']);

// Formularios de captación
$router->get('/contacto', [LeadController::class, 'contacto']);
$router->post('/contacto', [LeadController::class, 'contactoEnviar']);
$router->get('/soy-comprador', [LeadController::class, 'comprador']);
$router->post('/soy-comprador', [LeadController::class, 'compradorEnviar']);
$router->get('/soy-proveedor', [LeadController::class, 'proveedor']);
$router->post('/soy-proveedor', [LeadController::class, 'proveedorEnviar']);
$router->get('/solicitud', [LeadController::class, 'solicitud']);
$router->post('/solicitud', [LeadController::class, 'solicitudEnviar']);
$router->get('/gracias', [LeadController::class, 'gracias']);
$router->post('/api/solicitud', [LeadController::class, 'apiSolicitud'], ['csrf' => false]);

// ── Admin: autenticación ───────────────────────────────────────────────
$router->get('/admin/login', [AuthController::class, 'showLogin']);
$router->post('/admin/login', [AuthController::class, 'login'], ['csrf' => true]);
$router->post('/admin/logout', [AuthController::class, 'logout'], ['auth' => true]);

// ── Admin: panel ───────────────────────────────────────────────────────
$ADMIN = ['auth' => true, 'roles' => ['admin', 'editor']];
$ADMIN_ONLY = ['auth' => true, 'roles' => ['admin']];

$router->get('/admin', [DashboardController::class, 'index'], $ADMIN);

$router->get('/admin/materiales', [AdminMaterialController::class, 'index'], $ADMIN);
$router->get('/admin/materiales/nuevo', [AdminMaterialController::class, 'nuevo'], $ADMIN);
$router->get('/admin/materiales/{id}/editar', [AdminMaterialController::class, 'editar'], $ADMIN);
$router->post('/admin/materiales/guardar', [AdminMaterialController::class, 'guardar'], $ADMIN);
$router->post('/admin/materiales/{id}/eliminar', [AdminMaterialController::class, 'eliminar'], $ADMIN);
$router->post('/admin/materiales/{id}/activar', [AdminMaterialController::class, 'activar'], $ADMIN);

$router->get('/admin/categorias', [CategoriaController::class, 'index'], $ADMIN);
$router->get('/admin/categorias/nueva', [CategoriaController::class, 'form'], $ADMIN);
$router->get('/admin/categorias/{id}/editar', [CategoriaController::class, 'form'], $ADMIN);
$router->post('/admin/categorias/guardar', [CategoriaController::class, 'guardar'], $ADMIN);
$router->post('/admin/categorias/{id}/eliminar', [CategoriaController::class, 'eliminar'], $ADMIN);

$router->get('/admin/industrias', [CatalogosController::class, 'industrias'], $ADMIN);
$router->get('/admin/industrias/nueva', [CatalogosController::class, 'industriaForm'], $ADMIN);
$router->get('/admin/industrias/{id}/editar', [CatalogosController::class, 'industriaForm'], $ADMIN);
$router->post('/admin/industrias/guardar', [CatalogosController::class, 'industriaGuardar'], $ADMIN);
$router->post('/admin/industrias/{id}/eliminar', [CatalogosController::class, 'industriaEliminar'], $ADMIN);

$router->get('/admin/proveedores', [CatalogosController::class, 'proveedores'], $ADMIN);
$router->get('/admin/proveedores/nuevo', [CatalogosController::class, 'proveedorForm'], $ADMIN);
$router->get('/admin/proveedores/{id}/editar', [CatalogosController::class, 'proveedorForm'], $ADMIN);
$router->post('/admin/proveedores/guardar', [CatalogosController::class, 'proveedorGuardar'], $ADMIN);
$router->post('/admin/proveedores/{id}/eliminar', [CatalogosController::class, 'proveedorEliminar'], $ADMIN);

$router->get('/admin/leads', [AdminLeadController::class, 'index'], $ADMIN);
$router->get('/admin/leads.csv', [AdminLeadController::class, 'exportar'], $ADMIN);
$router->get('/admin/leads/{id}', [AdminLeadController::class, 'show'], $ADMIN);
$router->post('/admin/leads/{id}/actualizar', [AdminLeadController::class, 'actualizar'], $ADMIN);
$router->post('/admin/leads/{id}/eliminar', [AdminLeadController::class, 'eliminar'], $ADMIN_ONLY);

$router->get('/admin/calculadoras', [ConfigController::class, 'calculadoras'], $ADMIN);
$router->get('/admin/configuracion', [ConfigController::class, 'index'], $ADMIN_ONLY);
$router->post('/admin/configuracion', [ConfigController::class, 'guardar'], $ADMIN_ONLY);
$router->get('/admin/usuarios', [UsuarioController::class, 'index'], $ADMIN_ONLY);
$router->post('/admin/usuarios/guardar', [UsuarioController::class, 'guardar'], $ADMIN_ONLY);
$router->post('/admin/usuarios/{id}/eliminar', [UsuarioController::class, 'eliminar'], $ADMIN_ONLY);
