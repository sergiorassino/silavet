-- IVA discriminado para facturación de laboratorio responsable inscripto.
-- Obligatorio antes de activar facturacion_afip.regimen = responsable_inscripto.
-- Los laboratorios monotributistas pueden seguir facturando sin estas columnas.

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
              AND COLUMN_NAME = 'impNeto'
        ),
        'ALTER TABLE `compafip` ADD COLUMN `impNeto` DECIMAL(12,2) NULL DEFAULT NULL AFTER `importe`',
        'SELECT ''compafip.impNeto ya existe o falta tabla compafip'' AS info'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

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
              AND COLUMN_NAME = 'impIva'
        ),
        'ALTER TABLE `compafip` ADD COLUMN `impIva` DECIMAL(12,2) NULL DEFAULT NULL AFTER `impNeto`',
        'SELECT ''compafip.impIva ya existe o falta tabla compafip'' AS info'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

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
              AND COLUMN_NAME = 'alicuotaIva'
        ),
        'ALTER TABLE `compafip` ADD COLUMN `alicuotaIva` DECIMAL(5,2) NULL DEFAULT NULL AFTER `impIva`',
        'SELECT ''compafip.alicuotaIva ya existe o falta tabla compafip'' AS info'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
