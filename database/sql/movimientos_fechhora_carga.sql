-- movimientos.fechhoraCarga — fecha/hora real de carga (alta) del movimiento
-- Distinta de fechhora (fecha de negocio; puede ser anterior a la de carga).
-- Uso preferido: php artisan lb:migrate-legacy --force
-- Alternativa por laboratorio: php artisan lb:switch <slug> && php artisan lb:migrate-legacy --force

SET @silavet_schema = DATABASE();

SET @sql = (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = @silavet_schema AND TABLE_NAME = 'movimientos'
        )
        AND NOT EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = @silavet_schema
              AND TABLE_NAME = 'movimientos'
              AND COLUMN_NAME = 'fechhoraCarga'
        )
        AND EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = @silavet_schema
              AND TABLE_NAME = 'movimientos'
              AND COLUMN_NAME = 'fechhora'
        ),
        'ALTER TABLE `movimientos` ADD COLUMN `fechhoraCarga` DATETIME NULL DEFAULT NULL AFTER `fechhora`',
        IF(
            EXISTS (
                SELECT 1 FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = @silavet_schema AND TABLE_NAME = 'movimientos'
            )
            AND NOT EXISTS (
                SELECT 1 FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = @silavet_schema
                  AND TABLE_NAME = 'movimientos'
                  AND COLUMN_NAME = 'fechhoraCarga'
            ),
            'ALTER TABLE `movimientos` ADD COLUMN `fechhoraCarga` DATETIME NULL DEFAULT NULL',
            'SELECT ''movimientos.fechhoraCarga ya existe o falta tabla movimientos'' AS info'
        )
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
