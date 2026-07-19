-- Facturación AFIP: formato impresión en entorno + idCompAfipAsoc en compafip
-- Idempotente. Ejecutar manualmente en el cliente MySQL (no desde el agente).
-- Alternativa por lab: php artisan lb:switch <slug> && php artisan lb:migrate-legacy --force

-- entorno.afipFormatoImpresion (A4 | termica80)
SET @silavet_schema := DATABASE();

SET @silavet_sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = @silavet_schema AND TABLE_NAME = 'entorno'
        ) AND NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = @silavet_schema
              AND TABLE_NAME = 'entorno'
              AND COLUMN_NAME = 'afipFormatoImpresion'
        ),
        'ALTER TABLE `entorno` ADD COLUMN `afipFormatoImpresion` VARCHAR(20) NOT NULL DEFAULT ''A4''',
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

-- compafip.idCompAfipAsoc (factura asociada a NC)
SET @silavet_sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = @silavet_schema AND TABLE_NAME = 'compafip'
        ) AND NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = @silavet_schema
              AND TABLE_NAME = 'compafip'
              AND COLUMN_NAME = 'idCompAfipAsoc'
        ),
        'ALTER TABLE `compafip` ADD COLUMN `idCompAfipAsoc` INT UNSIGNED NULL DEFAULT NULL, ADD INDEX `compafip_idCompAfipAsoc_index` (`idCompAfipAsoc`)',
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;
