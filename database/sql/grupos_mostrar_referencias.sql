-- Encabezado «VALORES DE REFERENCIA» por grupo en el PDF del informe.
-- 1 = mostrar, 0 = ocultar. Default 1 (comportamiento previo de la mayoría).
-- OBSERVACIONES queda en 0 y además el PDF lo oculta siempre por código.
-- INFORME DE ECOGRAFÍA pasa de lista hardcodeada a este flag (queda en 0).
-- Uso preferido: php artisan lb:migrate-legacy --force
--
-- MySQL < 8.0.12 no soporta IF NOT EXISTS en ADD COLUMN.
-- Si falla, ejecutar solo si la columna no existe:

ALTER TABLE `grupos`
    ADD COLUMN IF NOT EXISTS `mostrarReferencias` tinyint(1) NOT NULL DEFAULT 1 AFTER `orden`;

-- Alternativa sin IF NOT EXISTS:
-- ALTER TABLE `grupos`
--     ADD COLUMN `mostrarReferencias` tinyint(1) NOT NULL DEFAULT 1 AFTER `orden`;

UPDATE `grupos`
SET `mostrarReferencias` = 0
WHERE UPPER(`nombreGrupo`) IN ('OBSERVACIONES', 'INFORME DE ECOGRAFÍA');
