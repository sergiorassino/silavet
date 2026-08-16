-- Centro de derivación predeterminado por tipo (FK a derivaciones.idDerivaciones).
-- 0 = sin derivar. Laboratorio SIV ya tiene esta columna con datos legacy: no ejecutar ahí.
-- Uso preferido: php artisan lb:migrate-legacy --force

ALTER TABLE `tipodeterminaciones`
    ADD COLUMN IF NOT EXISTS `derivacion` int(1) NOT NULL DEFAULT 0 AFTER `perfil`;

-- MySQL < 8.0.12 no soporta IF NOT EXISTS en ADD COLUMN.
-- Si falla, usar (solo si la columna no existe):

-- ALTER TABLE `tipodeterminaciones`
--     ADD COLUMN `derivacion` int(1) NOT NULL DEFAULT 0 AFTER `perfil`;
