-- ============================================================================
--  Kardex UD - Migracion: ciclo de vida de usuarios + administrador unico
--  Fecha: 2026-11-09
--  Base de datos: kardex
-- ============================================================================
--  Que hace:
--    1. Convierte usuarios.estado (TINYINT 1/0) en un ENUM de 4 estados:
--       ACTIVO / INACTIVO / SUSPENDIDO / ELIMINADO  (borrado logico)
--    2. Agrega metadatos del cambio de estado (motivo, vigencia, quien y cuando)
--    3. Agrega indices para los filtros del modulo de usuarios
--    4. Deja UN SOLO administrador activo: si hay varios usuarios con rol
--       administrador, conserva el mas antiguo y pasa el resto a INACTIVO
--
--  Como ejecutar:
--    mysql -u root kardex < db/migrations/2026_11_09_estados_usuarios.sql
--    (o pegar el archivo completo en la pestana SQL de phpMyAdmin)
--
--  Es idempotente: se puede ejecutar varias veces sin efectos secundarios.
-- ============================================================================

DROP PROCEDURE IF EXISTS kardex_migrar_estados_usuarios;

DELIMITER $$

CREATE PROCEDURE kardex_migrar_estados_usuarios()
BEGIN
    DECLARE v_tipo_estado VARCHAR(64) DEFAULT NULL;
    DECLARE v_admin_id    INT         DEFAULT NULL;

    -- ------------------------------------------------------------------
    -- 1. usuarios.estado -> ENUM de 4 estados
    -- ------------------------------------------------------------------
    SELECT DATA_TYPE INTO v_tipo_estado
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME   = 'usuarios'
       AND COLUMN_NAME  = 'estado';

    IF v_tipo_estado IS NULL THEN
        -- La columna no existe: se crea directamente con el tipo final
        ALTER TABLE usuarios
            ADD COLUMN estado ENUM('ACTIVO','INACTIVO','SUSPENDIDO','ELIMINADO')
                NOT NULL DEFAULT 'ACTIVO' AFTER rol;

    ELSEIF v_tipo_estado <> 'enum' THEN
        -- Venia como TINYINT/INT: se traduce 1 -> ACTIVO, 0 -> INACTIVO
        ALTER TABLE usuarios
            ADD COLUMN estado_tmp ENUM('ACTIVO','INACTIVO','SUSPENDIDO','ELIMINADO')
                NOT NULL DEFAULT 'ACTIVO';

        UPDATE usuarios SET estado_tmp = IF(estado + 0 = 0, 'INACTIVO', 'ACTIVO');

        ALTER TABLE usuarios DROP COLUMN estado;
        ALTER TABLE usuarios
            CHANGE COLUMN estado_tmp estado ENUM('ACTIVO','INACTIVO','SUSPENDIDO','ELIMINADO')
                NOT NULL DEFAULT 'ACTIVO';
    END IF;

    -- ------------------------------------------------------------------
    -- 2. Metadatos del cambio de estado
    -- ------------------------------------------------------------------
    IF (SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios'
           AND COLUMN_NAME = 'estado_motivo') = 0 THEN
        ALTER TABLE usuarios
            ADD COLUMN estado_motivo VARCHAR(255) NULL COMMENT 'Motivo del ultimo cambio de estado';
    END IF;

    IF (SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios'
           AND COLUMN_NAME = 'suspendido_hasta') = 0 THEN
        ALTER TABLE usuarios
            ADD COLUMN suspendido_hasta DATE NULL COMMENT 'Fin de la suspension; NULL = indefinida';
    END IF;

    IF (SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios'
           AND COLUMN_NAME = 'estado_cambiado_por') = 0 THEN
        ALTER TABLE usuarios
            ADD COLUMN estado_cambiado_por INT NULL COMMENT 'ID del admin que cambio el estado';
    END IF;

    IF (SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios'
           AND COLUMN_NAME = 'estado_cambiado_en') = 0 THEN
        ALTER TABLE usuarios
            ADD COLUMN estado_cambiado_en DATETIME NULL;
    END IF;

    IF (SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios'
           AND COLUMN_NAME = 'eliminado_en') = 0 THEN
        ALTER TABLE usuarios
            ADD COLUMN eliminado_en DATETIME NULL COMMENT 'Fecha del borrado logico';
    END IF;

    -- ------------------------------------------------------------------
    -- 3. Indices
    -- ------------------------------------------------------------------
    IF (SELECT COUNT(*) FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios'
           AND INDEX_NAME = 'idx_usuarios_estado') = 0 THEN
        ALTER TABLE usuarios ADD INDEX idx_usuarios_estado (estado);
    END IF;

    IF (SELECT COUNT(*) FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios'
           AND INDEX_NAME = 'idx_usuarios_rol_estado') = 0 THEN
        ALTER TABLE usuarios ADD INDEX idx_usuarios_rol_estado (rol, estado);
    END IF;

    -- ------------------------------------------------------------------
    -- 4. Un solo administrador: conserva el mas antiguo, inactiva el resto
    -- ------------------------------------------------------------------
    SELECT MIN(u.id) INTO v_admin_id
      FROM usuarios u
      JOIN roles r ON r.nombre = u.rol
     WHERE r.es_admin = 1
       AND u.estado <> 'ELIMINADO';

    IF v_admin_id IS NOT NULL THEN
        UPDATE usuarios u
          JOIN roles r ON r.nombre = u.rol
           SET u.estado             = 'INACTIVO',
               u.estado_motivo      = 'Inactivado por migracion: el sistema admite un solo administrador',
               u.estado_cambiado_por = v_admin_id,
               u.estado_cambiado_en = NOW()
         WHERE r.es_admin = 1
           AND u.estado <> 'ELIMINADO'
           AND u.id <> v_admin_id;

        -- El administrador que queda siempre debe estar activo
        UPDATE usuarios
           SET estado = 'ACTIVO', suspendido_hasta = NULL, eliminado_en = NULL
         WHERE id = v_admin_id;
    END IF;
END$$

DELIMITER ;

CALL kardex_migrar_estados_usuarios();
DROP PROCEDURE kardex_migrar_estados_usuarios;

-- ============================================================================
--  Verificacion (debe devolver exactamente 1 administrador)
-- ============================================================================
SELECT u.id, u.nombre, u.documento, u.rol, u.estado AS estado_admin
  FROM usuarios u
  JOIN roles r ON r.nombre = u.rol
 WHERE r.es_admin = 1
   AND u.estado = 'ACTIVO';

SELECT estado, COUNT(*) AS total FROM usuarios GROUP BY estado;
