-- Uso preferido: php artisan lb:migrate-legacy --force

ALTER TABLE `tipodeterminaciones`
    ADD COLUMN IF NOT EXISTS `precio2` DECIMAL(20,2) NOT NULL DEFAULT 0.00 AFTER `precio`;

ALTER TABLE `tipodeterminaciones`
    ADD COLUMN IF NOT EXISTS `precio3` DECIMAL(20,2) NOT NULL DEFAULT 0.00 AFTER `precio2`;

-- MySQL < 8.0.12 no soporta IF NOT EXISTS en ADD COLUMN.
-- Si falla, usar este bloque alternativo (ejecutar solo las líneas que falten):

-- ALTER TABLE `tipodeterminaciones`
--     ADD COLUMN `precio2` DECIMAL(20,2) NOT NULL DEFAULT 0.00 AFTER `precio`;
-- ALTER TABLE `tipodeterminaciones`
--     ADD COLUMN `precio3` DECIMAL(20,2) NOT NULL DEFAULT 0.00 AFTER `precio2`;
