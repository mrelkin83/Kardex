-- ============================================================================
--  Kardex UD - Esquema completo de la base de datos
--  Derivado del codigo de modelos/ y controladores/ (no habia dump en el repo)
--  Fecha: 2026-11-09
-- ============================================================================
--  Incluye ya el ENUM de estados de usuario, por lo que una base creada con
--  este archivo NO necesita correr db/migrations/2026_11_09_estados_usuarios.sql
--
--  Uso:
--    mysql -u root -p < db/schema.sql
-- ============================================================================

CREATE DATABASE IF NOT EXISTS kardex
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kardex;

-- ---------------------------------------------------------------- sedes -----
CREATE TABLE IF NOT EXISTS sedes (
  id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre  VARCHAR(120) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_sedes_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La sede 1 es la Bodega Central: el codigo la trata como caso especial
-- (origen de ENTRADA_BODEGA y de SALIDA_A_SEDE) y no se puede eliminar.

-- ---------------------------------------------------------------- roles -----
CREATE TABLE IF NOT EXISTS roles (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre     VARCHAR(50)  NOT NULL COMMENT 'Codigo interno, ej: ENFERMERA',
  etiqueta   VARCHAR(100) NOT NULL COMMENT 'Nombre visible',
  servicio   VARCHAR(50)      NULL COMMENT 'Servicio usado al registrar entregas',
  es_admin   TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '1 = administra la bodega',
  protegido  TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '1 = rol del sistema, no editable',
  PRIMARY KEY (id),
  UNIQUE KEY uq_roles_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------- usuarios -----
CREATE TABLE IF NOT EXISTS usuarios (
  id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre              VARCHAR(150) NOT NULL,
  documento           VARCHAR(30)  NOT NULL COMMENT 'Tambien es el usuario de ingreso',
  password            VARCHAR(255) NOT NULL COMMENT 'Hash de password_hash()',
  rol                 VARCHAR(50)  NOT NULL COMMENT 'Referencia a roles.nombre',
  sede_id             INT UNSIGNED NOT NULL,
  estado              ENUM('ACTIVO','INACTIVO','SUSPENDIDO','ELIMINADO')
                        NOT NULL DEFAULT 'ACTIVO',
  estado_motivo       VARCHAR(255) NULL COMMENT 'Motivo del ultimo cambio de estado',
  suspendido_hasta    DATE         NULL COMMENT 'Fin de la suspension; NULL = indefinida',
  estado_cambiado_por INT UNSIGNED NULL COMMENT 'Admin que cambio el estado',
  estado_cambiado_en  DATETIME     NULL,
  eliminado_en        DATETIME     NULL COMMENT 'Fecha del borrado logico',
  creado_en           TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_usuarios_documento (documento),
  KEY idx_usuarios_estado (estado),
  KEY idx_usuarios_rol_estado (rol, estado),
  KEY fk_usuarios_sede (sede_id),
  KEY fk_usuarios_actor (estado_cambiado_por),
  CONSTRAINT fk_usuarios_sede  FOREIGN KEY (sede_id)             REFERENCES sedes(id),
  CONSTRAINT fk_usuarios_actor FOREIGN KEY (estado_cambiado_por) REFERENCES usuarios(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------ productos -----
-- "Maestro" de catalogo. Los medicamentos usan principio_activo /
-- forma_farmaceutica / concentracion / presentacion; los dispositivos usan
-- marca / modelo / vida_util. estado = 1 significa activo en catalogo.
CREATE TABLE IF NOT EXISTS productos (
  id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tipo               ENUM('MEDICAMENTO','DISPOSITIVO') NOT NULL,
  codigo             VARCHAR(60)  NOT NULL,
  nombre             VARCHAR(255) NOT NULL,
  principio_activo   VARCHAR(255) NULL,
  forma_farmaceutica VARCHAR(120) NULL,
  concentracion      VARCHAR(120) NULL,
  presentacion       VARCHAR(120) NULL,
  registro           VARCHAR(120) NULL COMMENT 'Registro sanitario / INVIMA',
  marca              VARCHAR(120) NULL,
  modelo             VARCHAR(120) NULL,
  vida_util          VARCHAR(60)  NULL,
  estado             TINYINT(1)   NOT NULL DEFAULT 1,
  creado_en          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_productos_codigo (codigo),
  KEY idx_productos_tipo_estado (tipo, estado),
  KEY idx_productos_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------- lotes -----
-- Un lote por (producto, numero_lote). 'S/L' = sin lote asignado.
CREATE TABLE IF NOT EXISTS lotes (
  id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  producto_id       INT UNSIGNED NOT NULL,
  numero_lote       VARCHAR(80)  NOT NULL DEFAULT 'S/L',
  fecha_vencimiento DATE         NULL COMMENT 'NULL = no vence (dispositivos)',
  codigo_origen     VARCHAR(60)  NULL,
  creado_en         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_lotes_producto_numero (producto_id, numero_lote),
  KEY idx_lotes_vencimiento (fecha_vencimiento),
  CONSTRAINT fk_lotes_producto FOREIGN KEY (producto_id) REFERENCES productos(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------- movimientos_inventario -------
-- Libro mayor del inventario: el stock siempre se CALCULA sumando movimientos,
-- nunca se guarda. Semantica de cada tipo (ver InventarioModelo::caseStock):
--   ENTRADA_BODEGA   suma en la sede 1 (bodega central)
--   SALIDA_A_SEDE    resta en la sede 1 y suma en sede_id (traslado)
--   ENTREGA_PACIENTE resta en sede_id
--   BAJA_VENCIDO     resta en sede_id
CREATE TABLE IF NOT EXISTS movimientos_inventario (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  sede_id         INT UNSIGNED NOT NULL,
  lote_id         INT UNSIGNED NOT NULL,
  tipo_movimiento ENUM('ENTRADA_BODEGA','SALIDA_A_SEDE','ENTREGA_PACIENTE','BAJA_VENCIDO')
                    NOT NULL,
  cantidad        INT          NOT NULL,
  usuario_id      INT UNSIGNED NOT NULL,
  observacion     VARCHAR(255) NULL,
  fecha_hora      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_mov_lote (lote_id),
  KEY idx_mov_sede_tipo (sede_id, tipo_movimiento),
  KEY idx_mov_fecha (fecha_hora),
  KEY fk_mov_usuario (usuario_id),
  CONSTRAINT fk_mov_lote    FOREIGN KEY (lote_id)    REFERENCES lotes(id),
  CONSTRAINT fk_mov_sede    FOREIGN KEY (sede_id)    REFERENCES sedes(id),
  CONSTRAINT fk_mov_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------- entregas_pacientes ----
-- Detalle clinico de un movimiento ENTREGA_PACIENTE.
CREATE TABLE IF NOT EXISTS entregas_pacientes (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  movimiento_id   BIGINT UNSIGNED NOT NULL,
  servicio        VARCHAR(80)  NULL,
  profesional     VARCHAR(150) NULL,
  nombre_paciente VARCHAR(150) NULL,
  documento       VARCHAR(30)  NULL,
  PRIMARY KEY (id),
  KEY idx_entregas_movimiento (movimiento_id),
  KEY idx_entregas_documento (documento),
  CONSTRAINT fk_entregas_movimiento FOREIGN KEY (movimiento_id)
    REFERENCES movimientos_inventario(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------ auditoria -----
CREATE TABLE IF NOT EXISTS auditoria (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id INT UNSIGNED NOT NULL,
  accion     VARCHAR(500) NOT NULL,
  tabla      VARCHAR(50)  NOT NULL,
  fecha_hora DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_auditoria_usuario (usuario_id),
  KEY idx_auditoria_fecha (fecha_hora),
  CONSTRAINT fk_auditoria_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------- v_lotes -----
-- Lote aplanado con los datos de su producto. ALGORITHM=MERGE es necesario:
-- varias consultas hacen GROUP BY vl.lote_id y seleccionan otras columnas, lo
-- que solo pasa ONLY_FULL_GROUP_BY si la vista se fusiona con la consulta.
CREATE OR REPLACE ALGORITHM=MERGE VIEW v_lotes AS
SELECT
  l.id                AS lote_id,
  l.producto_id       AS producto_id,
  p.codigo            AS codigo,
  p.nombre            AS nombre_descripcion,
  p.tipo              AS tipo,
  l.numero_lote       AS numero_lote,
  l.fecha_vencimiento AS fecha_vencimiento,
  l.codigo_origen     AS codigo_origen
FROM lotes l
JOIN productos p ON p.id = l.producto_id;

-- ============================================================== semillas ====

INSERT INTO sedes (id, nombre) VALUES (1, 'Bodega Central')
  ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

INSERT INTO roles (nombre, etiqueta, servicio, es_admin, protegido) VALUES
  ('ADMIN',     'Administrador', NULL,         1, 1),
  ('ENFERMERA', 'Enfermera',     'ENFERMERIA', 0, 1)
  ON DUPLICATE KEY UPDATE etiqueta = VALUES(etiqueta);
