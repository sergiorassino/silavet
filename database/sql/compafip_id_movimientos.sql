-- compafip.idMovimientos — facturación AFIP sobre tabla movimientos (labvetciudad)
-- Ejecutar en la BD del laboratorio que use tesoreria_pacientes + facturacion_afip.modo = movimiento_caja.

SET @silavet_schema = DATABASE();

SET @sql = (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = @silavet_schema AND TABLE_NAME = 'compafip'
        )
        AND NOT EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = @silavet_schema
              AND TABLE_NAME = 'compafip'
              AND COLUMN_NAME = 'idMovimientos'
        ),
        'ALTER TABLE `compafip` ADD COLUMN `idMovimientos` INT UNSIGNED NULL DEFAULT NULL, ADD INDEX `compafip_idMovimientos_index` (`idMovimientos`)',
        'SELECT ''compafip.idMovimientos ya existe o falta tabla compafip'' AS info'
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
