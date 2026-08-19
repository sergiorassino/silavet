-- SILAVET — sincronización aditiva de esquema
-- Generado por: php artisan lb:schema-sync
-- Modelo : lb_neolab
-- Destino: lb_lvm
-- Fecha  : 2026-08-19 17:38:48
--
-- ADITIVO: no elimina tablas/columnas ni modifica tipos existentes.
-- Ejecutar sobre la BD destino (USE `lb_lvm`).
-- Después: php artisan lb:switch <slug> && php artisan lb:migrate-legacy --force

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------------
-- Tablas faltantes
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `permisos_ia` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `orden` int(10) unsigned NOT NULL,
  `tema` varchar(80) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permisos_ia_orden_unique` (`orden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

-- ---------------------------------------------------------------------------
-- Columnas faltantes
-- ---------------------------------------------------------------------------

SET @silavet_sql := (
    SELECT IF(
        NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'clientes'
              AND COLUMN_NAME = 'descuento'
        ),
        'ALTER TABLE `clientes` ADD COLUMN `descuento` decimal(6,2) DEFAULT NULL AFTER `dni`',
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

SET @silavet_sql := (
    SELECT IF(
        NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'clientes'
              AND COLUMN_NAME = 'listaPreciosCliente'
        ),
        'ALTER TABLE `clientes` ADD COLUMN `listaPreciosCliente` tinyint(3) unsigned NOT NULL DEFAULT 1 AFTER `descuento`',
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

SET @silavet_sql := (
    SELECT IF(
        NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'compafip'
              AND COLUMN_NAME = 'idCompAfipAsoc'
        ),
        'ALTER TABLE `compafip` ADD COLUMN `idCompAfipAsoc` int(10) unsigned DEFAULT NULL AFTER `CAEFchVto`',
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

SET @silavet_sql := (
    SELECT IF(
        NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'compafip'
              AND COLUMN_NAME = 'idMovimientos'
        ),
        'ALTER TABLE `compafip` ADD COLUMN `idMovimientos` int(10) unsigned DEFAULT NULL AFTER `idCompAfipAsoc`',
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

SET @silavet_sql := (
    SELECT IF(
        NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'determinaciones'
              AND COLUMN_NAME = 'neto'
        ),
        'ALTER TABLE `determinaciones` ADD COLUMN `neto` decimal(20,2) NOT NULL DEFAULT 0.00 AFTER `idTipodeterminaciones`',
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

SET @silavet_sql := (
    SELECT IF(
        NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'determinaciones'
              AND COLUMN_NAME = 'fechaEnvioDeriv'
        ),
        'ALTER TABLE `determinaciones` ADD COLUMN `fechaEnvioDeriv` date DEFAULT NULL AFTER `idDerivaciones`',
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

SET @silavet_sql := (
    SELECT IF(
        NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'determinaciones'
              AND COLUMN_NAME = 'fechaDevolucDeterm'
        ),
        'ALTER TABLE `determinaciones` ADD COLUMN `fechaDevolucDeterm` date DEFAULT NULL AFTER `fechaEnvioDeriv`',
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

SET @silavet_sql := (
    SELECT IF(
        NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'entorno'
              AND COLUMN_NAME = 'listaPreciosPdf'
        ),
        'ALTER TABLE `entorno` ADD COLUMN `listaPreciosPdf` varchar(255) DEFAULT NULL AFTER `nombreListaPrecio`',
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

SET @silavet_sql := (
    SELECT IF(
        NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'entorno'
              AND COLUMN_NAME = 'afipFormatoImpresion'
        ),
        'ALTER TABLE `entorno` ADD COLUMN `afipFormatoImpresion` varchar(20) NOT NULL DEFAULT \'A4\' AFTER `e_Borde`',
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

SET @silavet_sql := (
    SELECT IF(
        NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'entorno'
              AND COLUMN_NAME = 'headerInforme'
        ),
        'ALTER TABLE `entorno` ADD COLUMN `headerInforme` varchar(255) DEFAULT NULL AFTER `afipFormatoImpresion`',
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

SET @silavet_sql := (
    SELECT IF(
        NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'entorno'
              AND COLUMN_NAME = 'footerInforme'
        ),
        'ALTER TABLE `entorno` ADD COLUMN `footerInforme` varchar(255) DEFAULT NULL AFTER `headerInforme`',
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

SET @silavet_sql := (
    SELECT IF(
        NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'entorno'
              AND COLUMN_NAME = 'colorFondoSistema'
        ),
        'ALTER TABLE `entorno` ADD COLUMN `colorFondoSistema` varchar(20) DEFAULT NULL AFTER `footerInforme`',
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

SET @silavet_sql := (
    SELECT IF(
        NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'pacientes'
              AND COLUMN_NAME = 'listaPreciosPaciente'
        ),
        'ALTER TABLE `pacientes` ADD COLUMN `listaPreciosPaciente` tinyint(3) unsigned NOT NULL DEFAULT 1 AFTER `propietario`',
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

SET @silavet_sql := (
    SELECT IF(
        NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'pacientes'
              AND COLUMN_NAME = 'neto'
        ),
        'ALTER TABLE `pacientes` ADD COLUMN `neto` decimal(20,2) NOT NULL DEFAULT 0.00 AFTER `estado`',
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

SET @silavet_sql := (
    SELECT IF(
        NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'tipodeterminaciones'
              AND COLUMN_NAME = 'precio2'
        ),
        'ALTER TABLE `tipodeterminaciones` ADD COLUMN `precio2` decimal(20,2) NOT NULL DEFAULT 0.00 AFTER `precio`',
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

SET @silavet_sql := (
    SELECT IF(
        NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'tipodeterminaciones'
              AND COLUMN_NAME = 'precio3'
        ),
        'ALTER TABLE `tipodeterminaciones` ADD COLUMN `precio3` decimal(20,2) NOT NULL DEFAULT 0.00 AFTER `precio2`',
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

SET @silavet_sql := (
    SELECT IF(
        NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'usuarios'
              AND COLUMN_NAME = 'permisos_ia'
        ),
        'ALTER TABLE `usuarios` ADD COLUMN `permisos_ia` text DEFAULT NULL AFTER `password`',
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

SET @silavet_sql := (
    SELECT IF(
        NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'usuarios'
              AND COLUMN_NAME = 'crtVencimiento'
        ),
        'ALTER TABLE `usuarios` ADD COLUMN `crtVencimiento` date DEFAULT NULL AFTER `crt`',
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

-- ---------------------------------------------------------------------------
-- Índices faltantes
-- ---------------------------------------------------------------------------

SET @silavet_sql := (
    SELECT IF(
        NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'compafip'
              AND INDEX_NAME = 'compafip_idCompAfipAsoc_index'
        ),
        'ALTER TABLE `compafip` ADD KEY `compafip_idCompAfipAsoc_index` (`idCompAfipAsoc`)',
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

SET @silavet_sql := (
    SELECT IF(
        NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'compafip'
              AND INDEX_NAME = 'compafip_idMovimientos_index'
        ),
        'ALTER TABLE `compafip` ADD KEY `compafip_idMovimientos_index` (`idMovimientos`)',
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

-- ---------------------------------------------------------------------------
-- Catálogos (solo tablas creadas vacías)
-- ---------------------------------------------------------------------------

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
(9, 'Usuarios', 'ABM usuarios y roles'),
(10, 'Listados estadísticos', 'Estimación de costos y listados estadísticos');

-- ---------------------------------------------------------------------------
-- Diferencias de tipo (NO se modifican; revisar a mano si hace falta)
-- ---------------------------------------------------------------------------
-- clientes.email
--   modelo : `email` varchar(500) NOT NULL DEFAULT ''
--   destino: `email` varchar(150) NOT NULL DEFAULT ''
-- entorno.formulas
--   modelo : `formulas` text NOT NULL
--   destino: `formulas` mediumtext NOT NULL
-- entorno.e_AnchoPapel
--   modelo : `e_AnchoPapel` decimal(8,2) DEFAULT 80.00
--   destino: `e_AnchoPapel` float(6,2) DEFAULT NULL
-- entorno.e_AnchoEtiq
--   modelo : `e_AnchoEtiq` decimal(8,2) DEFAULT 35.00
--   destino: `e_AnchoEtiq` float(6,2) DEFAULT NULL
-- entorno.e_AltoEtiq
--   modelo : `e_AltoEtiq` decimal(8,2) DEFAULT 20.00
--   destino: `e_AltoEtiq` float(6,2) DEFAULT NULL
-- entorno.e_CantCol
--   modelo : `e_CantCol` tinyint(3) unsigned DEFAULT 2
--   destino: `e_CantCol` float(6,2) DEFAULT NULL
-- entorno.e_GapX
--   modelo : `e_GapX` decimal(8,2) DEFAULT 2.00
--   destino: `e_GapX` float(6,2) DEFAULT NULL
-- entorno.e_GapY
--   modelo : `e_GapY` decimal(8,2) DEFAULT 2.00
--   destino: `e_GapY` float(6,2) DEFAULT NULL
-- entorno.e_MarginTop
--   modelo : `e_MarginTop` decimal(8,2) DEFAULT 1.00
--   destino: `e_MarginTop` float(6,2) DEFAULT NULL
-- entorno.e_MarginBottom
--   modelo : `e_MarginBottom` decimal(8,2) DEFAULT 0.00
--   destino: `e_MarginBottom` float(6,2) DEFAULT NULL
-- entorno.e_MarginLeft
--   modelo : `e_MarginLeft` decimal(8,2) DEFAULT 2.00
--   destino: `e_MarginLeft` float(6,2) DEFAULT NULL
-- entorno.e_MarginRight
--   modelo : `e_MarginRight` decimal(8,2) DEFAULT 0.00
--   destino: `e_MarginRight` float(6,2) DEFAULT NULL
-- entorno.e_FontLinea1
--   modelo : `e_FontLinea1` tinyint(3) unsigned DEFAULT 18
--   destino: `e_FontLinea1` int(3) DEFAULT NULL
-- entorno.e_FontLinea2
--   modelo : `e_FontLinea2` tinyint(3) unsigned DEFAULT 12
--   destino: `e_FontLinea2` int(3) DEFAULT NULL
-- entorno.e_FontLinea3
--   modelo : `e_FontLinea3` tinyint(3) unsigned DEFAULT 11
--   destino: `e_FontLinea3` int(3) DEFAULT NULL
-- entorno.e_FontLinea4
--   modelo : `e_FontLinea4` tinyint(3) unsigned DEFAULT 8
--   destino: `e_FontLinea4` int(3) DEFAULT NULL
-- entorno.e_MaxLargoLinea2
--   modelo : `e_MaxLargoLinea2` tinyint(3) unsigned DEFAULT 21
--   destino: `e_MaxLargoLinea2` int(3) DEFAULT NULL
-- entorno.e_MaxLargoLinea3
--   modelo : `e_MaxLargoLinea3` tinyint(3) unsigned DEFAULT 25
--   destino: `e_MaxLargoLinea3` int(3) DEFAULT NULL
-- entorno.e_Borde
--   modelo : `e_Borde` tinyint(1) DEFAULT 0
--   destino: `e_Borde` int(1) DEFAULT NULL
-- itemsinforme.textos
--   modelo : `textos` text DEFAULT NULL
--   destino: `textos` mediumtext DEFAULT NULL
-- itemsinforme.idAnalizador
--   modelo : `idAnalizador` varchar(20) DEFAULT NULL
--   destino: `idAnalizador` varchar(50) DEFAULT NULL
-- pacientes.observaciones
--   modelo : `observaciones` text DEFAULT NULL
--   destino: `observaciones` mediumtext DEFAULT NULL
-- pacientes.clinica
--   modelo : `clinica` text DEFAULT NULL
--   destino: `clinica` mediumtext DEFAULT NULL
-- reactivoxdeterminacion.cantidad
--   modelo : `cantidad` decimal(10,4) NOT NULL DEFAULT 0.0000
--   destino: `cantidad` int(11) NOT NULL
-- renglones.valor
--   modelo : `valor` text DEFAULT NULL
--   destino: `valor` mediumtext DEFAULT NULL
-- renglones.idAnalizador
--   modelo : `idAnalizador` varchar(20) DEFAULT NULL
--   destino: `idAnalizador` varchar(50) DEFAULT NULL
-- usuarios.cuit
--   modelo : `cuit` varchar(11) NOT NULL DEFAULT '0'
--   destino: `cuit` varchar(11) CHARACTER SET utf8mb3 COLLATE utf8mb3_spanish_ci NOT NULL DEFAULT '0'
-- usuarios.razonSocial
--   modelo : `razonSocial` varchar(100) NOT NULL DEFAULT '0'
--   destino: `razonSocial` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_spanish_ci NOT NULL DEFAULT '0'
-- usuarios.domicComerc
--   modelo : `domicComerc` varchar(50) NOT NULL DEFAULT '0'
--   destino: `domicComerc` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_spanish_ci NOT NULL DEFAULT '0'
-- usuarios.condIva
--   modelo : `condIva` varchar(30) NOT NULL DEFAULT '0'
--   destino: `condIva` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_spanish_ci NOT NULL DEFAULT '0'
-- usuarios.ingresosBrutos
--   modelo : `ingresosBrutos` varchar(30) NOT NULL DEFAULT '0'
--   destino: `ingresosBrutos` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_spanish_ci NOT NULL DEFAULT '0'
-- usuarios.key
--   modelo : `key` varchar(100) NOT NULL DEFAULT '0'
--   destino: `key` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_spanish_ci NOT NULL DEFAULT '0'
-- usuarios.crt
--   modelo : `crt` varchar(100) NOT NULL DEFAULT '0'
--   destino: `crt` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_spanish_ci NOT NULL DEFAULT '0'

-- ---------------------------------------------------------------------------
-- Solo en destino (no se eliminan)
-- ---------------------------------------------------------------------------
-- columna extra: itemsinforme.mostrar
-- columna extra: pacientes.obsPagos

SET FOREIGN_KEY_CHECKS = 1;
