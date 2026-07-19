-- Columna pacientes.dni (documento del tutor / facturación AFIP).
-- Uso preferido: php artisan lb:migrate-legacy --force
--
-- MySQL < 8.0.12 no soporta IF NOT EXISTS en ADD COLUMN.
-- Si falla, ejecutar solo si la columna no existe:

ALTER TABLE `pacientes`
    ADD COLUMN IF NOT EXISTS `dni` VARCHAR(20) NOT NULL DEFAULT '' AFTER `propietario`;

-- Alternativa sin IF NOT EXISTS:
-- ALTER TABLE `pacientes`
--     ADD COLUMN `dni` VARCHAR(20) NOT NULL DEFAULT '' AFTER `propietario`;
