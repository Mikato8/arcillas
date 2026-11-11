# Fuentes de datos del catálogo Arcillas MX

**Última actualización:** 2026-11-11 · **Versión del catálogo:** 1.0.0
**Archivo canónico:** [`resources/data/catalogo.json`](../resources/data/catalogo.json)
**Uso:** este directorio y sus fichas son **DEMOSTRATIVOS**. Antes de publicar, el broker debe sustituir proveedores, certificaciones, regiones y precios por información comercial real y verificable.

---

## 1. Advertencia legal y de alcance

- **Proveedores ficticios:** los 10 proveedores llevan el sufijo `Demo` en el nombre, `es_demo = 1` y `verificado = 0`. No representan empresas reales, ni relaciones comerciales, ni capacidad instalada verificada. Las certificaciones (`ISO 9001 (demo)`, `API 13A (demo)`, etc.) son **textos genéricos de demostración**, no certificados vigentes de ningún organismo.
- **Materiales y proveedores:** la taxonomía de industrias y categorías sí es real y reutilizable; las fichas técnicas son recopilaciones de **rangos típicos de literatura pública** y hojas de datos comerciales genéricas. Ningún valor debe usarse como especificación contractual sin un certificado de análisis (COA) del proveedor real.
- **Precios:** son **referencias de mercado** por tonelada, no ofertas de venta ni cotizaciones vinculantes. Ver sección 6.
- **Grados alimentarios, cosméticos y farmacéuticos:** el catálogo no declara cumplimiento de COFEPRIS, FDA, FCC ni farmacopeas. Cualquier uso regulado requiere proveedor calificado, análisis de metales pesados y microbiología, y registro sanitario propio.
- **Nada de lo aquí escrito afirma** que una empresa real suministre, produzca o certifique un material. Los nombres de proveedores son inventados para el prototipo.

---

## 2. Fuentes generales consultadas

| Fuente | URL | Qué se tomó |
|---|---|---|
| USGS · Mineral Commodity Summaries 2025 (PDF completo) | https://pubs.usgs.gov/periodicals/mcs2025/mcs2025.pdf | Producción, comercio, usos y rangos de precios por mineral. |
| USGS · Minerals Yearbook (metales y minerales) | https://www.usgs.gov/centers/national-minerals-information-center/minerals-yearbook-metals-and-minerals | Contexto de producción mundial y mexicana; series históricas. |
| USGS · Mineral Commodity Summaries (índice) | https://www.usgs.gov/centers/national-minerals-information-center/mineral-commodity-summaries | Punto de entrada a capítulos anuales por sustancia. |
| Digitalfire · Materiales cerámicos | https://digitalfire.com/ | Análisis de óxidos, LOI, plasticidad, contracción, absorción y comportamiento en esmaltes. |
| Digitalfire · Glosario técnico | https://digitalfire.com/glossary/loi | Definiciones de LOI, plasticidad, absorción, malla, etc. |
| Servicio Geológico Mexicano (SGM) | https://www.sgm.gob.mx/ | Contexto geológico y de yacimientos de México. |
| Secretaría de Economía · minería | https://www.gob.mx/se | Contexto regulatorio y de comercio minero en México. |
| FDA · Talc in cosmetics | https://www.fda.gov/cosmetics/cosmetic-ingredients/talc | Contexto de seguridad para talco cosmético (no es certificación). |
| API · estándares de perforación | https://www.api.org/products-and-services/standards | Referencia conceptual para bentonita/barita de lodos (API 13A); el catálogo **no** certifica cumplimiento. |
| American Foundry Society (AFS) | https://www.afsinc.org/ | Pruebas de arena verde, AFS-GFN, bentonita de fundición. |
| Banco de México · tipo de cambio | https://www.banxico.org.mx/ | Conversión MXN/USD para precios de referencia. |
| LME · London Metal Exchange | https://www.lme.com/ | Referencia de metales base (Cu, Zn, Pb, Al); acceso a precios oficiales requiere cuenta. |
| Kitco · metales preciosos | https://www.kitco.com/ | Referencia de oro y plata para concentrados. |

### Capítulos USGS por mineral (verificados al 2026-11-11)

