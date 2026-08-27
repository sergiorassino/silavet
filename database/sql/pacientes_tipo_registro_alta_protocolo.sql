-- Alta de protocolo: tipoRegistro debe quedar en 1 (no 0).
--
-- Diferencia de esquema: en NeoLab/alqu la columna nació como
--   `tipoRegistro` int(1) NOT NULL
-- En civetfranca (y otros labs donde schema_sync la agregó) quedó
--   `tipoRegistro` int(1) NOT NULL DEFAULT 0
-- Si un INSERT no envía el campo, MySQL usa 0 y el listado staff
-- (tipoRegistro = 1) no muestra el protocolo.
--
-- Idempotente. Ejecutar manualmente en el cliente MySQL (no desde el agente).
-- Alcance: tabla pacientes. El UPDATE de filas en 0 no toca ingresos/egresos (2/3).
-- Irreversible respecto de filas que hayan quedado en 0 a propósito (no aplica
-- en civetfranca: 0 = protocolo legacy / alta sin el campo).

SET @silavet_schema := DATABASE();

-- 1) Crear la columna si falta (default 1 = protocolo).
SET @silavet_sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = @silavet_schema AND TABLE_NAME = 'pacientes'
        ) AND NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = @silavet_schema
              AND TABLE_NAME = 'pacientes'
              AND COLUMN_NAME = 'tipoRegistro'
        ),
        IF(
            EXISTS (
                SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = @silavet_schema
                  AND TABLE_NAME = 'pacientes'
                  AND COLUMN_NAME = 'idCuentasdetalle'
            ),
            'ALTER TABLE `pacientes` ADD COLUMN `tipoRegistro` int(1) NOT NULL DEFAULT 1 AFTER `idCuentasdetalle`',
            'ALTER TABLE `pacientes` ADD COLUMN `tipoRegistro` int(1) NOT NULL DEFAULT 1'
        ),
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

-- 2) Si ya existe, el DEFAULT debe ser 1 (no 0).
SET @silavet_sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = @silavet_schema
              AND TABLE_NAME = 'pacientes'
              AND COLUMN_NAME = 'tipoRegistro'
        ),
        'ALTER TABLE `pacientes` MODIFY COLUMN `tipoRegistro` int(1) NOT NULL DEFAULT 1',
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

-- 3) Protocolos que quedaron en 0 (legacy / INSERT sin el campo) → 1.
--    No modifica ingresos (2) ni egresos (3).
UPDATE `pacientes`
SET `tipoRegistro` = 1
WHERE `tipoRegistro` = 0;
