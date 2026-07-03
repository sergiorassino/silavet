-- Catálogo inicial de permisos SILAVET (idempotente)
-- Ejecutar manualmente en el cliente MySQL.

CREATE TABLE IF NOT EXISTS `permisos_ia` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `orden` int unsigned NOT NULL,
  `tema` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permisos_ia_orden_unique` (`orden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `permisos_ia` (`orden`, `tema`, `descripcion`) VALUES
(0, 'Clientes', 'ABM clientes veterinarios'),
(1, 'Especies', 'ABM especies y razas'),
(2, 'Determinaciones', 'ABM tipos de determinación'),
(3, 'Protocolos', 'Recepción y gestión de protocolos'),
(4, 'Resultados', 'Carga de resultados'),
(5, 'Informes', 'Emisión y envío de informes'),
(6, 'Facturación', 'Comprobantes y cobranza'),
(7, 'Reactivos', 'Stock de reactivos'),
(8, 'Parámetros', 'Configuración del laboratorio'),
(9, 'Usuarios', 'ABM usuarios y roles');

-- Columna permisos_ia en usuarios (si no existe)
SET @col_exists := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'usuarios'
      AND COLUMN_NAME = 'permisos_ia'
);
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE `usuarios` ADD COLUMN `permisos_ia` TEXT NULL AFTER `password`',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
