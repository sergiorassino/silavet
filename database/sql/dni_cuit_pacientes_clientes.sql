-- Asegura dni VARCHAR(8) y cuit VARCHAR(11) en pacientes y clientes.
-- Idempotente. Ejecutar manualmente en el cliente MySQL (no desde el agente).
-- Alternativa por lab: php artisan lb:switch <slug> && php artisan lb:migrate-legacy --force
--
-- Nota: MODIFY trunca valores más largos que el nuevo tamaño.

SET @silavet_schema := DATABASE();

-- ---- pacientes.dni VARCHAR(8) ----
SET @silavet_sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = @silavet_schema AND TABLE_NAME = 'pacientes'
        ) AND NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = @silavet_schema
              AND TABLE_NAME = 'pacientes'
              AND COLUMN_NAME = 'dni'
        ),
        IF(
            EXISTS (
                SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = @silavet_schema
                  AND TABLE_NAME = 'pacientes'
                  AND COLUMN_NAME = 'propietario'
            ),
            'ALTER TABLE `pacientes` ADD COLUMN `dni` VARCHAR(8) NOT NULL DEFAULT \'\' AFTER `propietario`',
            'ALTER TABLE `pacientes` ADD COLUMN `dni` VARCHAR(8) NOT NULL DEFAULT \'\''
        ),
        IF(
            EXISTS (
                SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = @silavet_schema
                  AND TABLE_NAME = 'pacientes'
                  AND COLUMN_NAME = 'dni'
                  AND (
                      LOWER(DATA_TYPE) <> 'varchar'
                      OR CHARACTER_MAXIMUM_LENGTH <> 8
                  )
            ),
            'ALTER TABLE `pacientes` MODIFY COLUMN `dni` VARCHAR(8) NOT NULL DEFAULT \'\'',
            'SELECT 1'
        )
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

-- ---- pacientes.cuit VARCHAR(11) ----
SET @silavet_sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = @silavet_schema AND TABLE_NAME = 'pacientes'
        ) AND NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = @silavet_schema
              AND TABLE_NAME = 'pacientes'
              AND COLUMN_NAME = 'cuit'
        ),
        IF(
            EXISTS (
                SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = @silavet_schema
                  AND TABLE_NAME = 'pacientes'
                  AND COLUMN_NAME = 'dni'
            ),
            'ALTER TABLE `pacientes` ADD COLUMN `cuit` VARCHAR(11) NOT NULL DEFAULT \'\' AFTER `dni`',
            'ALTER TABLE `pacientes` ADD COLUMN `cuit` VARCHAR(11) NOT NULL DEFAULT \'\''
        ),
        IF(
            EXISTS (
                SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = @silavet_schema
                  AND TABLE_NAME = 'pacientes'
                  AND COLUMN_NAME = 'cuit'
                  AND (
                      LOWER(DATA_TYPE) <> 'varchar'
                      OR CHARACTER_MAXIMUM_LENGTH <> 11
                  )
            ),
            'ALTER TABLE `pacientes` MODIFY COLUMN `cuit` VARCHAR(11) NOT NULL DEFAULT \'\'',
            'SELECT 1'
        )
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

-- ---- clientes.cuit VARCHAR(11) ----
SET @silavet_sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = @silavet_schema AND TABLE_NAME = 'clientes'
        ) AND NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = @silavet_schema
              AND TABLE_NAME = 'clientes'
              AND COLUMN_NAME = 'cuit'
        ),
        IF(
            EXISTS (
                SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = @silavet_schema
                  AND TABLE_NAME = 'clientes'
                  AND COLUMN_NAME = 'whatsapp'
            ),
            'ALTER TABLE `clientes` ADD COLUMN `cuit` VARCHAR(11) NOT NULL DEFAULT \'\' AFTER `whatsapp`',
            'ALTER TABLE `clientes` ADD COLUMN `cuit` VARCHAR(11) NOT NULL DEFAULT \'\''
        ),
        IF(
            EXISTS (
                SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = @silavet_schema
                  AND TABLE_NAME = 'clientes'
                  AND COLUMN_NAME = 'cuit'
                  AND (
                      LOWER(DATA_TYPE) <> 'varchar'
                      OR CHARACTER_MAXIMUM_LENGTH <> 11
                  )
            ),
            'ALTER TABLE `clientes` MODIFY COLUMN `cuit` VARCHAR(11) NOT NULL DEFAULT \'\'',
            'SELECT 1'
        )
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

-- ---- clientes.dni VARCHAR(8) ----
SET @silavet_sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = @silavet_schema AND TABLE_NAME = 'clientes'
        ) AND NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = @silavet_schema
              AND TABLE_NAME = 'clientes'
              AND COLUMN_NAME = 'dni'
        ),
        IF(
            EXISTS (
                SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = @silavet_schema
                  AND TABLE_NAME = 'clientes'
                  AND COLUMN_NAME = 'cuit'
            ),
            'ALTER TABLE `clientes` ADD COLUMN `dni` VARCHAR(8) NOT NULL DEFAULT \'\' AFTER `cuit`',
            'ALTER TABLE `clientes` ADD COLUMN `dni` VARCHAR(8) NOT NULL DEFAULT \'\''
        ),
        IF(
            EXISTS (
                SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = @silavet_schema
                  AND TABLE_NAME = 'clientes'
                  AND COLUMN_NAME = 'dni'
                  AND (
                      LOWER(DATA_TYPE) <> 'varchar'
                      OR CHARACTER_MAXIMUM_LENGTH <> 8
                  )
            ),
            'ALTER TABLE `clientes` MODIFY COLUMN `dni` VARCHAR(8) NOT NULL DEFAULT \'\'',
            'SELECT 1'
        )
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

-- Forzar DEFAULT '' (nunca 0) y limpiar sentinel '0' en filas existentes.
-- (Omitir ALTER/UPDATE de una columna si aún no existe en ese lab.)
ALTER TABLE `pacientes` MODIFY COLUMN `dni` VARCHAR(8) NOT NULL DEFAULT '';
ALTER TABLE `pacientes` MODIFY COLUMN `cuit` VARCHAR(11) NOT NULL DEFAULT '';
ALTER TABLE `clientes` MODIFY COLUMN `cuit` VARCHAR(11) NOT NULL DEFAULT '';
ALTER TABLE `clientes` MODIFY COLUMN `dni` VARCHAR(8) NOT NULL DEFAULT '';

UPDATE `pacientes` SET `dni` = '' WHERE `dni` IN ('0', 0);
UPDATE `pacientes` SET `cuit` = '' WHERE `cuit` IN ('0', 0);
UPDATE `clientes` SET `cuit` = '' WHERE `cuit` IN ('0', 0);
UPDATE `clientes` SET `dni` = '' WHERE `dni` IN ('0', 0);
