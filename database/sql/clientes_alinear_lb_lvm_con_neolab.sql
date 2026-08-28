-- SILAVET — Alinear tabla `clientes` de lb_lvm con lb_neolab (modelo)
-- Generado: 2026-08-28
--
-- Comparación lb_neolab vs lb_lvm:
--   - Mismas columnas (11); tipos y defaults ya coinciden (email VARCHAR(500), dni, cuit, etc.).
--   - Diferencias pendientes:
--       1) Orden físico de columnas (dni/descuento/listaPreciosCliente/cuit desordenados en LVM).
--       2) Charset/collation de tabla: LVM utf8mb4_spanish_ci → NeoLab utf8mb3_spanish_ci.
--
-- Ejecutar sobre la BD destino:
--   USE `lb_lvm`;
--
-- Advertencia: CONVERT TO utf8mb3 puede fallar o truncar si hay caracteres de 4 bytes
-- (emoji, algunos símbolos) en nombre, email u otros campos de texto.

SET FOREIGN_KEY_CHECKS = 0;

-- 1) Orden y definición de columnas (igual que SHOW CREATE TABLE de lb_neolab.clientes)
ALTER TABLE `clientes`
  MODIFY COLUMN `nombre` varchar(200) NOT NULL DEFAULT '' AFTER `idClientes`,
  MODIFY COLUMN `direccion` varchar(200) NOT NULL DEFAULT '' AFTER `nombre`,
  MODIFY COLUMN `telefono1` varchar(50) NOT NULL DEFAULT '' AFTER `direccion`,
  MODIFY COLUMN `telefono2` varchar(50) NOT NULL DEFAULT '' AFTER `telefono1`,
  MODIFY COLUMN `email` varchar(500) NOT NULL DEFAULT '' AFTER `telefono2`,
  MODIFY COLUMN `whatsapp` varchar(20) NOT NULL DEFAULT '' AFTER `email`,
  MODIFY COLUMN `cuit` varchar(13) NOT NULL DEFAULT '' AFTER `whatsapp`,
  MODIFY COLUMN `dni` varchar(8) NOT NULL DEFAULT '' AFTER `cuit`,
  MODIFY COLUMN `descuento` decimal(6,2) DEFAULT NULL AFTER `dni`,
  MODIFY COLUMN `listaPreciosCliente` tinyint(3) unsigned NOT NULL DEFAULT 1 AFTER `descuento`;

-- 2) Charset/collation de tabla (NeoLab usa utf8mb3_spanish_ci)
ALTER TABLE `clientes`
  CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_spanish_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Verificación sugerida (debe coincidir con lb_neolab salvo AUTO_INCREMENT):
-- SHOW CREATE TABLE `clientes`;
-- SELECT COLUMN_NAME, ORDINAL_POSITION, COLUMN_TYPE, COLLATION_NAME
--   FROM information_schema.COLUMNS
--  WHERE TABLE_SCHEMA = 'lb_lvm' AND TABLE_NAME = 'clientes'
--  ORDER BY ORDINAL_POSITION;
