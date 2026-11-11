<?php
declare(strict_types=1);

use App\Core\Env;
use App\Core\Request;
use App\Core\Settings;

if (!function_exists('e')) {
    /** Escapa texto para HTML. */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $base = APP_URL !== '' ? APP_URL : Request::baseUrl();
        if ($path === '') {
            return $base . '/';
        }
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return url('/assets/' . ltrim($path, '/')) . '?v=' . APP_VERSION;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return App\Core\Csrf::token();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return App\Core\Csrf::field();
    }
}

if (!function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return Settings::get($key, $default);
    }
}

if (!function_exists('empresa_nombre')) {
    function empresa_nombre(): string
    {
        $nombre = trim((string) setting('empresa_nombre', Env::get('EMPRESA_NOMBRE', 'Arcillas MX')));
        return $nombre !== '' ? $nombre : 'Arcillas MX';
    }
}

if (!function_exists('empresa_corto')) {
    function empresa_corto(): string
    {
        return (string) setting('empresa_corto', 'Arcillas MX');
    }
}

if (!function_exists('whatsapp_url')) {
    function whatsapp_url(string $mensaje = ''): string
    {
        $numero = preg_replace('/\D+/', '', (string) setting('empresa_whatsapp', Env::get('EMPRESA_WHATSAPP', '')));
        $texto = $mensaje !== '' ? '?text=' . rawurlencode($mensaje) : '';
        return 'https://wa.me/' . $numero . $texto;
    }
}

if (!function_exists('mailto')) {
    function mailto(string $asunto = ''): string
    {
        $email = (string) setting('empresa_email', Env::get('EMPRESA_EMAIL', 'contacto@arcillas.mx'));
        return 'mailto:' . $email . ($asunto !== '' ? '?subject=' . rawurlencode($asunto) : '');
    }
}

if (!function_exists('fmt_date')) {
    function fmt_date(?string $value, string $format = 'd/m/Y'): string
    {
        if ($value === null || $value === '' || $value === '0000-00-00 00:00:00') {
            return '—';
        }
        $time = strtotime($value);
        return $time === false ? '—' : date($format, $time);
    }
}

if (!function_exists('num')) {
    function num(float|int|string|null $value, int $decimals = 0): string
    {
        $amount = is_numeric($value) ? (float) $value : 0.0;
        return number_format($amount, $decimals, '.', ',');
    }
}

if (!function_exists('pct')) {
    function pct(float|int|string|null $value, int $decimals = 1): string
    {
        return num($value, $decimals) . ' %';
    }
}

if (!function_exists('money')) {
    /** Formatea un precio de referencia; null → "A consultar". */
    function money(float|int|string|null $value, ?string $moneda = null): string
    {
        if ($value === null || $value === '') {
            return 'A consultar';
        }
        $moneda = $moneda ?: 'MXN';
        $simbolo = $moneda === 'USD' ? 'US$' : '$';
        $decimals = ((float) $value) < 100 ? 2 : 0;
        return $simbolo . number_format((float) $value, $decimals, '.', ',') . ' ' . $moneda;
    }
}

if (!function_exists('truncate')) {
    function truncate(?string $text, int $length = 160, string $suffix = '…'): string
    {
        $text = trim((string) $text);
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        $corte = mb_substr($text, 0, $length - 1);
        $espacio = mb_strrpos($corte, ' ');
        if ($espacio !== false && $espacio > $length * 0.55) {
            $corte = mb_substr($corte, 0, $espacio);
        }
        return rtrim($corte, " ,.;:") . $suffix;
    }
}

if (!function_exists('slugify')) {
    function slugify(string $text): string
    {
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
        $text = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $text) ?? '');
        return trim($text, '-');
    }
}

if (!function_exists('is_active')) {
    function is_active(string $prefix): string
    {
        $path = Request::path();
        if ($prefix === '/') {
            return $path === '/' ? 'is-active' : '';
        }
        return str_starts_with($path, $prefix) ? 'is-active' : '';
    }
}

if (!function_exists('canonical')) {
    function canonical(?string $path = null): string
    {
        $path = $path ?? Request::path();
        $clean = '/' . ltrim(parse_url($path, PHP_URL_PATH) ?: '/', '/');
        return url($clean);
    }
}

