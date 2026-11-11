# Arquitectura del directorio técnico de UL Prospector (`/es/la`) y su traducción a arcillas.mx

> **Proyecto:** Arcillas MX · ARCILLAS PROTEÍNAS Y DESARROLLOS GARCÍA
> **Autor:** investigador técnico (equipo Arcillas MX)
> **Fecha de verificación:** 2026-11-11, 13:30–13:55 (America/Mexico_City)
> **Alcance:** análisis de la información pública de <https://www.ulprospector.com/es/la> y propuesta de arquitectura de rutas para el sitio B2B de arcillas y minerales no ferrosos. No toca código.

---

## 0. Resumen ejecutivo

UL Prospector es un **directorio técnico de materias primas** (no una tienda): el usuario busca por palabra clave, industria, tipo de material y aplicación; revisa fichas con documentos técnicos (TDS), pide muestras y contacta al proveedor. Su modelo comercial es *gratis para formuladores registrados* y *de pago para proveedores* (marketing y servicios de datos).

Para arcillas.mx **sí conviene replicar** el esqueleto: industrias → categorías → fichas de material con propiedades EAV → proveedores → comparación → solicitud de muestra/cotización. **No conviene replicar**: base de formulaciones de terceros, muro de registro para ver documentos, Tarjeta Amarilla UL, ni el discurso de "800 mil documentos".

Lo que UL Prospector **no tiene** y es la ventaja diferencial del broker: **calculadoras técnicas** (UMF/Seger, mezcla de arcillas, contracción/absorción, balance metalúrgico, bentonita en arena verde, granulometría/Stokes, normalización de precio/humedad, fórmula estructural) integradas al embudo de cotización.

---

## 1. Método y limitaciones de acceso (importante)

El sitio bloquea el acceso automatizado desde este entorno:

| Recurso | URL probada | Código observado |
|---|---|---|
| Home /es/la | <https://www.ulprospector.com/es/la> | **403** (Cloudflare; cuerpo "Warning: Blocked - UL Prospector") |
| Industria Coatings | <https://www.ulprospector.com/es/la/Coatings> | 403 |
| Help | <https://www.ulprospector.com/es/help> | 403 |
| About | <https://www.ulprospector.com/es/About> | 403 |
| Términos | <https://www.ulprospector.com/es/Terms> | 403 |
| Sitemap | <https://www.ulprospector.com/sitemap.xml> | 403 |
| **robots.txt** | <https://www.ulprospector.com/robots.txt> | **200** (único recurso accesible en vivo) |

Por ello el análisis de contenido se hizo sobre **copias archivadas** de la Wayback Machine, servidas en modo `id_` (HTML original, sin barra de la Wayback). Todas las URLs de archivo usadas se verificaron con `curl -s -o /dev/null -w '%{http_code}'` y devolvieron **200** (ver §10). Se distingue en todo el documento entre:

- **observado**: se leyó en el HTML archivado;
- **declarado**: lo afirma el propio sitio en su copy;
- **no verificado**: no se pudo comprobar (se dice explícitamente).

`robots.txt` (leído en vivo, 200) confirma además la estructura de rutas: desautoriza `/documents/`, `/{region}/`, `/{cybrary}/`, `/contactsupplier/`, `/users/`, `/bookshelves/`, `/productdetails/`, `/restricted/`, y declara `Sitemap: https://www.ulprospector.com/sitemap.xml`. También incluye `GPTBot`, `ClaudeBot` y `ia_archiver` en `Disallow: /`, por lo que la política del sitio prohíbe reutilizar su contenido; **este documento no reproduce su copy, solo describe la arquitectura**.

---

## 2. Inventario de páginas y patrones de URL (observado)

Patrón general: `/{idioma}/{región}/{industria}/{sección}[/{id}/{slug}]`.

