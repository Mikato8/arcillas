# Integración de IA con Facebook para Arcillas MX

> **Cuenta identificada:** Page pública **“Arcillas y minerales no ferrosos”**, ID
> `61596741170224` (`facebook.com/profile.php?id=61596741170224`). El enlace es una
> **Página de Facebook** (no un perfil personal), por lo que sí se puede automatizar con
> las APIs oficiales de Meta.
>
> **Objetivo:** una IA que redacte y programe publicaciones, responda/gestione la
> comunidad y ayude a crecer de forma **compatible con las políticas de Meta**.

---

## 0. Resumen ejecutivo

Hay dos caminos, y no son excluyentes:

| | Ruta A · Sin código | Ruta B · Integrada en arcillas.mx |
|---|---|---|
| **Cómo** | Meta Business Suite + IA generativa + Make/Zapier/n8n | App propia de Meta + Graph API + módulo en el panel admin |
| **Tiempo** | 1–3 días | 3–6 semanas (incluye revisión de Meta) |
| **Costo** | $0–25 USD/mes (herramientas + IA) | $0 en APIs de Meta + costo de IA + desarrollo |
| **Control** | Medio | Total (flujo, datos, métricas, marca) |
| **Requisito** | Acceso admin a la Page | Verificación de negocio + App Review |
| **Cuándo usarla** | Empezar a publicar ya | Cuando el volumen y la operación lo justifiquen |

**Recomendación:** arrancar con la **Ruta A esta semana** (contenido y calendario con IA)
y, en paralelo, preparar la **Ruta B** para que el panel de arcillas.mx genere borradores,
los apruebe un humano y publique/programe solo, con métricas integradas.

> ⚠️ **Crecimiento real, no artificial:** comprar seguidores, usar bots de seguidores,
> *follow/unfollow* masivo, pods de engagement o scraping **violan las políticas de Meta**
> y pueden restringir o cerrar la Page. El crecimiento que sí funciona es contenido +
> comunidad + anuncios + multicanal. La IA acelera la producción y el análisis, no sustituye
> la estrategia ni compra audiencia.

---

## 1. Diagnóstico y requisitos previos (Meta)

1. **Tipo de cuenta:** ya es una Page. Verifica que tu usuario sea **administrador** de la
   Page (Configuración → Roles de la Page) y que la Page esté dentro de un **portafolio
   empresarial** (`business.facebook.com`).
2. **Portafolio empresarial (Business Portfolio):** créalo o usa el existente; agrega la
   Page, la cuenta de Instagram (si aplica), WhatsApp Business y el dominio `arcillas.mx`.
3. **Verificación del negocio:** en Business Settings → Centro de seguridad. Meta pide
   documentos fiscales/legales (RFC, acta, comprobante de domicilio). Es requisito para
   permisos avanzados y para anuncios.
4. **Dominio:** verifica `arcillas.mx` en Business Settings → Seguridad de la marca →
   Dominios (meta-etiqueta o DNS). Ya tienes HTTPS y aviso de privacidad públicos, que Meta
   revisa.
5. **2FA y roles:** activa doble factor en las cuentas administradoras; usa roles mínimos
   (no compartas contraseñas; se usan roles del portafolio).
6. **Activos de marca:** logo, fotografías reales de minerales/procesos, tono de voz,
   datos de contacto (`contacto@arcillas.mx`, WhatsApp `+52 33 1713 5769`).

---

## 2. Ruta A · Publicar con IA sin desarrollar (rápida)

**Objetivo:** tener un calendario editorial asistido por IA y publicar de forma constante
en 1–3 días.

### Opción A1 · Meta Business Suite + IA de escritorio

1. En **Meta Business Suite** → Publicaciones programadas, agenda contenido nativo.
2. Usa un asistente de IA (ChatGPT, Claude o DeepSeek) con el *prompt maestro* de marca
   (sección 5) para generar textos, variantes y calendario.
3. Genera/edita imágenes con las fotos reales del catálogo y las fichas de arcillas.mx.
4. Programa 3–5 publicaciones/semana y revisa métricas semanalmente.

**Ventaja:** cero desarrollo y usa las herramientas nativas de Meta.
**Límite:** la IA no “opera” la cuenta sola; tú copias/pegas o programas.

### Opción A2 · Automatización low-code (Make / Zapier / n8n)