| Mineral / familia | Capítulo MCS 2025 |
|---|---|
| Arcillas (ball clay, bentonita, caolín, fuller's earth) | https://pubs.usgs.gov/periodicals/mcs2025/mcs2025-clays.pdf |
| Barita | https://pubs.usgs.gov/periodicals/mcs2025/mcs2025-barite.pdf |
| Tierras de diatomeas | https://pubs.usgs.gov/periodicals/mcs2025/mcs2025-diatomite.pdf |
| Feldespato | https://pubs.usgs.gov/periodicals/mcs2025/mcs2025-feldspar.pdf |
| Yeso | https://pubs.usgs.gov/periodicals/mcs2025/mcs2025-gypsum.pdf |
| Mica | https://pubs.usgs.gov/periodicals/mcs2025/mcs2025-mica.pdf |
| Perlita | https://pubs.usgs.gov/periodicals/mcs2025/mcs2025-perlite.pdf |
| Talco y pirofilita | https://pubs.usgs.gov/periodicals/mcs2025/mcs2025-talc.pdf |
| Wollastonita | https://pubs.usgs.gov/periodicals/mcs2025/mcs2025-wollastonite.pdf |
| Cobre | https://pubs.usgs.gov/periodicals/mcs2025/mcs2025-copper.pdf |
| Zinc | https://pubs.usgs.gov/periodicals/mcs2025/mcs2025-zinc.pdf |
| Plomo | https://pubs.usgs.gov/periodicals/mcs2025/mcs2025-lead.pdf |
| Plata | https://pubs.usgs.gov/periodicals/mcs2025/mcs2025-silver.pdf |
| Litio | https://pubs.usgs.gov/periodicals/mcs2025/mcs2025-lithium.pdf |
| Fluorita | https://pubs.usgs.gov/periodicals/mcs2025/mcs2025-fluorspar.pdf |
| Antimonio | https://pubs.usgs.gov/periodicals/mcs2025/mcs2025-antimony.pdf |
| Manganeso | https://pubs.usgs.gov/periodicals/mcs2025/mcs2025-manganese.pdf |
| Molibdeno | https://pubs.usgs.gov/periodicals/mcs2025/mcs2025-molybdenum.pdf |
| Tungsteno | https://pubs.usgs.gov/periodicals/mcs2025/mcs2025-tungsten.pdf |
| Bismuto | https://pubs.usgs.gov/periodicals/mcs2025/mcs2025-bismuth.pdf |

---

## 3. Fuentes por propiedad y rangos usados

Cada propiedad del JSON es un objeto EAV con `clave`, `nombre`, `grupo`, `valor` (numérico, listo para filtros), `valor_texto` (rango o cota legible) y `unidad`. Las claves son estables y en minúsculas.

### 3.1 Química

| Clave | Unidad | Fuente principal | Criterio aplicado |
|---|---|---|---|
| `sio2` | % | USGS MCS (clays, talc, feldspar, silica) + Digitalfire | Sílice reportada en base seca; en caolín 46-48 %, en sílice molida > 99 %. |
| `al2o3` | % | Digitalfire + TDS cerámicos | Caolín cerámico 35-39 %; fire clay 40-48 %; alúmina calcinada > 99 %. |
| `fe2o3` | % | Digitalfire + TDS | Se usa cota (`< 1.2`, etc.) cuando la especificación habitual es un máximo; el valor numérico es el límite. |
| `tio2` | % | Digitalfire | Trazas en caolines (< 0.3 %) y arcillas (0.3-1.2 %). |
| `k2o`, `na2o` | % | Digitalfire + TDS de feldespatos | Feldespato potásico K₂O 10-12 %; sódico Na₂O 9-10 %. |
| `cao`, `mgo` | % | TDS de carbonatos, dolomitas y talco | Dolomita ~30 % CaO y ~20 % MgO; talco ~31 % MgO. |
| `loi` | % | Digitalfire | Pérdida por calcinación coherente con carbonatos (43-44 %), caolín (13-14 %), talco (5-6 %). |
| `so3`, `p2o5` | % | TDS de yeso y fertilizantes | Yeso ~44-46 % SO₃; P₂O₅ traza en minerales de manganeso. |
| `cu`, `zn`, `pb`, `sb`, `mn`, `bi` | % | USGS MCS + normas de concentrados | Ley de concentrado, no ley de mineral de mina. |
| `ag`, `au` | g/t | USGS MCS + Kitco | Leyes de subproducto en concentrados; no se usa oz/t. |
| `li2o` | % | USGS lithium | Espodumena 5.5-6.5 % Li₂O. |
| `wo3`, `mos2` | % | USGS tungsten/molybdenum | Ley de concentrado (WO₃ 60-70 %, MoS₂ 85-90 %). |
| `caf2` | % | USGS fluorspar | Acidspar > 97 % CaF₂. |
| `baso4` | % | USGS barite | Barita de perforación 90-93 %; carga para pinturas 95-97 %. |
| `pureza`, `s`, `fe`, `as` | % | TDS y especificaciones de concentrados | Pureza de carbonato alimenticio > 99 %; azufre total en sulfuros; arsénico como penalizable. |

### 3.2 Físicas

| Clave | Unidad | Fuente principal | Criterio aplicado |
|---|---|---|---|
| `ph` | pH | TDS de bentonitas, carbonatos, yeso, zeolitas | Suspensión 10 % en agua; bentonita sódica 9-10, cálcica 8-8.5. |
| `densidad` | g/cm³ | USGS + TDS | Barita 4.2-4.4; sílice 2.65; alúmina 3.9. |
| `densidad_aparente` | g/cm³ | TDS de perlita, diatomita, mica | Perlita expandida 0.08-0.12; diatomita 0.3-0.4; mica en polvo 0.4-0.5. |
| `blancura` | % | TDS + ISO 2470 (concepto) | Caolín 85-90; talco cosmético > 90; carbonato 95-97. No se declara método ISO específico. |
| `plasticidad` | IP | Digitalfire + TDS de arcillas | Ball clay 25-30; caolín 18-22; fire clay 17-20. |
| `mbi` | meq/100 g | API 13A (concepto) + TDS de bentonitas | Bentonita sódica 90-100; cálcica 60-70; organofílica no reporta MBI. |
| `absorcion` | % | Digitalfire + TDS | Arcillas 6-10 %; chamota 4-5 %; atapulgita 30 % (adsorción, no absorción cerámica). |
| `refractario` | cono Orton | TDS de refractarios | Fire clay 34; alta alúmina 36; pirofilita 32. |
| `mohs` | Mohs | USGS + literatura mineralógica | Talco 1; mica 2.8; barita 3-3.5; wollastonita 4.5; alúmina 9. |
| `hinchamiento` | % | TDS de bentonitas | Sódica 20-28; cálcica 6-12; organofílica 15-22 (en solvente). |
| `superficie` | m²/g | BET (concepto) en TDS | Atapulgita 120-200; diatomita 20-30; caolín calcinado 10-15. |
| `aceite` | g/100 g | TDS de cargas | Caolín de carga 25-30; carbonato 15-20; ATH 25-35. |
| `cicec` | meq/100 g | TDS/USGS de zeolitas | Clinoptilolita 100-140. |
| `temperatura_uso` | °C | TDS de refractarios | Reservada para futuras fichas; no usada en esta versión. |

### 3.3 Granulometría

| Clave | Unidad | Fuente principal | Criterio aplicado |
|---|---|---|---|
| `d50` | µm | Difracción láser (ISO 13320, concepto) | Cuando el proveedor reporta D50; no se mezcla con malla. |
| `d97` | µm | Difracción láser | Reservada; no usada en esta versión. |
| `malla_60`, `malla_100`, `malla_200`, `malla_325` | % pasante | Tamices ASTM E11 / ISO 3310 (concepto) | Se usa la malla dominante de cada producto; no todas las fichas reportan todas. |
| `retenido_45` | % | Tamiz 45 µm | Reservada para arenas; no usada en esta versión. |

> En esta versión quedan **declaradas pero sin uso** en fichas: `malla_60`, `retenido_45`, `d97` y `temperatura_uso`. Se conservan en el registro para futuras fichas de arenas y refractarios, pero no deben mostrarse como filtros activos hasta que existan datos.
| `humedad` | % | TDS | Humedad de embarque: secos 0.1-1 %; arcillas 6-12 %; concentrados 5-10 %. |

### 3.4 Presentación

| Clave | Unidad | Fuente | Criterio |
|---|---|---|---|
| `saco_kg` | kg | TDS comerciales | Saco de 25 kg (estándar en México). |
| `bigbag_kg` | kg | TDS comerciales | Big bag de 500-1,000 kg; se usa 1,000 kg salvo excepción. |

> **Importante:** las fichas no replican un TDS real ni un certificado de análisis. Los valores son consistentes entre sí (p. ej. suma de óxidos + LOI cercana a 100 % en silicatos) y se eligieron dentro de los rangos publicados, pero **deben validarse contra el COA del proveedor** antes de una compra.

---

## 4. Fuentes y criterios por familia de materiales

| Categoría (slug) | Materiales incluidos | Fuentes principales | Notas de modelado |
|---|---|---|---|
| `arcillas-plasticas` | Ball clay, arcilla roja, arcilla illítica gris, atapulgita | USGS *clays*; Digitalfire | La atapulgita se incluye aquí por ser arcilla natural adsorbente; su uso principal es blanqueo/filtración. |
| `caolin` | Caolín cerámico, papelero, de carga, sanitario | USGS *clays*; Digitalfire; TDS de caolín | El caolín papelero no tiene una industria "papel" en la taxonomía; se asocia a pinturas y plásticos. Ver sección 7. |
| `bentonita` | Sódica, cálcica, perforación API, organofílica | USGS *clays*; API 13A (concepto); TDS | MBI, hinchamiento y pH solo se reportan en bentonitas. |
| `arcilla-refractaria` | Fire clay 40 %, alta alúmina 47 %, chamota | USGS *clays*; TDS de refractarios | Cono Orton orientativo; no sustituye ensayo pirométrico. |
| `silice` | Sílice molida, arena de fundición, filtración, frac sand | USGS MCS de sílice/arena; AFS | `d50` y mallas varían según producto; no se fuerza un análisis único. |
| `feldespatos` | Potásico, sódico, molido para esmaltes | USGS *feldspar*; Digitalfire | Álcalis y hierro controlados; granulometría fina para esmaltes. |
| `talco-pirofilita` | Talco industrial, cosmético, papelero, pirofilita | USGS *talc*; FDA (contexto cosmético); Digitalfire | El talco cosmético no declara grado farmacéutico real. |
| `carbonatos` | CaCO₃ micronizado, CaCO₃ alimenticio, dolomita agrícola, dolomita calcinada, magnesita | USGS MCS de caliza/dolomita/magnesita; TDS | CaCO₃ alimenticio: pureza y blancura de referencia, sin certificación sanitaria. |
| `sulfatos` | Yeso agrícola, yeso cerámico, barita perforación, barita pinturas | USGS *gypsum* y *barite*; TDS | Barita API: densidad y malla 325; no se certifica API 13A. |
| `zeolitas` | Clinoptilolita, tratamiento de agua, sustratos | USGS MCS de zeolitas naturales; TDS | CIC típica 100-140 meq/100 g; pureza mineralógica no verificada. |
| `minerales-no-ferrosos` | Concentrados de Cu, Zn, Pb, Ag, Mo; fluorita; Sb; Mn; Li; W; Bi | USGS MCS por metal; LME/Kitco (referencia) | Leyes de concentrado, no de mina; precios con lógica TC/RC. |
| `cargas-minerales` | Mica, perlita, diatomita, wollastonita, alúmina calcinada, ATH | USGS *mica*, *perlite*, *diatomite*, *wollastonite*; TDS | Alúmina calcinada y ATH se marcan como importadas/almacenadas en México. |

---

## 5. Cobertura de claves para el buscador facetado

El catálogo tiene **48 claves de propiedad distintas** y **550 valores** en 55 materiales (promedio 10 por ficha). Cobertura de los filtros solicitados para el catálogo público:

| Filtro | Clave | Materiales con dato | Cobertura por categoría |
|---|---|---|---|
| `al2o3_min` | `al2o3` | 41 / 55 | Todas las categorías industriales y arcillas; concentrados solo espodumena y bauxita/ATH. |
| `fe2o3_max` | `fe2o3` | 46 / 55 | Todas las categorías salvo algunos concentrados. |
| `blancura_min` | `blancura` | 15 / 55 | Caolín (4), talco/pirofilita (4), cargas (4), carbonatos (2), barita (1). |
| `humedad_max` | `humedad` | 54 / 55 | Solo falta en un material (bismuto metálico, que no maneja humedad de mineral). |
| `ph_min` | `ph` | 8 / 55 | Bentonitas (4), atapulgita, dolomita agrícola, yeso agrícola, zeolita de sustratos. |
| `mbi_min` | `mbi` | 3 / 55 | Solo bentonitas sódica, cálcica y de perforación (la organofílica no reporta MBI acuoso). |
| `d50_max` | `d50` | 22 / 55 | Caolín, sílice, feldespatos, cargas, carbonatos, talco, zeolitas, sulfatos, concentrados. |
| `sio2_min` | `sio2` | 50 / 55 | Todas las categorías; faltan 5 concentrados metálicos. |

**Recomendación de UI:** tratar cada propiedad como opcional. Un material sin la clave no debe excluirse cuando el filtro está vacío; cuando el filtro está activo, se incluyen únicamente los materiales con valor numérico dentro del rango. Para arcillas que reportan malla en lugar de D50 (`malla_200`, `malla_325`), el frontend puede mostrar la malla como dato sustituto, pero no debe compararla numéricamente con `d50`.

> **No falta ninguna de las 8 claves solicitadas.** Si el Lead quiere que `d50` cubra el 100 % de materiales, habría que añadir D50 estimado a arcillas y concentrados; se recomienda no inventarlo y dejar el filtro como opcional.

---

## 6. Precios de referencia

- **Moneda y unidad:** MXN/USD por **tonelada** (`unidad = "tonelada"`). Los concentrados no ferrosos se expresan por tonelada de concentrado, **no** por unidad de metal fino; la liquidación real se hace por metal pagable y requiere TC/RC, penalizaciones y ajuste por ley.
- **Naturaleza:** precios de **referencia** para comparar órdenes de magnitud. No son ofertas, no incluyen IVA, flete, maniobras, muestreo, ensayes, seguros ni financiamiento.
- **Fecha de referencia:** 2025-2026, con base en rangos de USGS MCS, LME/Kitco y TDS de la industria. El tipo de cambio MXN/USD debe actualizarse con Banxico antes de publicar.
- **Materiales con precio en USD (16):** caolín papelero, bentonita organofílica, magnesita, fluorita, concentrados de Cu/Zn/Pb/Ag/Sb, manganeso, litio, tungsteno, molibdeno, bismuto, alúmina calcinada y ATH. El resto (39) está en MXN.

### Rangos por categoría en el catálogo (referencia, por tonelada)

| Categoría | Rango MXN/t | Rango USD/t |
|---|---|---|
| Arcillas plásticas | 1,200 – 3,800 | — |
| Caolín | 4,800 – 5,600 | 390 |
| Bentonita | 2,400 – 4,200 | 3,900 (organofílica) |
| Arcilla refractaria | 2,800 – 4,500 | — |
| Sílice | 1,900 – 2,900 | — |
| Feldespatos | 2,900 – 3,600 | — |
| Talco y pirofilita | 3,100 – 7,500 | — |
| Carbonatos | 1,100 – 3,900 | 420 (magnesita) |
| Sulfatos | 950 – 4,000 | — |
| Zeolitas | 2,300 – 3,300 | — |
| Minerales no ferrosos | — | 420 – 22,000 |
| Cargas minerales | 3,000 – 6,200 | 650 – 850 |

> Los valores del JSON son un **punto medio** dentro del rango comercial típico. El campo `valor_texto` de propiedades sí conserva rangos cuando aplica, pero el precio se guarda como `DECIMAL(12,2)` para poder ordenar y comparar; la UI debe acompañarlo siempre de la leyenda "precio de referencia, no oferta".

---

## 7. Decisiones de modelado para integrar

1. **Claves de propiedad nuevas** (además de las canónicas del encargo): `cu`, `zn`, `pb`, `ag`, `au`, `sb`, `as`, `mn`, `fe`, `li2o`, `wo3`, `mos2`, `bi`, `caf2`, `baso4`, `pureza`, `s`, `cicec`, `densidad_aparente`, `d97`, `malla_100`, `temperatura_uso`. `material_properties.clave` es `VARCHAR(60)`, por lo que no requieren migración; solo deben registrarse en la configuración de filtros del frontend/panel si se quieren facetar.
2. **Filtro `ph_min` y `mbi_min`:** por su naturaleza solo aplican a bentonitas, zeolitas y algunos correctores; el resto de fichas no los reporta. Evitar mostrarlos como filtros globales obligatorios.
3. **Sin industria "papel":** la taxonomía de 10 industrias no incluye papel/celulosa. `caolin-papelero` y `talco-papelero` se asociaron a `pinturas-recubrimientos` y `plasticos-caucho` para que tengan facetas; si el negocio mueve volumen papelero, conviene añadir una industria `papel-celulosa` en una siguiente versión (requiere cambiar el JSON y el conteo de 10).
4. **Proveedor principal:** el primer slug de `proveedores[]` se marca `es_principal = 1` en `material_suppliers`; los demás quedan en 0. Si el broker quiere ordenar por cercanía o capacidad, debe editar la relación, no el JSON.
5. **Precio por proveedor:** en esta versión `material_suppliers.precio_ref` replica el precio del material, con nota "Proveedor DEMO; precio de referencia, no oferta". Para precios diferenciados por proveedor hay que extender el JSON (objetos en `proveedores[]`) y regenerar.
6. **`disponibilidad`:** se hereda del material a cada relación; los concentrados y grados especiales usan `programada` o `bajo_pedido` para reflejar que no son inventario permanente.
7. **Regiones:** los estados citados corresponden a provincias geológicas conocidas (Guanajuato/Hidalgo para arcillas y caolín; Nuevo León/Coahuila para bentonita y barita; Sonora para cobre y litio; Zacatecas para zeolita y carbonatos), pero **no** a un yacimiento ni a un proveedor verificable. Son referencias de demostración.
8. **Settings sembrados (15):** `sitio_nombre`, `sitio_titulo`, `sitio_descripcion`, `sitio_email`, `sitio_telefono`, `sitio_whatsapp`, `sitio_direccion`, `empresa_nombre`, `empresa_rfc`, `sitio_aviso_privacidad`, `home_hero_titulo`, `home_hero_subtitulo`, `home_hero_cta`, `catalogo_nota_demo`, `precios_nota`. El seed **no** crea usuarios distintos del admin demo `admin@arcillas.mx`.

---

## 8. Cómo sustituir los datos DEMO por datos reales

1. Editar `resources/data/catalogo.json` (nunca editar `database/seed.sql` a mano: se regenera).
2. Cambiar proveedores: nombre real, `estado`, `ciudad`, `capacidad`, certificaciones verificadas, `sitio_web`, `email_publico`; poner `es_demo = 0` y `verificado = 1` solo cuando el broker haya validado la empresa. El validar del seeder/`dry-run` avisa si un proveedor no lleva el sufijo `Demo` (puede omitirse el aviso cuando ya sea real).
3. Reemplazar propiedades con el COA de cada lote/producto y conservar las claves para no romper filtros.
4. Actualizar precios con fecha, moneda, unidad, incoterm y nota de vigencia; no publicar como oferta.
5. Ejecutar las validaciones:

```bash
# 1) Validación sin base de datos
php database/seed.php --dry-run

# 2) Regenerar el SQL portable
php tools/generar_seed_sql.php

# 3) Sembrar por PHP (inserta faltantes y conserva leads/usuarios)
php database/seed.php

# 4) Actualizar registros semilla existentes (solo cuando se quiera refrescar el catálogo)
php database/seed.php --force

# 5) Despliegue sin PHP (Hostinger): aplicar el SQL
mysql -h HOST -P PORT -u USER -p BASE < database/seed.sql
```

6. Revisar el aviso legal y el texto de `catalogo_nota_demo`/`precios_nota` antes de quitar la marca de demostración.

**Garantías de idempotencia:** `seed.php` y `seed.sql` usan `INSERT ... ON DUPLICATE KEY UPDATE`; no borran leads, ni usuarios, ni materiales editados. El usuario admin demo solo se crea si no existe y nunca se sobrescribe su contraseña.

---

## 9. Limitaciones conocidas

- No hay certificados de análisis reales, licencias, concesiones mineras, ni registros sanitarios.
- Las URL de USGS, Digitalfire y SGM se consultaron para rangos públicos; los valores **no** provienen de un TDS específico de un proveedor mexicano real.
- Los precios no incorporan logística México-Asia/Europa, ni contratos de largo plazo, ni primas por bajo contenido de impurezas.
- No se incluyen imágenes ni documentos TDS/SDS (`material_docs` queda vacío); el broker debe subirlos.
- La información de costos TC/RC de concentrados se menciona conceptualmente; no se modela en la semilla para no presentar cifras de maquila no verificadas.

---

## 10. Control de cambios

| Versión | Fecha | Cambio |
|---|---|---|
| 1.0.0 | 2026-11-11 | Catálogo inicial: 10 industrias, 12 categorías, 10 proveedores DEMO, 55 materiales, 550 propiedades, 15 settings, seeder PHP y `seed.sql`. |