| Tipo de página | Patrón de URL | Ejemplo verificado (archivo) | Contenido observado |
|---|---|---|---|
| Home regional | `/es/la` | [home 2024-01-14](https://web.archive.org/web/20240114104004/https://www.ulprospector.com/es/la) | Buscador, 10 industrias, registro gratis |
| Home de industria | `/es/la/{Industria}` | [Coatings 2022-12-10](https://web.archive.org/web/20221210060338/https://www.ulprospector.com/es/la/Coatings) | H1, "Trending Products/Keywords/Formulations", navegación de la industria |
| Buscar materias primas | `/es/la/{Ind}/Product` | [Product 2021-10-22](https://web.archive.org/web/20211022090125/https://www.ulprospector.com/es/la/Coatings/Product) | Listado + panel de refinamiento + orden + paginación |
| Categoría (tipo de material) | `/es/la/{Ind}/Products/{id}/{slug}` | `/es/la/Coatings/Products/1/Aditivos` | Listado filtrado, conteo (p. ej. "5299 Resultados") |
| Aplicación | `/es/la/{Ind}/Applications/{id}/{slug}` | `/es/la/Coatings/Applications/19534/Revestimientos-para-madera-y-muebles` | Listado filtrado por aplicación |
| Ficha de material | `/es/la/{Ind}/Detail/{proveedorId}/{productoId}/{slug}` | [Bisomer TEGDMA 2024-05-16](https://web.archive.org/web/20240516011954/https://www.ulprospector.com/es/la/Coatings/Detail/1868/206405/Bisomer-TEGDMA) | Ficha con documentos, empresa, categorías, dónde comprar |
| Ficha de formulación | misma ruta `/Detail/...` | p. ej. `/Coatings/Detail/2062/46326/RAL-6018-Formulation...` | Igual que material, con ingredientes (no archivada) |
| Página de proveedor | `/es/la/{Ind}/Suppliers/{id}/{slug}` | [Datacolor 2021-01-18](https://web.archive.org/web/20210118144757/https://www.ulprospector.com/es/la/Coatings/Suppliers/31170/Datacolor) | Descripción, web, catálogo filtrado del proveedor |
| Catálogo del proveedor | `/es/la/{Ind}/Suppliers/{id}/{slug}/Product` | `/.../Suppliers/31170/Datacolor/Products/1/Aditivos` | Listado del proveedor por categoría |
| Listado de formulaciones | `/es/la/{Ind}/Formulation` | [Formulation 2021-09-21](https://web.archive.org/web/20210921081537/https://www.ulprospector.com/es/la/Coatings/Formulation) | "583 Resultados" |
| Equipos y servicios | `/es/la/{Ind}/Equipment` | `/es/la/Coatings/Equipment` | Tercera pestaña del browse |
| Artículos / información | `/es/la/{Ind}/Articles` | `/es/la/Coatings/Articles` | Contenido editorial |
| Búsqueda por palabra clave | `/es/la/{Ind}/search?k={término}&st=1` | [búsqueda "melamina"](https://web.archive.org/web/20211127210731/https://www.ulprospector.com/es/la/Adhesives/search?k=melamina&st=1) | "21 Resultados", orden, fichas resumidas |
| Carrito de muestras | `/samplerequest/new` | enlace presente en todas las páginas | "Muestras (0)" |
| Login / registro | `/session/new`, `/users/new`, `/es/login` | home | Acceso gratuito |
| Alta externa de cuenta | `accounts.ulprospector.com/ul-auth?action=registration&lang=es&industry=coatings` | ficha de producto | Registro segmentado por industria |
| Descarga de TDS | `/documents/{docId}.pdf?bs={provId}&b={prodId}&st=20&r=la&ind=coatings` | ficha de producto | PDF con idioma declarado |
| Contacto | `/es/contact/new`, `/contactsupplier/` (robots) | footer | Formularios |
| Páginas corporativas | `/es/About`, `/es/Partners`, `/es/help`, `/es/Terms`, `/es/Privacy`, `/es/Data`, `/es/datasheet-services` | [About 2019-07-20](https://web.archive.org/web/20190720155343/https://www.ulprospector.com/es/About) | Anclas internas: información de cuenta, historia, locales, prensa |
| "Top 250 proveedores" | `/es/eu/{Ind}/Companies` | enlace desde home de industria | Listado de proveedores |
| Variantes de idioma/región | `/{es,en,pt,de,fr,zh-cn,zh-tw,ja}/{la,na,eu,asia}/...` | home | 8 idiomas × 4 regiones |
| Otros verticales | `plastics.ulprospector.com/es`, `additives.ulprospector.com/es`, `metals.ulprospector.com/es` | home | Subdominios de industria |

> **Nota sobre `/es/help`:** el enlace existe en el pie de todas las páginas, pero la Wayback Machine **no tiene copia** de esa URL (consulta de archivo devolvió 404) y el sitio en vivo responde 403; por eso **no se describe su contenido**. Lo mismo aplica a `/es/Terms` y `/es/Privacy`: se citan como enlaces existentes, no como contenido revisado.

**Observación clave para el diseño de rutas:** el `id` del proveedor y el `id` del producto viajan en la URL (`/Detail/1868/206405/Bisomer-TEGDMA`), lo que permite fichas estables aunque cambie el slug. Es una buena práctica que arcillas.mx debe adoptar (`/materiales/{id}-{slug}` o `/materiales/{slug}` con redirección desde `id`).

---

## 3. Navegación global (observada)

Anatomía del encabezado y del pie, común a todas las páginas archivadas:

1. **Barra de búsqueda** con etiqueta "BÚSQUEDA" (input `k` + selector de alcance por industria).
2. **Carrito de muestras**: "Muestras (0)" enlazado a `/samplerequest/new`; cada tarjeta del listado añade "Esto ha sido añadido a su carrito de muestra!" con CTA "Envíe su pedido ahora".
3. **Selector de idioma y región** (8 idiomas / 4 regiones), persistente.
4. **Breadcrumb**: `Home > {Industria} > {Categoría|Producto}` (en la ficha de producto aparece también la cadena de categorías como *tags*).
5. **Navegación interna de la industria**: `Materias Primas` (`/Product`), `Formulaciones` (`/Formulation`), `Equipment / Services` (`/Equipment`), `Industry Information` (`/Articles`).
6. **Panel de refinamiento** (izquierda): "Busque y refine los ítems de la industria", con bloques `Compañía | Vea Todo`, `Materias Primas | Vea Todo`, `Aplicaciones | Vea Todo`; en la práctica son refinamientos por tipo de contenido (`bookType`), tipo de producto (`ProdType`) y proveedor, más checkboxes con `data-checkbox-refinement`.
7. **Orden y paginación**: `select` de orden (`Relevancia`, `Compañías A-Z`, `Compañías Z-A`, `Nombre A-Z`, `Nombre Z-A`, `Tipo de Contenido`), conteo de resultados y paginación ("siguiente").
8. **Pie**: About, Contacto, Programas de Anuncios, Servicios de Hoja de Datos, Knowledge Center, términos, privacidad, portales de derechos de datos (TrustArc/OneTrust), industrias hermanas.

**Facetas verificadas:** compañía, tipo de materia prima (`Products/{id}`), aplicación (`Applications/{id}`). **No verificado en `/es/la`:** facetas por *propiedad técnica* y por *certificación*. El home las **declara** ("Busque … por palabra clave, propiedad, certificación y más") y Prospector tiene módulos de propiedades/cumplimiento en otras regiones y niveles, pero en las copias archivadas de Latinoamérica solo se observaron las tres facetas anteriores. Para arcillas.mx la recomendación es implementarlas igualmente (el esquema `material_properties` EAV ya lo permite) y **no** afirmar cifras de cobertura que no se tengan.

### 3.1 Tipos de búsqueda observados/declarados

| Tipo | Evidencia | Cómo funciona en arcillas.mx |
|---|---|---|
| Palabra clave | `/search?k=melamina&st=1`; "21 Resultados" | `GET /buscar?q=` sobre nombre, resumen, aplicaciones y sinónimos |
| Por tipo de material | `/Products/{id}/{slug}` ("Aditivos", "Ligantes / Resinas") | `/materiales?categoria=caolin` |
| Por aplicación | `/Applications/{id}/{slug}` (p. ej. "Revestimientos para madera y muebles") | `/materiales?industria=fundicion&aplicacion=arenas-de-moldeo` |
| Por proveedor | `/Suppliers/{id}/{slug}` y facetas de compañía | `/proveedores/{slug}` + filtro `proveedor=` |
| Por propiedad técnica | **Declarada** en home; no observada en `/es/la` | `?prop=al2o3&min=35&max=40` sobre `material_properties` |
| Por certificación | **Declarada** en home; no observada en `/es/la` | `?cert=iso9001` sobre campo de proveedor/material (a definir) |
| Búsqueda guardada + alerta | Formulario con `SaveSearchName`, `isAlert` | Fase 2: `search_logs` + alertas por correo |
| Búsqueda comparativa | Home declara "comparaciones lado a lado" y gráficas de radar | `/comparar?ids=1,2,3` |

---

## 4. Anatomía de la ficha de producto (observada)

Caso analizado: **Bisomer® TEGDMA** de GEO Specialty Chemicals (snapshot 2024-05-16, el más reciente de la muestra). Estructura, en orden:

| Bloque | Contenido observado | Equivalente en arcillas.mx |
|---|---|---|
| 1. Título | `H1` = nombre comercial (`Bisomer® TEGDMA`) | `materials.nombre` |
| 2. Proveedor | `H4` enlace a la página del proveedor | `material_suppliers` → `suppliers` |
| 3. Acciones de cabecera | compartición ("Compartir"), marcador ("MARCADOR"), añadir a lista ("Prospector Lists") | favoritos (cookie/sesión), "compartir", "añadir a mi solicitud" |
| 4. **Documentos** | `H3 Documentos`: "Data Sheet (Inglés)", catálogo del producto; enlaces directos `/documents/{id}.pdf?bs=&b=&st=&r=la&ind=coatings` con **idioma** indicado | `material_docs` (`ficha_tecnica`, `seguridad`, `analisis`) + campo idioma/visibilidad |
| 5. Descripción técnica | 2–4 párrafos con propiedades del producto (viscosidad, peso molecular, contenido de inhibidor…) | `materials.descripcion` + `material_properties` |
| 6. Empresa | `H3 Empresa`: descripción del proveedor | `suppliers.descripcion` |
| 7. Documentos de la empresa | catálogo, selección de producto, etc. | `material_docs` (tipo catálogo) |
| 8. Aviso legal | "La información presentada aquí fue adquirida por UL desde el productor del material…" | aviso de veracidad + fecha de actualización |
| 9. Disponibilidad regional | lista de regiones donde el proveedor publica documentación | `material_suppliers.region/cobertura` |
| 10. CTA de registro | "Crear su cuenta gratis" (enlace a `accounts.ulprospector.com`) | CTA de cotización/muestra (sin muro) |
| 11. Enlace al proveedor | "Visite la pagina del suministrador" | `suppliers.sitio_web` |
| 12. **Categorías** | cadena con separador `~`: Productos Intermedios ~ Monómeros ~ Acrílicos; Ligantes/Resinas ~ Diluyentes ~ Reactivos | `categories` + relación múltiple (una ficha puede estar en 2–3 ramas) |
| 13. **Dónde Comprar** | proveedor + "Visite el sitio web" + "¿Es usted un distribuidor…? contáctenos" | `material_suppliers` (mina/planta/distribuidor/broker) + alta de proveedor |
| 14. Muestras / contacto | en ficha de equipos aparece "Contactar al Suministrador"; en materiales, el flujo es el carrito de muestras | botones "Solicitar muestra" y "Solicitar cotización" → `leads` |

**Lo que no aparece en esta copia:** tabla de propiedades numéricas exhaustiva, pestaña de cumplimiento y comparador embebido. En fichas de producto de otras industrias/regiones Prospector sí muestra "technical data" estructurada; en la copia de `/es/la` el dato técnico llega dentro de la descripción y en el PDF (TDS). Para arcillas.mx se recomienda **sí** estructurar las propiedades (EAV) porque son la base de las facetas, el comparador y las calculadoras.

### 4.1 Flujo de conversión observado

```
listado → ficha → (documentos / descripción) → añadir muestra al carrito → /samplerequest/new
                                     ↘ "Contactar al Suministrador" → /contactsupplier/
                                     ↘ "Crear su cuenta gratis" → accounts.ulprospector.com
```

El registro es **gratuito para profesionales calificados** (declarado en el home) y desbloquea documentos y muestras. En arcillas.mx el equivalente es un formulario corto sin muro: pedir muestra/cotización genera un `lead` y el contenido sigue indexable (mejor SEO y mejor tasa de conversión para un broker).

---

## 5. Página de proveedor (observada)

Caso Datacolor (snapshot 2021-01-18):

- Cabecera con nombre del proveedor y **descripción corporativa** larga.
- Enlace "Visite el sitio web de la empresa".
- Conteo de resultados ("20 Resultados"), orden y refinamiento por contenido/categoría/proveedor (checkboxes).
- Catálogo del proveedor como listado filtrable: `/Suppliers/{id}/{slug}/Product`, `/Products/{catId}`.

Traducción: `/proveedores/{slug}` con descripción, tipo (mina/planta/distribuidor/broker), estado, certificaciones, capacidad, catálogo de materiales, CTA "Solicitar cotización" y "Ver ficha de cada material". El esquema ya soporta casi todo (`suppliers`, `material_suppliers`).

---

## 6. Formulaciones (observadas)

- Existe un **listado de formulaciones** (`/es/la/{Ind}/Formulation`, "583 Resultados") y fichas con el mismo patrón `/Detail/{id}/{slug}`.
- Los nombres incluyen el número de formulación ("(Formulation #PCO-0059SF)") y el proveedor que la publica; las fichas de formulación listan ingredientes (no archivada en detalle).

**Decisión para arcillas.mx: no replicar el banco de formulaciones de terceros.** Es contenido con derechos de autor de cada proveedor, requiere curaduría y no aporta al negocio del broker. En su lugar:

- "Aplicaciones y dosificaciones recomendadas" por material (contenido propio, corto, basado en literatura pública y en la experiencia del broker).
- **Calculadoras** que hacen el trabajo pesado (mezcla de arcillas, bentonita en arena verde) en lugar de recetas cerradas.

---

## 7. Cumplimiento normativo (declarado, no observado en detalle)

El home declara:

- "Analizar información de cumplimiento a nivel de ingrediente … para más de **200 listas de asesoría y normativas globales** y **30 listas de proveedores**".
- "información de rendimiento de **Tarjeta Amarilla de UL**", "materiales aprobados automotrices/OEM", "encuentre resinas alternativas".
- "Vea curvas de datos de múltiples puntos… compare propiedades en un trazo de radar".

No fue posible verificar esas pantallas (403 + sin capturas archivadas específicas). Para arcillas.mx se traduce en:

- Campos de **certificaciones del proveedor** (ISO 9001, ISO 14001, REACH/RoHS si aplica) visibles en la ficha y usables como faceta.
- **Aviso de origen y trazabilidad** (mina/planta, región, lote) — más relevante para minerales que las listas regulatorias químicas.
- **No** prometer "cumplimiento" ni "aprobado por OEM" sin evidencia documental del proveedor.

---

## 8. Registro de cuenta (observado)

- Enlaces "Sign In" → `/session/new`; "Registrarse" → `/users/new`.
- Alta segmentada por industria: `accounts.ulprospector.com/ul-auth?action=registration&lang=es&industry=coatings`.
- El registro es gratuito para "profesionales calificados" (declarado); el registro desbloquea documentos y solicitud de muestras.
- Existe página "Información de la cuenta" dentro de `/es/About` y portal de derechos de datos (TrustArc/OneTrust).

Para arcillas.mx: **no** crear cuentas de usuario en la fase 1. El valor está en el lead: `leads` (`tipo = cotizacion | muestra | contacto | proveedor | calculadora`) + `lead_items` (carrito de muestras múltiple). El registro solo si más adelante se quiere "mis cotizaciones" o "mis búsquedas guardadas".

---

## 9. Qué se replica y qué no en un broker de arcillas/minerales

| Capacidad de UL Prospector | ¿Replicar? | Traducción a arcillas.mx | Notas |
|---|---|---|---|
| Industrias → categorías → materiales | **Sí** | `/industrias`, `/materiales` con facetas | Es el núcleo del SEO y del negocio |
| Ficha de material con TDS | **Sí** | Propiedades EAV + documentos PDF | Sin muro de registro |
| Página de proveedor | **Sí** | `/proveedores/{slug}` | Marcar `verificado`/`es_demo` |
| Comparación lado a lado | **Sí (2–4)** | `/comparar?ids=` | Matriz de propiedades con resaltado de diferencias |
| Carrito de muestras múltiple | **Sí** | `lead_items` + `leads` | 1 lead con N materiales |
| Búsqueda por propiedad/certificación | **Sí (fase 2)** | Filtros `?prop=&min=&max=` | Requiere poblar `material_properties` |
| Búsquedas guardadas + alertas | No (fase 3) | `search_logs` sin alertas | Bajo retorno vs. costo |
| Banco de formulaciones de terceros | **No** | Sustituir por calculadoras y guías propias | Derechos de autor + curaduría |
| Cumplimiento regulatorio global | **No** (versión simple) | Certificaciones del proveedor + aviso | Evitar promesas no verificables |
| Tarjeta Amarilla / datos OEM | **No** | — | Propiedad de UL |
| Registro obligatorio para ver docs | **No** | Descarga libre + formulario opcional | Mejor indexación y conversión |
| Programas de anuncios para proveedores | Fase 3 | "Publica tu empresa" → lead `proveedor` | El esquema ya tiene tipo `proveedor` |
| Multi-idioma/multi-región | **No** (solo es-MX al inicio) | `lang=es-MX` | Se puede añadir `en` después |

---

## 10. Mapa de rutas propuesto para arcillas.mx

Convenciones: slugs en español, minúsculas, guiones, sin acentos; IDs numéricos solo cuando aportan estabilidad; parámetros de faceta en query string; canónicas y `hreflang` cuando exista inglés.

```
/                                   Home (buscador + industrias + destacados + calculadoras)
/industrias                         Índice de industrias
/industrias/{slug}                  Industria (p. ej. /industrias/ceramica) → materiales y aplicaciones
/industrias/{slug}/aplicaciones     Aplicaciones de esa industria
/categorias                         Índice de categorías de minerales
/categorias/{slug}                  Categoría (p. ej. /categorias/caolin)
/materiales                         Catálogo con facetas
        ?q= &industria= &categoria= &proveedor= &prop=al2o3 &min=35 &max=40 &pagina=2 &orden=relevancia|nombre|precio
/materiales/{id}-{slug}             Ficha de material (canónica; redirige 301 desde slug solo)
/materiales/{slug}                  Alias 301 → /materiales/{id}-{slug}
/materiales/{slug}/ficha-tecnica    Descarga/visor de TDS (puede ser /docs/{id})
/proveedores                        Índice de proveedores (tipo, estado, certificaciones)
/proveedores/{slug}                 Página de proveedor + catálogo
/proveedores/{slug}/materiales      Catálogo filtrado del proveedor
/comparar?ids=12,34,56              Comparación 2–4 materiales (máx. 4; resto ignora con aviso)
/buscar?q=                          Búsqueda global (materiales + proveedores + calculadoras)
/calculadoras                       Índice de las 8 calculadoras
/calculadoras/{slug}                Calculadora (UMF, mezcla, contracción, balance, bentonita, granulometría, precio, fórmula estructural)
/calculadoras/{slug}/resultado      Resultado + CTA (opcional; puede ser POST a la misma ruta)
/cotizar                            Formulario de cotización (material preseleccionado opcional)
/muestras                           Carrito de muestras → 1 lead con N items
/contacto                           Contacto general
/quienes-somos                      Empresa (equivalente a /es/About)
/aviso-de-privacidad                Privacidad y datos personales (LFPDPPP)
/terminos                           Términos
/sitemap.xml, /robots.txt           SEO
```

### 10.1 Mapa de rutas → tablas del esquema (`database/schema.sql`)

| Ruta | Tablas principales | Notas de implementación |
|---|---|---|
| `/` | `industries`, `materials` (destacado=1), `settings` | Cachear 5–10 min |
| `/industrias/{slug}` | `industries` + `material_industries` + `materials` | 404 si `activo=0` |
| `/categorias/{slug}` | `categories` + `materials` | `familia` como filtro secundario |
| `/materiales` | `materials`, `material_properties`, `material_industries`, `material_suppliers`, `categories` | Facetas con índice `idx_props_clave_valor` |
| `/materiales/{id}-{slug}` | `materials` + `material_properties` + `material_docs` + `material_suppliers` + `suppliers` | JSON-LD `Product`/`ChemicalSubstance` si aplica |
| `/proveedores/{slug}` | `suppliers` + `material_suppliers` + `materials` | Badge `verificado`; `es_demo` oculto en producción |
| `/comparar` | `materials` + `material_properties` | Solo propiedades comunes; resaltar diferencias |
| `/calculadoras/{slug}` | `calculator_runs` (bitácora) | Nunca guardar datos personales en `input_json` |
| `/cotizar`, `/muestras`, `/contacto` | `leads` + `lead_items` | `consentimiento=1` obligatorio; honeypot + rate limit |
| `/buscar` | `materials`, `suppliers`, `search_logs` | Registrar término y número de resultados (detecta demanda no cubierta) |

### 10.2 Decisiones de SEO y UX

1. **URLs planas y legibles** en español; no anidar industria en la ficha del material (una misma bentonita sirve a fundición y perforación → canónica única `/materiales/{id}-{slug}` y relaciones N:M con industrias).
2. **Breadcrumb** `Inicio > Materiales > Categoría > Material` con `BreadcrumbList` en JSON-LD.
3. **Contenido indexable sin registro**, con CTA de muestra/cotización visible en el primer viewport.
4. **Facetas que no generen URLs basura**: parámetros de filtro con `noindex, follow` cuando haya combinación > 2 facetas; canónica al listado base.
5. **Comparador**: `?ids=` acotado a 4, con enlace "compartir comparación" (URL estable) y sin indexar.
6. **Calculadoras**: cada calculadora es una landing (`/calculadoras/umf-esmalte`) con explicación, ejemplo resuelto, formulario y CTA; al calcular, registrar en `calculator_runs` y ofrecer "¿Quieres que te cotizemos este material?" (lead `calculadora`).
7. **Analítica**: `search_logs` y `calculator_runs` permiten priorizar qué minerales y qué calculadoras empujar.

### 10.3 Reutilización de los patrones de UL Prospector en el modelo de datos

| Patrón de ULP | Tabla/campo en `schema.sql` |
|---|---|
| Jerarquía industria → categoría → material | `industries`, `categories`, `materials.category_id` + `material_industries` |
| Propiedades facetables | `material_properties` (`grupo`, `clave`, `valor`, `unidad`) con `idx_props_clave_valor` |
| TDS y documentos | `material_docs` (`tipo`, `mime`, `publico`) |
| "Dónde comprar" | `material_suppliers` (`es_principal`, `precio_ref`, `minimo`) |
| Carrito de muestras | `leads` (tipo `muestra`) + `lead_items` |
| Múltiples proveedores por material | `material_suppliers` (N:M) |
| Múltiples industrias por material | `material_industries` (N:M, con `aplicacion`) |
| Búsqueda guardada | `search_logs` (fase 1 sin alertas) |
| Comparación | no requiere tabla: se resuelve con `material_properties` |

---

## 11. Riesgos y recomendaciones

1. **No copiar contenido de UL Prospector**: `robots.txt` prohíbe explícitamente a bots de IA (`GPTBot`, `ClaudeBot`) y a `ia_archiver`; usar solo la estructura como referencia y redactar fichas propias o con autorización de los proveedores.
2. **Veracidad técnica**: cada propiedad publicada debe tener fuente (TDS del proveedor, análisis de laboratorio, literatura). Añadir campo "fuente" y "fecha de actualización" por propiedad/doc.
3. **Datos demo**: `es_demo=1` en proveedores y materiales de ejemplo; nunca indexar demo en producción (filtrar en consultas y en `sitemap.xml`).
4. **Precios**: `precio_ref` es referencia, no oferta; mostrar "Precio sujeto a cotización" y fecha.
5. **Muestras**: al ser un broker, definir política de muestras (cantidad, costo de envío, cobertura) antes de publicar el calculador de leads.
6. **Legal México**: aviso de privacidad (LFPDPPP), consentimiento explícito en `leads.consentimiento`, y retención de datos.
7. **Accesibilidad y rendimiento**: el catálogo debe funcionar sin JS para SEO; las facetas pueden ser formularios GET.

---

## 12. Fuentes y verificación

### 12.1 Recursos vivos

| Recurso | URL | Código | Nota |
|---|---|---|---|
| robots.txt | <https://www.ulprospector.com/robots.txt> | 200 | Leído completo; confirma rutas y sitemap |
| Home /es/la | <https://www.ulprospector.com/es/la> | 403 | Bloqueo Cloudflare |
| Sitemap | <https://www.ulprospector.com/sitemap.xml> | 403 | No accesible |

### 12.2 Copias archivadas utilizadas (todas 200)

- Home 2024-01-14 — <https://web.archive.org/web/20240114104004/https://www.ulprospector.com/es/la>
- Industria Coatings 2022-12-10 — <https://web.archive.org/web/20221210060338/https://www.ulprospector.com/es/la/Coatings>
- Buscar materias primas 2021-10-22 — <https://web.archive.org/web/20211022090125/https://www.ulprospector.com/es/la/Coatings/Product>
- Categoría Aditivos 2021-10-24 — <https://web.archive.org/web/20211024144230/https://www.ulprospector.com/es/la/Coatings/Products/1/Aditivos>
- Ficha Bisomer TEGDMA 2024-05-16 — <https://web.archive.org/web/20240516011954/https://www.ulprospector.com/es/la/Coatings/Detail/1868/206405/Bisomer-TEGDMA>
- Búsqueda "melamina" 2021-11-27 — <https://web.archive.org/web/20211127210731/https://www.ulprospector.com/es/la/Adhesives/search?k=melamina&st=1>
- Proveedor Datacolor 2021-01-18 — <https://web.archive.org/web/20210118144757/https://www.ulprospector.com/es/la/Coatings/Suppliers/31170/Datacolor>
- Formulaciones (listado) 2021-09-21 — <https://web.archive.org/web/20210921081537/https://www.ulprospector.com/es/la/Coatings/Formulation>
- About 2019-07-20 — <https://web.archive.org/web/20190720155343/https://www.ulprospector.com/es/About>
- Aplicación panadería (Food) 2020-11-23 — <https://web.archive.org/web/20201123221036/https://www.ulprospector.com/es/la/Food/Applications/18259/Productos-de-panadera>

### 12.3 Otros verticales (solo referencia)

- Plásticos: <https://plastics.ulprospector.com/es> — respondió **403** en la verificación; no analizado.
- Aditivos plásticos: <https://additives.ulprospector.com/es> — respondió **403**; no analizado.
- Metales: <https://metals.ulprospector.com/es> — respondió **403**; no analizado.

> **Nota de honestidad:** las cifras de cobertura ("800,000 documentos", "250 principales suministradores") son **declaraciones del sitio** citadas en su propio copy; no se verificaron de forma independiente y no deben reutilizarse como datos propios.