1. Crea una cuenta en Make (o Zapier) y conecta el conector oficial **Facebook Pages**
   (está aprobado por Meta; no requiere crear app propia ni App Review).
2. Flujo típico:
   - Disparador: Google Sheets/Notion con la fila “idea + material + fecha”.
   - Acción 1: OpenAI/DeepSeek genera copy con el prompt de marca.
   - Acción 2: Facebook Pages → *Create a Post* (mensaje + enlace + imagen).
   - Acción 3 (opcional): guarda el `post_id` y métricas en la hoja.
3. Programa la ejecución diaria/semanal. Añade un paso de **aprobación humana** (correo o
   Slack/WhatsApp) antes de publicar.

**Costo típico:** Make ~$9–16 USD/mes + consumo de IA (centavos por publicación).
**Tiempo:** 1–2 días de configuración.

---

## 3. Ruta B · Integración propia en el panel de arcillas.mx

**Objetivo:** que el panel admin tenga un módulo “Social” donde la IA redacte a partir del
catálogo, un humano apruebe y el sistema publique/programe y mida.

### 3.1 Crear la app de Meta

1. Entra a [developers.facebook.com](https://developers.facebook.com/) con la cuenta que
   administra el portafolio.
2. **Create App** → caso de uso **“Administrar todo en tu Page”** (Business).
3. Agrega los productos:
   - **Facebook Login for Business** (OAuth).
   - **Pages API**.
   - Opcional: **Instagram Graph API**, **Messenger API** (comentarios/DMs).
4. Guarda `App ID` y `App Secret` (el secreto **nunca** va al repositorio; va en `.env` del
   servidor y con permisos 600).

### 3.2 Permisos necesarios

| Permiso | Para qué |
|---|---|
| `pages_show_list` | Listar las Pages que administra el usuario |
| `pages_read_engagement` | Leer publicaciones y métricas |
| `pages_manage_posts` | Crear/editar/eliminar publicaciones y programarlas |
| `pages_manage_engagement` | Moderar comentarios y reacciones |
| `read_insights` | Métricas de la Page |
| `business_management` | Operar con el portafolio y usuarios del sistema |
| `pages_messaging` (opcional) | Responder DMs con Messenger API |
| `instagram_basic` + `instagram_content_publish` (opcional) | Cross-post a Instagram |

En modo desarrollo solo funciona para administradores/desarrolladores de la app; para
producción se necesita **App Review** con **acceso avanzado** a esos permisos.

### 3.3 Verificación y App Review

1. Completa **Business Verification** en el portafolio.
2. En la app: App Review → Permissions and Features → solicita acceso avanzado.
3. Adjunta un **video screencast** que muestre: inicio de sesión con Facebook, selección de
   la Page, generación del borrador, aprobación y publicación programada.
4. Ten listos: URL de aviso de privacidad (`https://arcillas.mx/aviso-de-privacidad`),
   términos, política de eliminación de datos y correo de contacto.
5. Tiempo típico: **5–10 días hábiles** por revisión; planea **2–4 semanas** con una ronda
   de correcciones.

### 3.4 Token de publicación

1. En Business Settings → Usuarios → **Usuarios del sistema**, crea uno
   (p. ej. `arcillas-social-bot`) y asígnale la Page con rol de administrador.
2. Genera un token con la app y los permisos de la tabla anterior (token de usuario del
   sistema, de larga duración).
3. Guárdalo cifrado en el servidor: `FB_PAGE_ID=61596741170224`, `FB_PAGE_TOKEN=…`,
   `FB_APP_ID`, `FB_APP_SECRET`.
4. Define rotación (cada 60–90 días) y revocación inmediata si hay incidente.

### 3.5 Publicación con Graph API (v27.0, versión vigente)

- Texto/enlace: `POST https://graph.facebook.com/v27.0/{page-id}/feed` con `message`,
  `link`, `published=false` y `scheduled_publish_time`.
- Imagen: `POST /{page-id}/photos` con `url` o `source` + `message`.
- Video: `POST /{page-id}/videos` (o flujo `video_reels` para Reels).
- Programación: entre **10 minutos y 30 días** desde la petición.
- Enlaces con UTM: `?utm_source=facebook&utm_medium=social&utm_campaign=...` para medir en
  el sitio.
- Manejo de errores: códigos 190 (token), 4/17/32/613 (rate limit), 368 (política);
  reintentos con *backoff* y alerta al admin.

### 3.6 Métricas

- `GET /{page-id}/insights?metric=page_impressions,page_post_engagements,page_follows,...`
- Guarda snapshots diarios en MySQL y muestra en el panel: alcance, interacción,
  seguidores nuevos, clics al sitio, leads.
- Cruza con `leads` y `calculator_runs` de arcillas.mx para atribuir contenido a negocio.

### 3.7 Arquitectura propuesta en el proyecto

```text
app/
├── Services/Social/
│   ├── FacebookGraphClient.php     # cURL + versión + errores + rate limit
│   ├── FacebookPageService.php     # feed/photos/videos/insights
│   ├── AiContentService.php        # LLM: prompts + catálogo + tono de marca
│   ├── SocialPublisher.php         # aprueba, programa, publica, guarda resultado
│   └── TokenManager.php            # cifrado/rotación de token
├── Controllers/Admin/SocialController.php
└── Views/admin/social/             # calendario, generador, aprobaciones, métricas
database/
└── social_posts (id, page_id, copy, media, link, scheduled_at, estado,
                 fb_post_id, error, metricas_json, creado_en)
    social_accounts (page_id, token_cifrado, expira_en, activo)
    social_metrics (page_id, fecha, alcance, interacciones, seguidores)
tools/
└── social_cron.php                 # cada 5–15 min: publica lo programado y sincroniza
```

- **Flujo:** admin elige material/industria → IA genera 3 variantes → humano edita/aprueba →
  se programa → cron publica → métricas regresan al panel.
- **Aprobación obligatoria** antes de publicar (evita errores y contenido fuera de marca).
- **Cifrado** del token con `APP_KEY`; log de auditoría de cada publicación.

### 3.8 Seguridad y cumplimiento

- Permisos mínimos, token de usuario del sistema (no contraseñas), 2FA.
- No automatizar follows/unfollows, DMs masivos, comentarios o “me gusta”.
- Respetar límites de la API y políticas de contenido; revisar la Page periódicamente.
- Aviso de privacidad y consentimiento para datos de leads (ya existen).
- Etiquetar contenido generado con IA cuando sea relevante (transparencia).

---

## 4. Qué puede hacer la IA (y qué no)

**Sí:**
- Generar copies, variantes, hashtags y calendarios con la voz de marca.
- Convertir fichas técnicas en publicaciones divulgativas.
- Proponer imágenes a partir de fotos reales y gráficos del catálogo.
- Redactar respuestas a comentarios/DMs (con aprobación o respuestas controladas).
- Analizar métricas y proponer ajustes semanales.
- Traducir/publicar en inglés para compradores internacionales.

**No (políticas de Meta):**
- Publicar en perfiles personales vía API (solo Pages).
- Comprar seguidores, usar bots o granjas de cuentas.
- Automatizar follows/unfollows o engagement masivo.
- Hacer scraping de datos de otras Pages o usuarios.
- Publicar contenido engañoso o promesas técnicas no respaldadas.

---

## 5. Estrategia de contenido y crecimiento (30 días)

### Pilares para un broker B2B de minerales

1. **Mineral de la semana:** ficha resumida (Al₂O₃, Fe₂O₃, humedad, aplicación) + enlace a
   `arcillas.mx/materiales/...`.
2. **Aplicación industrial:** cerámica, fundición, construcción, pinturas, perforación.
3. **Dato técnico/calculadora:** UMF, contracción, balance metalúrgico, precio normalizado
   con enlace a `/calculadoras/...`.
4. **Proveedor/procedencia:** mina, estado, capacidad, certificaciones (sin datos demo).
5. **Confianza:** casos, muestras, logística, preguntas frecuentes de compradores.
6. **Reels cortos:** 15–30 s mostrando mineral, proceso o tip de compra.
7. **Detrás de cámara:** visitas a mina/planta, laboratorio, embarques.

### Calendario base

| Día | Formato | Tema |
|---|---|---|
| Lunes | Imagen/carrusel | Mineral de la semana |
| Miércoles | Imagen/video | Aplicación por industria |
| Viernes | Reel + enlace | Calculadora o dato técnico |
| Sábado (opcional) | Story | Encuesta/pregunta |

- Frecuencia: 3 publicaciones/semana + 3 historias/semana al inicio; subir a 5 cuando haya
  capacidad de respuesta.
- Horarios a probar (hora CDMX): 8:00–10:00, 13:00–14:00, 18:00–19:00.
- Responde comentarios/DMs en menos de 1 hora hábil; la respuesta impulsa el alcance.
- Reutiliza: Facebook → Instagram → LinkedIn → WhatsApp Channel, adaptando formato.

### Crecimiento orgánico + pagado

- **Enlace social en el sitio:** agrega el icono de Facebook en footer y página de contacto
  (se puede implementar en arcillas.mx).
- **Contenido de valor + lead magnet:** las calculadoras y fichas técnicas son el gancho;
  pide seguir para recibir “mineral de la semana”.
- **Invitaciones:** invita por correo/WhatsApp a clientes y proveedores a seguir la Page
  (sin spam).
- **Colaboraciones:** asociaciones mineras/cerámicas, universidades, proveedores; publica
  contenido cruzado.
- **Anuncios:** empieza con $150–300 USD/mes en campañas de **interacción** y **clientes
  potenciales** (no de “me gusta”); público: Jalisco + corredores industriales, intereses en
  cerámica/minería/fundición, lookalike de la lista de leads (con consentimiento), y
  **retargeting** con Meta Pixel en arcillas.mx.
- **Medición:** UTMs + Pixel + leads del sitio; revisa costo por lead y calidad, no solo
  seguidores.

### KPIs semanales

- Alcance y cuentas alcanzadas.
- Interacciones y tasa de interacción.
- Seguidores nuevos netos y visitas al perfil.
- Clics al sitio y leads atribuidos.
- Costo por lead (si hay ads).

---

## 6. Cronograma y costos estimados

| Fase | Duración | Entregable | Costo aprox. |
|---|---|---|---|
| 0. Accesos y verificación | 1–3 días | Portafolio, Page, dominio verificado | $0 |
| 1. Ruta A (IA + scheduler) | 1–3 días | Calendario, prompts, publicaciones | $0–25 USD/mes |
| 2. App + permisos + App Review | 2–4 semanas | App aprobada, token de sistema | $0 (Meta no cobra) |
| 3. Módulo Social en el panel | 1–2 semanas | Borradores IA, aprobación, publicación, métricas | Desarrollo |
| 4. Crecimiento (ads) | continuo | Campañas y optimización | $150–300 USD/mes de prueba |

---

## 7. Qué necesito de ti para implementarlo

**Obligatorio (Ruta A):**
1. Confirmar que eres **administrador** de la Page `61596741170224`.
2. Crear el **portafolio empresarial** y agregar la Page (o darme acceso como socio/empleado
   con rol suficiente).
3. Decidir el alcance: ¿solo Facebook o también Instagram/LinkedIn/WhatsApp Channel?

**Para la Ruta B (integración en arcillas.mx):**
4. Crear la **app de Meta** (o agregarme al portafolio como desarrollador) y entregar de
   forma segura `App ID` y `App Secret`.
5. Completar **verificación de negocio** y **dominio**; autorizar la revisión con el video.
6. Crear el **usuario del sistema** con la Page y generar el token.
7. Definir la **API de IA** a usar (DeepSeek/OpenAI) y su presupuesto.
8. Aprobar el flujo de **borrador → aprobación humana → publicación**.

**Nunca compartas contraseñas por chat.** Para credenciales usa variables de entorno en el
servidor o un gestor de secretos; el repositorio queda sin tokens.

---

## 8. Siguiente paso propuesto

1. **Hoy:** activar Ruta A con 8 publicaciones iniciales (4 minerales, 2 aplicaciones, 2
   calculadoras) y conectar el enlace de Facebook en arcillas.mx.
2. **Esta semana:** definir prompts de marca y calendario de 30 días.
3. **En paralelo:** iniciar la app de Meta y la verificación de negocio para la Ruta B.
4. **Cuando esté aprobada:** implementar el módulo Social en el panel admin (borradores IA,
   aprobación, programación, publicación y métricas) y medir leads atribuidos.

---

### Referencias

- [Pages API · publicar y programar](https://developers.facebook.com/docs/pages-api/posts)
- [Permisos de Meta](https://developers.facebook.com/docs/permissions)
- [App Review](https://developers.facebook.com/docs/app-review)
- [Business Verification](https://www.facebook.com/business/help/2058515294227817)
- [Meta Business Suite](https://business.facebook.com/)
- [Políticas de la plataforma](https://developers.facebook.com/policy/)
