<?php
/**
 * Arcillas MX · Front controller
 *
 * Funciona en dos disposiciones:
 *  1. Desarrollo local:  <raíz>/public/index.php  (código en <raíz>/app)
 *  2. Hostinger:         public_html/index.php    (código en public_html/app)
 */
declare(strict_types=1);

$root = is_dir(__DIR__ . '/../app') ? dirname(__DIR__) : __DIR__;
require $root . '/app/bootstrap.php';
