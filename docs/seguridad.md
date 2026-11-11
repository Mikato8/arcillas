# Seguridad y checklist de pruebas

## 1. Controles implementados

| Área | Implementación |
|---|---|
| Contraseñas | `password_hash`/`password_verify` (bcrypt), rehash automático, mínimo 10 caracteres en el panel |
| Sesiones | cookie `ARCILLAS_SID` con `HttpOnly`, `SameSite=Lax`, `Secure` bajo HTTPS, regeneración al iniciar sesión y al autenticar |
| CSRF | token por sesión, campo oculto en todos los formularios y verificación en el router para todo `POST` (salvo el endpoint público de solicitudes, que usa rate limit) |
| SQL | PDO con consultas preparadas y `ATTR_EMULATE_PREPARES=false`; sin concatenación de entradas |
| XSS | escape `e()` en todas las salidas HTML; JSON con `JSON_HEX_TAG|JSON_HEX_AMP` en Schema.org |
| Spam | honeypot `sitio_web`, rate limit por IP/archivo (5 leads/15 min, 60 cálculos/h, 8 logins/15 min), longitud mínima de mensaje |
| Autorización | sesión + roles `admin`/`editor`; rutas del panel con `auth` y `roles`; acciones destructivas solo `admin` |
| Panel | `noindex,nofollow`, X-Robots-Tag y bloqueo por `.htaccess` de directorios internos |
| Archivos | `.htaccess` deniega `app/ config/ database/ resources/ storage/ tests/ tools/ vendor/`, `.env`, `.sql`, `.md`, `.log`; uploads validados por extensión y tamaño |
| Cabeceras | CSP, `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, HSTS vía `.htaccess` |
| Errores | `APP_DEBUG=false` en producción, mensajes genéricos al usuario y detalle en `storage/logs/app-*.log` |
| Bitácora | tabla `logs` con usuario/acción/entidad/IP; login/logout y cambios de catálogo y leads |
| Legal | consentimiento explícito obligatorio, aviso de privacidad LFPDPPP base y enlaces en formularios |
| Correo | SMTP con TLS (puerto 465), credenciales en `.env` (nunca en BD); modo `log` si no hay buzón |
| Base de datos | usuario de BD dedicado, sin acceso público; `.env` con permisos 600 |

## 2. Evidencia de pruebas (2026-11-11)

Verificado sobre la instancia de producción
`https://arcillas.mx` y el servidor local:

- [x] Portada, catálogo, ficha, calculadoras, industrias, proveedores, contacto y legal → **200**.
- [x] Slugs inexistentes (`/no-existe`, `/calculadoras/no-existe`) → **404** personalizado.
- [x] Rutas internas (`/app/bootstrap.php`, `/database/seed.sql`, `/tools/migrate.php`, `/.env`) → **403**.
- [x] Login de admin → `302 /admin`; dashboard y CRUD → **200**.
- [x] Formulario de lead completo (CSRF + validación + honeypot) → lead guardado en MySQL,
      correo interno y acuse generados, redirección a `/gracias`.
- [x] API de calculadoras con token CSRF → **200** con `ok:true`; datos inválidos → **422**;
      sin token → **419**; slug desconocido → **404**.
- [x] UMF con convención estándar ΣRO = 1: SiO₂ 3.5041 / Al₂O₃ 0.5453 / CaO 0.7571 /
      Si:Al 6.426 (receta 40/20/20/20).
- [x] `php tests/calculators.php` → **321 asserts, 0 fallos**, 8/8 calculadoras.
- [x] `php -l` sin errores en todos los PHP.
- [x] Mitigación de timeouts de Chrome en sandbox con `--no-sandbox` (ver AGENTS.md).

## 3. Checklist antes de operar comercialmente

- [ ] Cambiar la contraseña del admin demo (`admin@arcillas.mx` / `ArcillasAdmin2026$`).
- [ ] Crear usuarios reales con rol mínimo necesario; desactivar los que no se usen.
- [ ] Revisar y sustituir los datos DEMO de proveedores, materiales y precios.
- [ ] Revisión legal del aviso de privacidad y términos (marcadores `[ ]`).
- [ ] Crear buzón `contacto@arcillas.mx`, activar SMTP y probar entrega real + SPF/DKIM.
- [ ] Apuntar `arcillas.mx`, actualizar `APP_URL`, emitir SSL y verificar redirecciones.
- [ ] Programar respaldos de base y de `public/uploads`; probar restauración.
- [ ] Configurar rotación/limpieza de `storage/logs` y `storage/cache`.
- [ ] Revisar cabeceras con `curl -I` y escaneo básico (p. ej. observatorio SSL).
- [ ] Definir política de retención de leads y de datos personales (LFPDPPP).
- [ ] Considerar 2FA para el panel (mejora futura) y WAF de Hostinger.

## 4. Riesgos residuales conocidos

1. **Sin 2FA**: el panel depende solo de contraseña + rate limit. Mitigar con contraseñas
   fuertes y, si se requiere, implementar TOTP.
2. **Sin WAF propio**: se depende de las protecciones de Hostinger y del rate limit en
   archivos; tráfico distribuido podría saturar formularios.
3. **Rate limit por archivo**: usa `storage/cache`; en clústeres no se comparte.
4. **Modo `log` de correo**: no envía notificaciones reales hasta configurar SMTP.
5. **Datos demo**: el catálogo público actual es de demostración, no una oferta comercial.
6. **Aviso legal base**: requiere validación por abogado antes de su publicación definitiva.
