-- usuarios.crtVencimiento — fecha de vencimiento del certificado AFIP (.crt)
-- Se completa al cargar el archivo en Gestión de Usuarios.
-- Uso preferido: php artisan lb:migrate-legacy --force
-- Alternativa por laboratorio: php artisan lb:switch <slug> && php artisan lb:migrate-legacy --force

SET @silavet_schema = DATABASE();

SET @sql = (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = @silavet_schema AND TABLE_NAME = 'usuarios'
        )
        AND NOT EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = @silavet_schema
              AND TABLE_NAME = 'usuarios'
              AND COLUMN_NAME = 'crtVencimiento'
        )
        AND EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = @silavet_schema
              AND TABLE_NAME = 'usuarios'
              AND COLUMN_NAME = 'crt'
        ),
        'ALTER TABLE `usuarios` ADD COLUMN `crtVencimiento` DATE NULL DEFAULT NULL AFTER `crt`',
        IF(
            EXISTS (
                SELECT 1 FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = @silavet_schema AND TABLE_NAME = 'usuarios'
            )
            AND NOT EXISTS (
                SELECT 1 FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = @silavet_schema
                  AND TABLE_NAME = 'usuarios'
                  AND COLUMN_NAME = 'crtVencimiento'
            ),
            'ALTER TABLE `usuarios` ADD COLUMN `crtVencimiento` DATE NULL DEFAULT NULL',
            'SELECT ''usuarios.crtVencimiento ya existe o falta tabla usuarios'' AS info'
        )
    )
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
