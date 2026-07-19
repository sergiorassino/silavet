-- Columna pacientes.obsInterna (observaciones internas del protocolo).
-- Uso preferido: php artisan lb:migrate-legacy --force
--
-- MySQL < 8.0.12 no soporta IF NOT EXISTS en ADD COLUMN.
-- Si falla, ejecutar solo si la columna no existe:

ALTER TABLE `pacientes`
    ADD COLUMN IF NOT EXISTS `obsInterna` TEXT NULL DEFAULT NULL AFTER `observaciones`;

-- Alternativa sin IF NOT EXISTS:
-- ALTER TABLE `pacientes`
--     ADD COLUMN `obsInterna` TEXT NULL DEFAULT NULL AFTER `observaciones`;
