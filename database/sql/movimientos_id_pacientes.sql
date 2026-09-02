-- movimientos.idPacientes — vínculo opcional al protocolo (pacientes.idPacientes).
-- 0 = no asignado. Usado al borrar protocolos y, a futuro, para asignar caja a un caso.
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
              AND COLUMN_NAME = 'idPacientes'
        )
        AND EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = @silavet_schema
              AND TABLE_NAME = 'movimientos'
              AND COLUMN_NAME = 'idClientes'
        ),
        'ALTER TABLE `movimientos` ADD COLUMN `idPacientes` INT NOT NULL DEFAULT 0 AFTER `idClientes`, ADD INDEX `movimientos_idPacientes_index` (`idPacientes`)',
        IF(
            EXISTS (
                SELECT 1 FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = @silavet_schema AND TABLE_NAME = 'movimientos'
            )
            AND NOT EXISTS (
                SELECT 1 FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = @silavet_schema
                  AND TABLE_NAME = 'movimientos'
                  AND COLUMN_NAME = 'idPacientes'
            ),
            'ALTER TABLE `movimientos` ADD COLUMN `idPacientes` INT NOT NULL DEFAULT 0, ADD INDEX `movimientos_idPacientes_index` (`idPacientes`)',
            'SELECT ''movimientos.idPacientes ya existe o falta tabla movimientos'' AS info'
        )
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
