# Despliegue en Hostinger

Este documento describe cómo está desplegado Arcillas MX y cómo reproducir/actualizar
la instalación. No contiene contraseñas: las credenciales reales viven en
`.deploy.env` (SSH) y `.env` (aplicación), ambos fuera de Git.

## 1. Estado actual

| Dato | Valor |
|---|---|
| Plan | Hostinger Business Web Hosting (cuenta `u771496034`) |
| Servidor | `45.93.101.150` |
| SSH | puerto `65002`, usuario `u771496034` (clave/password) |
| Dominio de preview | `floralwhite-meerkat-876567.hostingersite.com` |
| Docroot | `~/domains/floralwhite-meerkat-876567.hostingersite.com/public_html` |
| PHP del servidor | 8.3.36 (CLI verificado) |
| Base de datos | `u771496034_Arcillas` (MariaDB 11.8) |
| Usuario MySQL | `u771496034_Arcillas` (solo localhost del servidor) |

La carpeta `public_html` contiene **todo el proyecto** (no solo `public/`), igual que en
desarrollo: `index.php` y `assets/` en la raíz, con `app/`, `config/`, `database/`,
`tools/`, `storage/` como hermanos. El `.htaccess` bloquea el acceso web a los
directorios internos y a `.env`.

## 2. Despliegue automatizado

```bash
cd arcillas
./tools/deploy.sh            # rsync + .env + permisos + estado de BD
./tools/deploy.sh --status   # solo consulta conteos remotos
./tools/deploy.sh --seed     # re-siembra el catálogo demo en remoto
```

`.deploy.env` (no versionado) define:

```dotenv
HOSTINGER_HOST=45.93.101.150
HOSTINGER_PORT=65002
HOSTINGER_USER=u771496034
HOSTINGER_PASS=...
REMOTE_DIR=domains/floralwhite-meerkat-876567.hostingersite.com/public_html
SITE_URL=https://floralwhite-meerkat-876567.hostingersite.com
```

El script:

1. rsync de `public/` al docroot.
2. rsync de `app/`, `config/`, `database/`, `tools/`, `docs/` como hermanos.
3. Sube `.env.production` como `.env` con permisos `600`.
4. Crea `storage/{logs,cache,uploads}` y `public/uploads/materiales`, ajusta permisos
   (`755` directorios, `644` archivos, `775` storage/uploads, `600` `.env`).
5. Ejecuta `php tools/migrate.php --status` en el servidor para verificar PHP y BD.

### Importante: permisos

LiteSpeed necesita leer `.htaccess`, `index.php` y los assets. Si un archivo sube con
`600`, el rewrite no se aplica y las rutas amigables devuelven 404 aunque `/` funcione.
El script ya normaliza permisos; si despliegas a mano:

```bash
find . -type d -exec chmod 755 {} \;
find . -type f -not -name '.env' -exec chmod 644 {} \;
chmod -R 775 storage public/uploads
chmod 600 .env
```

## 3. Base de datos

- La base y el usuario ya existen en hPanel (MySQL remoto).
- El acceso desde internet está bloqueado; se administra por SSH o por un túnel:

```bash
# túnel local 3307 → MySQL del servidor
ssh -p 65002 -L 3307:127.0.0.1:3306 u771496034@45.93.101.150 -N
```

- Migración y siembra (desde el servidor o por el túnel):

```bash
php tools/migrate.php --fresh --seed   # BORRA TODO: solo primera instalación
php tools/migrate.php --seed           # crea tablas si faltan y siembra
php database/seed.php --force          # actualiza catálogo demo sin borrar leads
php tools/migrate.php --status
```

- El esquema es idempotente (`CREATE TABLE IF NOT EXISTS`). El catálogo se puede
  regenerar con `php tools/generar_seed_sql.php` y `database/seed.sql` es portable.

## 4. Correo

Estado actual: `MAIL_DRIVER=log`. Los correos (notificación interna y acuse al
interesado) se guardan en `storage/logs/mail.log`; **los leads sí se guardan en MySQL**
aunque el correo no salga.

Para activar SMTP:

1. Crea el buzón `contacto@arcillas.mx` en hPanel → Correos.
2. En `.env`: `MAIL_DRIVER=smtp`, `SMTP_PASS=<contraseña del buzón>`.
3. Verifica `SMTP_HOST=smtp.hostinger.com`, `SMTP_PORT=465`, `SMTP_SECURE=ssl`.
4. Configura SPF/DKIM en DNS (hPanel lo hace al crear el buzón).
5. Prueba un formulario y revisa que llegue a `MAIL_TO`.

## 5. Dominio `arcillas.mx`

El sitio opera hoy en el hostname de preview. Para publicarlo en `arcillas.mx`:

1. Compra/agrega el dominio en hPanel y apunta los nameservers de Hostinger (o un
   registro `A` a la IP del hosting).
2. En hPanel → Sitios web → añade `arcillas.mx` y `www.arcillas.mx` al plan.
3. Actualiza `APP_URL=https://arcillas.mx` en `.env.production` y redespliega.
4. Emite el SSL (hPanel → SSL) y fuerza HTTPS; el `.htaccess` ya redirige `www` y HTTP.
5. Verifica `https://arcillas.mx/robots.txt`, `sitemap.xml` y una ficha de material.
6. Actualiza el `canonical`/OG (se generan con `APP_URL`).

### Nota sobre `robots.txt`

Hostinger sirve su propio `/robots.txt` desde el edge (hcdn) mientras el sitio está en
el hostname de preview: actualmente responde `User-agent: Googlebot / Disallow: /`, por
encima del `robots.txt` dinámico de la aplicación (`PageController::robots`). Antes de
publicar, activa la visibilidad en buscadores en hPanel (Sitio web → Avanzado/SEO) y
verifica con `curl -s https://arcillas.mx/robots.txt`; debe mostrar `Allow: /`,
`Disallow: /admin` y la línea `Sitemap:`. El `sitemap.xml` sí lo genera la app.

## 6. Verificación posterior al despliegue

```bash
BASE=https://floralwhite-meerkat-876567.hostingersite.com
for u in / /materiales /materiales/caolin-ceramico-grado-1 /calculadoras \
         /calculadoras/umf-esmalte /proveedores /industrias /contacto \
         /admin/login /sitemap.xml /robots.txt; do
  echo "$u $(curl -s -o /dev/null -w '%{http_code}' "$BASE$u")"
done
# Protecciones esperadas: 403 en /app/bootstrap.php, /.env, /database/seed.sql, /tools/
```

Resultado esperado: `200` en rutas públicas, `404` en slugs inexistentes, `403` en
rutas internas. El login de admin debe redirigir a `/admin` y el dashboard responder 200.

## 7. Solución de problemas

| Síntoma | Causa probable | Acción |
|---|---|---|
| `/` carga pero `/materiales` da 404 | `.htaccess` sin permisos de lectura o rewrite deshabilitado | `chmod 644 .htaccess`; revisar que `mod_rewrite` esté activo |
| Todo da 404 | docroot incorrecto o falta `index.php` | confirmar `REMOTE_DIR` y que exista `index.php` |
| Error 500 | excepción PHP / BD inaccesible | revisar `storage/logs/app-*.log`; poner `APP_DEBUG=true` temporalmente |
| Fichas sin imágenes | faltan permisos en `public/uploads` | `chmod -R 775 public/uploads` |
| Correos no llegan | `MAIL_DRIVER=log` o SMTP mal configurado | revisar `storage/logs/mail.log`, credenciales del buzón y SPF |
| Cambios no se ven | caché de assets (`?v=APP_VERSION`) | subir `APP_VERSION` y redesplegar |
| Login bloqueado | rate limit de 8 intentos/15 min | esperar o borrar `storage/cache/rl-login-*.json` |
| BD vacía tras migrar | se ejecutó `--fresh` sin `--seed` | `php database/seed.php --force` |

## 8. Respaldos y mantenimiento

```bash
# Respaldo de base (desde el servidor)
mysqldump -u u771496034_Arcillas -p u771496034_Arcillas \
  | gzip > ~/backups/arcillas-$(date +%F).sql.gz

# Respaldo de código/uploads
tar czf ~/backups/arcillas-files-$(date +%F).tar.gz \
  ~/domains/floralwhite-meerkat-876567.hostingersite.com/public_html/{app,public/uploads,.env}
```

Programa respaldos semanales y rota `storage/logs/` si crece. Revisa
`/admin/calculadoras` y `/admin/leads` para detectar uso anómalo.
