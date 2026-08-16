-- Listas de precio 1/2/3 por protocolo y por cliente.
-- Default = 1 (tipodeterminaciones.precio).
-- Idempotente: no toca la columna si ya existe (laboratoriosiv ya tiene
-- pacientes.listaPreciosPaciente).
-- Uso preferido: php artisan lb:migrate-legacy --force
--
-- MySQL < 8.0.12 no soporta IF NOT EXISTS en ADD COLUMN.

SET @silavet_schema := DATABASE();

-- ---- pacientes.listaPreciosPaciente ----
SET @silavet_sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = @silavet_schema AND TABLE_NAME = 'pacientes'
        ) AND NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = @silavet_schema
              AND TABLE_NAME = 'pacientes'
              AND COLUMN_NAME = 'listaPreciosPaciente'
        ),
        IF(
            EXISTS (
                SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = @silavet_schema
                  AND TABLE_NAME = 'pacientes'
                  AND COLUMN_NAME = 'propietario'
            ),
            'ALTER TABLE `pacientes` ADD COLUMN `listaPreciosPaciente` TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER `propietario`',
            'ALTER TABLE `pacientes` ADD COLUMN `listaPreciosPaciente` TINYINT UNSIGNED NOT NULL DEFAULT 1'
        ),
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

-- ---- clientes.listaPreciosCliente ----
SET @silavet_sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = @silavet_schema AND TABLE_NAME = 'clientes'
        ) AND NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = @silavet_schema
              AND TABLE_NAME = 'clientes'
              AND COLUMN_NAME = 'listaPreciosCliente'
        ),
        IF(
            EXISTS (
                SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = @silavet_schema
                  AND TABLE_NAME = 'clientes'
                  AND COLUMN_NAME = 'descuento'
            ),
            'ALTER TABLE `clientes` ADD COLUMN `listaPreciosCliente` TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER `descuento`',
            'ALTER TABLE `clientes` ADD COLUMN `listaPreciosCliente` TINYINT UNSIGNED NOT NULL DEFAULT 1'
        ),
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;