if (!function_exists('json_ld')) {
    /** @param array<string,mixed> $data */
    function json_ld(array $data): string
    {
        return '<script type="application/ld+json">'
            . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP)
            . '</script>';
    }
}

if (!function_exists('q')) {
    /** Construye una URL conservando los filtros actuales. @param array<string,mixed> $changes */
    function q(array $changes = [], ?string $path = null): string
    {
        $params = array_merge($_GET, $changes);
        $params = array_filter($params, static fn ($v) => $v !== null && $v !== '');
        $path = $path ?? Request::path();
        return url($path . ($params ? '?' . http_build_query($params) : ''));
    }
}

if (!function_exists('badge_disponibilidad')) {
    function badge_disponibilidad(?string $valor): string
    {
        $map = [
            'inmediata'   => ['Disponible inmediata', 'ok'],
            'programada'  => ['Entrega programada', 'info'],
            'bajo_pedido' => ['Bajo pedido', 'warn'],
            'agotado'     => ['Agotado', 'danger'],
        ];
        [$label, $tone] = $map[$valor ?? ''] ?? [ucfirst((string) $valor), 'ghost'];
        return '<span class="badge badge--' . $tone . '">' . e($label) . '</span>';
    }
}

if (!function_exists('badge_lead')) {
    function badge_lead(string $estado): string
    {
        $map = [
            'nuevo'      => ['Nuevo', 'info'],
            'contactado' => ['Contactado', 'warn'],
            'cotizado'   => ['Cotizado', 'accent'],
            'ganado'     => ['Ganado', 'ok'],
            'perdido'    => ['Perdido', 'ghost'],
            'spam'       => ['Spam', 'danger'],
        ];
        [$label, $tone] = $map[$estado] ?? [ucfirst($estado), 'ghost'];
        return '<span class="badge badge--' . $tone . '">' . e($label) . '</span>';
    }
}

if (!function_exists('tipo_lead_label')) {
    function tipo_lead_label(string $tipo): string
    {
        return [
            'cotizacion' => 'Cotización',
            'muestra'    => 'Muestra',
            'contacto'   => 'Contacto',
            'proveedor'  => 'Proveedor',
            'calculadora' => 'Calculadora',
        ][$tipo] ?? ucfirst($tipo);
    }
}

if (!function_exists('prop_valor')) {
    /** Presenta el valor de una propiedad técnica con su unidad. */
    function prop_valor(array $prop): string
    {
        if ($prop['valor'] !== null && $prop['valor'] !== '') {
            $decimales = abs((float) $prop['valor']) < 10 ? 2 : 1;
            return num($prop['valor'], $decimales) . ($prop['unidad'] ? ' ' . $prop['unidad'] : '');
        }
        return (string) ($prop['valor_texto'] ?? '—');
    }
}

if (!function_exists('material_imagen')) {
    /** Imagen del material o arte generado de respaldo. */
    function material_imagen(?array $material): string
    {
        if (!empty($material['imagen'])) {
            return url('/uploads/' . ltrim((string) $material['imagen'], '/'));
        }
        $slug = (string) ($material['categoria_slug'] ?? $material['slug'] ?? 'mineral');
        return url('/assets/img/minerales/' . material_arte($slug) . '.svg');
    }
}

if (!function_exists('material_arte')) {
    /** Devuelve el nombre del SVG de arte para una categoría. */
    function material_arte(string $slug): string
    {
        static $map = [
            'caolin'               => 'caolin',
            'arcillas-plasticas'   => 'arcilla',
            'bentonita'            => 'bentonita',
            'arcilla-refractaria'  => 'refractaria',
            'silice'               => 'silice',
            'feldespatos'          => 'feldespato',
            'talco-pirofilita'     => 'talco',
            'carbonatos'           => 'carbonato',
            'sulfatos'             => 'sulfato',
            'zeolitas'             => 'zeolita',
            'minerales-no-ferrosos' => 'metal',
            'cargas-minerales'     => 'carga',
        ];
        return $map[$slug] ?? 'mineral';
    }
}

