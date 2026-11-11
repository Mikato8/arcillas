-- ═══════════════════════════════════════════════════════════════════════
-- Arcillas MX · ARCILLAS PROTEÍNAS Y DESARROLLOS GARCÍA
-- Esquema MySQL 8 / MariaDB 10.6+ · utf8mb4
--
-- Arquitectura inspirada en el directorio técnico de UL Prospector:
-- industrias → categorías → materiales (fichas técnicas EAV) → proveedores,
-- más captación de leads (cotización / muestra / proveedor) y calculadoras.
--
-- Uso local:   mysql -u root -p < database/schema.sql
-- En Hostinger: mysql -u u771496034_Arcillas -p u771496034_Arcillas < schema.sql
-- ═══════════════════════════════════════════════════════════════════════

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- ─────────────────────────────────────────────────────────────────────
-- Usuarios del panel administrativo
-- ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre          VARCHAR(120) NOT NULL,
  email           VARCHAR(190) NOT NULL,
  password_hash   VARCHAR(255) NOT NULL,
  rol             ENUM('admin','editor') NOT NULL DEFAULT 'editor',
  activo          TINYINT(1) NOT NULL DEFAULT 1,
  ultimo_acceso   DATETIME NULL,
  creado_en       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────
-- Configuración del sitio (clave/valor)
-- ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS settings (
  clave           VARCHAR(60) NOT NULL,
  valor           TEXT NULL,
  grupo           VARCHAR(40) NOT NULL DEFAULT 'general',
  etiqueta        VARCHAR(160) NOT NULL DEFAULT '',
  tipo            ENUM('texto','textarea','email','url','numero','booleano') NOT NULL DEFAULT 'texto',
  actualizado_en  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────
-- Industrias / sectores que consumen minerales (nivel 1 del directorio)
-- ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS industries (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug        VARCHAR(80) NOT NULL,
  nombre      VARCHAR(120) NOT NULL,
  descripcion TEXT NULL,
  icono       VARCHAR(40) NOT NULL DEFAULT 'industria',
  color       VARCHAR(20) NOT NULL DEFAULT '#8B5E3C',
  orden       SMALLINT NOT NULL DEFAULT 0,
  activo      TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uq_industries_slug (slug),
  KEY idx_industries_activo_orden (activo, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────
-- Categorías de minerales (nivel 2 del directorio)
-- ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS categories (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug        VARCHAR(80) NOT NULL,
  nombre      VARCHAR(120) NOT NULL,
  descripcion TEXT NULL,
  familia     ENUM('arcillas','industrial','no_ferroso','carga') NOT NULL DEFAULT 'industrial',
  orden       SMALLINT NOT NULL DEFAULT 0,
  activo      TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categories_slug (slug),
  KEY idx_categories_familia (familia, activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────
-- Proveedores / minas / plantas / distribuidores
-- ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS suppliers (
  id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug              VARCHAR(120) NOT NULL,
  nombre            VARCHAR(160) NOT NULL,
  tipo              ENUM('mina','planta','distribuidor','broker') NOT NULL DEFAULT 'mina',
  descripcion       TEXT NULL,
  pais              VARCHAR(80) NOT NULL DEFAULT 'México',
  estado            VARCHAR(80) NULL,
  ciudad            VARCHAR(80) NULL,
  capacidad         VARCHAR(120) NULL,
  certificaciones   VARCHAR(255) NULL,
  sitio_web         VARCHAR(190) NULL,
  email_publico     VARCHAR(190) NULL,
  telefono_publico  VARCHAR(40) NULL,
  logo              VARCHAR(255) NULL,
  verificado        TINYINT(1) NOT NULL DEFAULT 0,
  es_demo           TINYINT(1) NOT NULL DEFAULT 0,
  activo            TINYINT(1) NOT NULL DEFAULT 1,
  creado_en         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_suppliers_slug (slug),
  KEY idx_suppliers_activo (activo, verificado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────
-- Materiales / minerales (nivel 3: la ficha que se indexa y compara)
-- ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS materials (
  id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  category_id       INT UNSIGNED NOT NULL,
  nombre            VARCHAR(160) NOT NULL,
  slug              VARCHAR(160) NOT NULL,
  resumen           VARCHAR(320) NOT NULL DEFAULT '',
  descripcion       TEXT NULL,
  origen            VARCHAR(160) NULL,
  aplicaciones      TEXT NULL,
  presentacion      VARCHAR(255) NULL,
  disponibilidad    ENUM('inmediata','programada','bajo_pedido','agotado') NOT NULL DEFAULT 'inmediata',
  region            VARCHAR(120) NULL,
  precio_ref        DECIMAL(12,2) NULL,
  moneda            CHAR(3) NOT NULL DEFAULT 'MXN',
  unidad            VARCHAR(20) NOT NULL DEFAULT 'tonelada',
  imagen            VARCHAR(255) NULL,
  destacado         TINYINT(1) NOT NULL DEFAULT 0,
  es_demo           TINYINT(1) NOT NULL DEFAULT 0,
  meta_title        VARCHAR(190) NULL,
  meta_description  VARCHAR(320) NULL,
  vistas            INT UNSIGNED NOT NULL DEFAULT 0,
  activo            TINYINT(1) NOT NULL DEFAULT 1,
  creado_en         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_materials_slug (slug),
  KEY idx_materials_category (category_id, activo),
  KEY idx_materials_destacado (destacado, activo),
  CONSTRAINT fk_materials_category FOREIGN KEY (category_id) REFERENCES categories (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────
-- Propiedades técnicas (EAV): composición química, físicas, granulometría
-- Es lo que permite el buscador facetado tipo Prospector.
-- ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS material_properties (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  material_id INT UNSIGNED NOT NULL,
  grupo       ENUM('quimica','fisica','granulometria','presentacion') NOT NULL DEFAULT 'fisica',
  clave       VARCHAR(60) NOT NULL,
  nombre      VARCHAR(120) NOT NULL,
  valor       DECIMAL(12,4) NULL,
  valor_texto VARCHAR(120) NULL,
  unidad      VARCHAR(30) NULL,
  orden       SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_props_material_clave (material_id, clave),
  KEY idx_props_clave_valor (clave, valor),
  CONSTRAINT fk_props_material FOREIGN KEY (material_id) REFERENCES materials (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────
-- Relación material ↔ industria (aplicaciones por sector)
-- ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS material_industries (
  material_id  INT UNSIGNED NOT NULL,
  industry_id  INT UNSIGNED NOT NULL,
  aplicacion   VARCHAR(255) NULL,
  PRIMARY KEY (material_id, industry_id),
  KEY idx_mi_industry (industry_id),
  CONSTRAINT fk_mi_material FOREIGN KEY (material_id) REFERENCES materials (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_mi_industry FOREIGN KEY (industry_id) REFERENCES industries (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────
-- Relación material ↔ proveedor (quién lo suministra y en qué condiciones)
-- ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS material_suppliers (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  material_id   INT UNSIGNED NOT NULL,
  supplier_id   INT UNSIGNED NOT NULL,
  es_principal  TINYINT(1) NOT NULL DEFAULT 0,
  disponibilidad ENUM('inmediata','programada','bajo_pedido','agotado') NOT NULL DEFAULT 'inmediata',
  precio_ref    DECIMAL(12,2) NULL,
  moneda        CHAR(3) NOT NULL DEFAULT 'MXN',
  unidad        VARCHAR(20) NOT NULL DEFAULT 'tonelada',
  minimo        VARCHAR(80) NULL,
  notas         VARCHAR(255) NULL,
  activo        TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ms_material_supplier (material_id, supplier_id),
  KEY idx_ms_supplier (supplier_id),
  CONSTRAINT fk_ms_material FOREIGN KEY (material_id) REFERENCES materials (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_ms_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────
-- Documentos técnicos (TDS / fichas de seguridad / análisis)
-- ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS material_docs (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  material_id INT UNSIGNED NOT NULL,
  tipo        ENUM('ficha_tecnica','seguridad','analisis','cotizacion') NOT NULL DEFAULT 'ficha_tecnica',
  nombre      VARCHAR(160) NOT NULL,
  archivo     VARCHAR(255) NOT NULL,
  mime        VARCHAR(100) NOT NULL DEFAULT 'application/pdf',
  publico     TINYINT(1) NOT NULL DEFAULT 1,
  creado_en   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_docs_material (material_id),
  CONSTRAINT fk_docs_material FOREIGN KEY (material_id) REFERENCES materials (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────
-- Leads: cotizaciones, muestras, contacto y ofertas de proveedores
-- ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS leads (
  id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tipo              ENUM('cotizacion','muestra','contacto','proveedor','calculadora') NOT NULL DEFAULT 'contacto',
  nombre            VARCHAR(160) NOT NULL,
  empresa           VARCHAR(190) NULL,
  cargo             VARCHAR(120) NULL,
  email             VARCHAR(190) NOT NULL,
  telefono          VARCHAR(40) NULL,
  whatsapp          VARCHAR(40) NULL,
  material_id       INT UNSIGNED NULL,
  material_texto    VARCHAR(190) NULL,
  volumen           VARCHAR(80) NULL,
  unidad            VARCHAR(30) NULL,
  destino           VARCHAR(160) NULL,
  frecuencia        VARCHAR(80) NULL,
  especificaciones  TEXT NULL,
  mensaje           TEXT NULL,
  consentimiento    TINYINT(1) NOT NULL DEFAULT 0,
  estado            ENUM('nuevo','contactado','cotizado','ganado','perdido','spam') NOT NULL DEFAULT 'nuevo',
  fuente            VARCHAR(120) NULL,
  ip                VARCHAR(45) NULL,
  user_agent        VARCHAR(255) NULL,
  notas_internas    TEXT NULL,
  atendido_por      INT UNSIGNED NULL,
  creado_en         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_leads_estado_fecha (estado, creado_en),
  KEY idx_leads_tipo_fecha (tipo, creado_en),
  KEY idx_leads_email (email),
  KEY idx_leads_material (material_id),
  CONSTRAINT fk_leads_material FOREIGN KEY (material_id) REFERENCES materials (id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_leads_user FOREIGN KEY (atendido_por) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ítems de una solicitud múltiple de muestras (carrito de muestras)
CREATE TABLE IF NOT EXISTS lead_items (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  lead_id     BIGINT UNSIGNED NOT NULL,
  material_id INT UNSIGNED NOT NULL,
  cantidad    VARCHAR(60) NULL,
  unidad      VARCHAR(30) NULL,
  nota        VARCHAR(255) NULL,
  PRIMARY KEY (id),
  KEY idx_items_lead (lead_id),
  CONSTRAINT fk_items_lead FOREIGN KEY (lead_id) REFERENCES leads (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_items_material FOREIGN KEY (material_id) REFERENCES materials (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────
-- Bitácora de acciones administrativas
-- ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS logs (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id     INT UNSIGNED NULL,
  accion      VARCHAR(80) NOT NULL,
  entidad     VARCHAR(60) NULL,
  entidad_id  VARCHAR(40) NULL,
  detalle     VARCHAR(500) NULL,
  ip          VARCHAR(45) NULL,
  creado_en   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_logs_fecha (creado_en),
  CONSTRAINT fk_logs_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────
-- Ejecuciones de calculadoras (analítica de uso + leads indirectos)
-- ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS calculator_runs (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  calculadora VARCHAR(60) NOT NULL,
  input_json  JSON NULL,
  exito       TINYINT(1) NOT NULL DEFAULT 1,
  ip          VARCHAR(45) NULL,
  creado_en   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_calc_fecha (calculadora, creado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────
-- Búsquedas internas (para saber qué minerales piden y no encuentran)
-- ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS search_logs (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  termino     VARCHAR(190) NOT NULL,
  resultados  INT UNSIGNED NOT NULL DEFAULT 0,
  ip          VARCHAR(45) NULL,
  creado_en   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_search_fecha (creado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
