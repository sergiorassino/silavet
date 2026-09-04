-- Cambia renglones.valor2 de VARCHAR(100) a TEXT (igual que valor).
-- Conserva el contenido. Todos los laboratorios.
--
-- Preferido: php artisan lb:migrate-legacy --force
--   (migración 2026_09_04_000002_widen_renglones_valor2_to_text.php;
--   idempotente y respeta charset/collation de cada BD)
--
-- Este SQL es el equivalente si se aplica a mano. Si la columna ya es TEXT, no hace falta.

ALTER TABLE `renglones`
    MODIFY COLUMN `valor2` text CHARACTER SET utf8mb3 COLLATE utf8mb3_spanish_ci DEFAULT NULL;