if (!function_exists('icon')) {
    /**
     * Iconos SVG inline (24×24, trazo). Sin dependencias ni peticiones externas.
     */
    function icon(string $name, string $class = ''): string
    {
        static $icons = [
            'home'         => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M9.5 21v-6h5v6"/>',
            'cube'         => '<path d="M12 2.8 21 7.4v9.2L12 21.2 3 16.6V7.4l9-4.6Z"/><path d="M3.4 7.6 12 12l8.6-4.4"/><path d="M12 12v9"/>',
            'layers'       => '<path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 13 9 5 9-5"/><path d="m3 17 9 5 9-5"/>',
            'calculator'   => '<rect x="5" y="2.5" width="14" height="19" rx="2"/><path d="M8 6.5h8"/><path d="M8 11h.1"/><path d="M12 11h.1"/><path d="M16 11h.1"/><path d="M8 15h.1"/><path d="M12 15h.1"/><path d="M16 15h.1"/><path d="M8 19h.1"/><path d="M12 19h.1"/><path d="M16 19h.1"/>',
            'factory'      => '<path d="M3 21V9l6 4V9l6 4V4h6v17H3Z"/><path d="M6 21v-4h4v4"/><path d="M14 21v-4h4v4"/>',
            'flask'        => '<path d="M9 3h6"/><path d="M10 3v6L4.8 18a2 2 0 0 0 1.7 3h11a2 2 0 0 0 1.7-3L14 9V3"/><path d="M7 15h10"/>',
            'search'       => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m15.5 15.5 5 5"/>',
            'chevron'      => '<path d="m9 5 7 7-7 7"/>',
            'chevron-down' => '<path d="m5 9 7 7 7-7"/>',
            'arrow-right'  => '<path d="M4 12h16"/><path d="m14 6 6 6-6 6"/>',
            'arrow-left'   => '<path d="M20 12H4"/><path d="m10 6-6 6 6 6"/>',
            'mail'         => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3.5 6.5 8.5 6 8.5-6"/>',
            'phone'        => '<path d="M6.5 3h3l1.5 4-2 1.4a12 12 0 0 0 6.6 6.6l1.4-2 4 1.5v3A2.5 2.5 0 0 1 18.5 21C10 21 3 14 3 5.5A2.5 2.5 0 0 1 6.5 3Z"/>',
            'pin'          => '<path d="M12 21s7-5.6 7-11a7 7 0 1 0-14 0c0 5.4 7 11 7 11Z"/><circle cx="12" cy="10" r="2.6"/>',
            'whatsapp'     => '<path d="M20.5 11.6a8.5 8.5 0 0 1-12.6 7.5L3.5 20.5l1.5-4.3a8.5 8.5 0 1 1 15.5-4.6Z"/><path d="M8.5 8.5c.3 2.6 2.4 4.7 5 5l1-1.3 2 1c0 1.2-1 2.1-2.2 2.1-3.4 0-6.6-3.2-6.6-6.6 0-1.2 1-2.2 2.2-2.2l1 2-1.4 1Z"/>',
            'check'        => '<path d="m4 12.5 5 5L20 6.5"/>',
            'download'     => '<path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/>',
            'file-text'    => '<path d="M6 2.8h8l4 4V21H6V2.8Z"/><path d="M14 3v4h4"/><path d="M9 12h6"/><path d="M9 16h6"/>',
            'shield'       => '<path d="M12 3 4.5 6v5.5c0 4.6 3.2 8.1 7.5 9.5 4.3-1.4 7.5-4.9 7.5-9.5V6L12 3Z"/><path d="m9 12 2 2 4-4"/>',
            'menu'         => '<path d="M4 7h16"/><path d="M4 12h16"/><path d="M4 17h16"/>',
            'x'            => '<path d="M6 6l12 12"/><path d="M18 6 6 18"/>',
            'users'        => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.4 2.9-5.5 6.5-5.5s6.5 2.1 6.5 5.5"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7"/><path d="M17.5 14.8c2.4.5 4 2.2 4 5.2"/>',
            'settings'     => '<circle cx="12" cy="12" r="3.2"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.7 1.7 0 0 0-1.87-.34 1.7 1.7 0 0 0-1 1.55V21a2 2 0 1 1-4 0v-.09a1.7 1.7 0 0 0-1.1-1.55 1.7 1.7 0 0 0-1.87.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-1.55-1H3a2 2 0 1 1 0-4h.09A1.7 1.7 0 0 0 4.6 9a1.7 1.7 0 0 0-.34-1.87l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.6h.09A1.7 1.7 0 0 0 10.1 3.05V3a2 2 0 1 1 4 0v.09A1.7 1.7 0 0 0 15 4.6a1.7 1.7 0 0 0 1.87-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 9v.09c.25.7.83 1.2 1.55 1.26H21a2 2 0 1 1 0 4h-.09a1.7 1.7 0 0 0-1.51 1Z"/>',
            'logout'       => '<path d="M15 4h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4"/><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/>',
            'edit'         => '<path d="M4 20h4l10.5-10.5a2.1 2.1 0 0 0-3-3L5 17v3Z"/><path d="m13.5 5.5 3 3"/>',
            'trash'        => '<path d="M4 7h16"/><path d="M9 7V4h6v3"/><path d="M6.5 7 8 21h8l1.5-14"/><path d="M10 11v6"/><path d="M14 11v6"/>',
            'plus'         => '<path d="M12 5v14"/><path d="M5 12h14"/>',
            'save'         => '<path d="M5 3h11l3 3v15H5V3Z"/><path d="M8 3v6h7V3"/><path d="M8 21v-7h8v7"/>',
            'eye'          => '<path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="3"/>',
            'star'         => '<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2L12 17.3 6.4 20.2l1.1-6.2L3 9.6l6.2-.9L12 3Z"/>',
            'clock'        => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
            'box'          => '<path d="m12 2.8 8 4v10.4l-8 4-8-4V6.8l8-4Z"/><path d="m4 6.9 8 4 8-4"/><path d="M12 10.9v10"/>',
            'truck'        => '<path d="M3 6h11v11H3V6Z"/><path d="M14 9h4l3 3v5h-7"/><circle cx="7" cy="18.5" r="1.8"/><circle cx="17.5" cy="18.5" r="1.8"/>',
            'gauge'        => '<path d="M4 18a8 8 0 1 1 16 0"/><path d="M12 18v-5"/><path d="m12 13 3.5-3.5"/>',
            'scale'        => '<path d="M12 3v18"/><path d="M7 21h10"/><path d="M5 7h14"/><path d="m5 7-2 6h4L5 7Z"/><path d="m19 7-2 6h4l-2-6Z"/>',
            'leaf'         => '<path d="M20 4C11 4 4 8 4 15a5 5 0 0 0 5 5c7 0 11-7 11-16Z"/><path d="M4 20c2-5 5-8 10-10"/>',
            'sparkles'     => '<path d="M12 3l1.8 4.7L18.5 9.5l-4.7 1.8L12 16l-1.8-4.7L5.5 9.5l4.7-1.8L12 3Z"/><path d="M18.5 15.5l.9 2.1 2.1.9-2.1.9-.9 2.1-.9-2.1-2.1-.9 2.1-.9.9-2.1Z"/>',
            'alert'        => '<path d="M12 3 2.5 20h19L12 3Z"/><path d="M12 9v5"/><path d="M12 17.2v.1"/>',
            'info'         => '<circle cx="12" cy="12" r="9"/><path d="M12 11v6"/><path d="M12 7.5v.1"/>',
            'filter'       => '<path d="M3 5h18l-7 8v6l-4 2v-8L3 5Z"/>',
            'globe'        => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3c2.5 2.5 3.8 5.6 3.8 9s-1.3 6.5-3.8 9c-2.5-2.5-3.8-5.6-3.8-9S9.5 5.5 12 3Z"/>',
            'chart'        => '<path d="M4 20V4"/><path d="M4 20h16"/><path d="M8 16v-5"/><path d="M13 16V7"/><path d="M18 16v-8"/>',
        ];
        $path = $icons[$name] ?? $icons['info'];
        return '<svg class="icon ' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
    }
}

