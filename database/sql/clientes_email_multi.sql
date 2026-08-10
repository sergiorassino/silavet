-- Amplía clientes.email para varios correos separados por ;
-- (migración 2026_08_10_000001_widen_clientes_email_multi.php)
-- Idempotente: seguro re-ejecutar.

ALTER TABLE `clientes`
  MODIFY COLUMN `email` VARCHAR(500) NOT NULL DEFAULT '';
