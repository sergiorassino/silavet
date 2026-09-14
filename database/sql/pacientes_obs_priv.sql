-- Columna pacientes.obsPriv (observación privada del laboratorio; no va al informe).
-- Uso preferido: php artisan lb:migrate-legacy --force
--
-- MySQL < 8.0.12 no soporta IF NOT EXISTS en ADD COLUMN.
-- Si falla, ejecutar solo si la columna no existe:

ALTER TABLE `pacientes`
    ADD COLUMN IF NOT EXISTS `obsPriv` TEXT NULL DEFAULT NULL AFTER `obsInterna`;

-- Alternativa sin IF NOT EXISTS:
-- ALTER TABLE `pacientes`
--     ADD COLUMN `obsPriv` TEXT NULL DEFAULT NULL AFTER `obsInterna`;