if (!function_exists('calculadoras_disponibles')) {
    /** @return array<int,\App\Services\Calculators\CalculatorInterface> */
    function calculadoras_disponibles(): array
    {
        if (!class_exists(\App\Services\Calculators\CalculatorRegistry::class)) {
            return [];
        }
        try {
            return \App\Services\Calculators\CalculatorRegistry::all();
        } catch (\Throwable) {
            return [];
        }
    }
}

if (!function_exists('industria_icono')) {
    function industria_icono(string $slug): string
    {
        static $map = [
            'ceramica'                => 'flask',
            'fundicion'               => 'factory',
            'construccion'            => 'box',
            'pinturas-recubrimientos' => 'layers',
            'plasticos-caucho'        => 'cube',
            'alimentos-bebidas'       => 'leaf',
            'perforacion-mineria'     => 'gauge',
            'agricultura'             => 'leaf',
            'cosmeticos-farmaceutica' => 'sparkles',
            'vidrio'                  => 'star',
        ];
        return $map[$slug] ?? 'factory';
    }
}

if (!function_exists('calculadoras_relacionadas')) {
    /**
     * Calculadoras sugeridas según la categoría del material.
     *
     * @return array<int,\App\Services\Calculators\CalculatorInterface>
     */
    function calculadoras_relacionadas(string $categoriaSlug): array
    {
        $mapa = [
            'caolin'                => ['umf-esmalte', 'mezcla-arcillas', 'contraccion-absorcion', 'formula-estructural-arcillas'],
            'arcillas-plasticas'    => ['mezcla-arcillas', 'contraccion-absorcion', 'formula-estructural-arcillas', 'umf-esmalte'],
            'bentonita'             => ['bentonita-arena-verde', 'mezcla-arcillas', 'precio-normalizado'],
            'arcilla-refractaria'   => ['mezcla-arcillas', 'contraccion-absorcion', 'formula-estructural-arcillas'],
            'silice'                => ['granulometria-stokes', 'precio-normalizado', 'mezcla-arcillas'],
            'feldespatos'           => ['umf-esmalte', 'granulometria-stokes', 'precio-normalizado'],
            'talco-pirofilita'      => ['precio-normalizado', 'granulometria-stokes'],
            'carbonatos'            => ['precio-normalizado', 'granulometria-stokes'],
            'sulfatos'              => ['precio-normalizado', 'granulometria-stokes'],
            'zeolitas'              => ['precio-normalizado', 'granulometria-stokes'],
            'minerales-no-ferrosos' => ['balance-metalurgico', 'precio-normalizado', 'granulometria-stokes'],
            'cargas-minerales'      => ['precio-normalizado', 'granulometria-stokes'],
        ];
        $deseadas = $mapa[$categoriaSlug] ?? ['precio-normalizado', 'granulometria-stokes'];
        $out = [];
        foreach (calculadoras_disponibles() as $calc) {
            if (in_array($calc->slug(), $deseadas, true)) {
                $out[$calc->slug()] = $calc;
            }
        }
        return array_slice(array_values($out), 0, 3);
    }
}

if (!function_exists('es_movil')) {
    /** Detección simple de dispositivo móvil por User-Agent (solo para UI). */
    function es_movil(): bool
    {
        $ua = strtolower((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
        return $ua !== '' && preg_match('/android|iphone|ipad|ipod|mobile|opera mini|iemobile/', $ua) === 1;
    }
}

if (!function_exists('ocultar_whatsapp_flotante')) {
    /** Oculta el botón flotante en pantallas con formularios para no tapar campos. */
    function ocultar_whatsapp_flotante(): bool
    {
        if (!es_movil()) {
            return false;
        }
        $ruta = \App\Core\Request::path();
        if (preg_match('#^/(calculadoras|contacto|soy-comprador|soy-proveedor|solicitud|admin)#', $ruta) === 1) {
            return true;
        }
        // Ficha de material: la barra fija ya ofrece cotizar/muestra.
        return preg_match('#^/materiales/[^/]+$#', $ruta) === 1;
    }
}
