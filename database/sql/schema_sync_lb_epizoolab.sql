-- SILAVET — sincronización aditiva de esquema
-- Generado por: php artisan lb:schema-sync
-- Modelo : lb_neolab
-- Destino: lb_epizoolab
-- Fecha  : 2026-08-15 15:18:50
--
-- ADITIVO: no elimina tablas/columnas ni modifica tipos existentes.
-- Ejecutar sobre la BD destino (USE `lb_epizoolab`).
-- Después: php artisan lb:switch <slug> && php artisan lb:migrate-legacy --force

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------------
-- Tablas faltantes
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `compafip` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idPacientes` varchar(100) NOT NULL DEFAULT '0',
  `cuit` varchar(11) NOT NULL DEFAULT '0',
  `PtoVta` int(2) NOT NULL DEFAULT 0,
  `CbteTipo` int(3) NOT NULL DEFAULT 0,
  `Concepto` int(2) NOT NULL DEFAULT 0,
  `DocTipo` int(2) NOT NULL DEFAULT 0,
  `DocNro` varchar(11) NOT NULL DEFAULT '0',
  `razonSocial` varchar(100) NOT NULL DEFAULT '0',
  `domicComerc` varchar(50) NOT NULL DEFAULT '0',
  `razonSocialCliente` varchar(100) NOT NULL DEFAULT '0',
  `importe` float(15,2) NOT NULL DEFAULT 0.00,
  `FechServDesde` date DEFAULT NULL,
  `FechServHasta` date DEFAULT NULL,
  `fechaComprobante` date DEFAULT NULL,
  `CbteHasta` int(10) DEFAULT NULL,
  `CondicionIVAReceptorId` int(2) NOT NULL DEFAULT 0,
  `conceptoFacturado` varchar(200) NOT NULL DEFAULT '0',
  `CAE` varchar(30) NOT NULL DEFAULT '0',
  `CAEFchVto` date DEFAULT NULL,
  `idCompAfipAsoc` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idCuotasPagos` (`idPacientes`) USING BTREE,
  KEY `compafip_idCompAfipAsoc_index` (`idCompAfipAsoc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

CREATE TABLE IF NOT EXISTS `imagenesxrenglon` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idRenglones` int(11) DEFAULT NULL,
  `nombreImagen` varchar(50) DEFAULT NULL,
  `observacion` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE IF NOT EXISTS `notificaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fechaCreacion` datetime DEFAULT NULL,
  `idClientes` int(11) NOT NULL,
  `idPacientes` int(11) NOT NULL,
  `notificacion` varchar(255) NOT NULL DEFAULT '',
  `leido` int(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fechaCreacion` (`fechaCreacion`),
  KEY `idClientes` (`idClientes`),
  KEY `idPacientes` (`idPacientes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `permisos_ia` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `orden` int(10) unsigned NOT NULL,
  `tema` varchar(80) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permisos_ia_orden_unique` (`orden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

CREATE TABLE IF NOT EXISTS `requerimientos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(30) NOT NULL DEFAULT '',
  `requerimiento` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `reqxtipodet` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idRequerimientos` int(11) NOT NULL,
  `idTipodeterminaciones` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idRequerimientos` (`idRequerimientos`),
  KEY `idTipodeterminaciones` (`idTipodeterminaciones`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------------
-- Columnas faltantes
-- ---------------------------------------------------------------------------

SET @silavet_sql := (
    SELECT IF(
        NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'clientes'
              AND COLUMN_NAME = 'dni'
        ),
        'ALTER TABLE `clientes` ADD COLUMN `dni` varchar(8) NOT NULL DEFAULT \'\' AFTER `cuit`',
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
              AND COLUMN_NAME = 'logo'
        ),
        'ALTER TABLE `entorno` ADD COLUMN `logo` varchar(60) DEFAULT NULL AFTER `carpeta`',
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
              AND COLUMN_NAME = 'fondo'
        ),
        'ALTER TABLE `entorno` ADD COLUMN `fondo` varchar(60) DEFAULT NULL AFTER `logo`',
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
              AND COLUMN_NAME = 'direLabo'
        ),
        'ALTER TABLE `entorno` ADD COLUMN `direLabo` varchar(100) DEFAULT NULL AFTER `fondo`',
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
              AND COLUMN_NAME = 'teleLabo'
        ),
        'ALTER TABLE `entorno` ADD COLUMN `teleLabo` varchar(100) DEFAULT NULL AFTER `direLabo`',
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
              AND COLUMN_NAME = 'emailLabo'
        ),
        'ALTER TABLE `entorno` ADD COLUMN `emailLabo` varchar(100) DEFAULT NULL AFTER `teleLabo`',
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
              AND COLUMN_NAME = 'colorInforme'
        ),
        'ALTER TABLE `entorno` ADD COLUMN `colorInforme` varchar(20) DEFAULT NULL AFTER `emailLabo`',
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
              AND COLUMN_NAME = 'texto1footerIzq'
        ),
        'ALTER TABLE `entorno` ADD COLUMN `texto1footerIzq` varchar(60) DEFAULT NULL AFTER `colorInforme`',
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
              AND COLUMN_NAME = 'texto2footerIzq'
        ),
        'ALTER TABLE `entorno` ADD COLUMN `texto2footerIzq` varchar(60) DEFAULT NULL AFTER `texto1footerIzq`',
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
              AND COLUMN_NAME = 'texto1footerCentro'
        ),
        'ALTER TABLE `entorno` ADD COLUMN `texto1footerCentro` varchar(60) DEFAULT NULL AFTER `texto2footerIzq`',
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
              AND COLUMN_NAME = 'texto2footerCentro'
        ),
        'ALTER TABLE `entorno` ADD COLUMN `texto2footerCentro` varchar(60) DEFAULT NULL AFTER `texto1footerCentro`',
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
              AND COLUMN_NAME = 'texto1footerDer'
        ),
        'ALTER TABLE `entorno` ADD COLUMN `texto1footerDer` varchar(60) DEFAULT NULL AFTER `texto2footerCentro`',
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
              AND COLUMN_NAME = 'texto2footerDer'
        ),
        'ALTER TABLE `entorno` ADD COLUMN `texto2footerDer` varchar(60) DEFAULT NULL AFTER `texto1footerDer`',
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
              AND COLUMN_NAME = 'firmaIzq'
        ),
        'ALTER TABLE `entorno` ADD COLUMN `firmaIzq` varchar(60) DEFAULT NULL AFTER `texto2footerDer`',
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
              AND COLUMN_NAME = 'firmaCentro'
        ),
        'ALTER TABLE `entorno` ADD COLUMN `firmaCentro` varchar(60) DEFAULT NULL AFTER `firmaIzq`',
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
              AND COLUMN_NAME = 'firmaDer'
        ),
        'ALTER TABLE `entorno` ADD COLUMN `firmaDer` varchar(60) DEFAULT NULL AFTER `firmaCentro`',
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
              AND COLUMN_NAME = 'ctaEnvioMail'
        ),
        'ALTER TABLE `entorno` ADD COLUMN `ctaEnvioMail` varchar(100) DEFAULT NULL AFTER `firmaDer`',
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
              AND COLUMN_NAME = 'passEnvioMail'
        ),
        'ALTER TABLE `entorno` ADD COLUMN `passEnvioMail` varchar(20) DEFAULT NULL AFTER `ctaEnvioMail`',
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
              AND COLUMN_NAME = 'fromMail'
        ),
        'ALTER TABLE `entorno` ADD COLUMN `fromMail` varchar(50) DEFAULT NULL AFTER `passEnvioMail`',
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
              AND COLUMN_NAME = 'nombrePieMail'
        ),
        'ALTER TABLE `entorno` ADD COLUMN `nombrePieMail` varchar(100) DEFAULT NULL AFTER `fromMail`',
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
              AND COLUMN_NAME = 'direccionPieMail'
        ),
        'ALTER TABLE `entorno` ADD COLUMN `direccionPieMail` varchar(100) DEFAULT NULL AFTER `nombrePieMail`',
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
              AND COLUMN_NAME = 'telefonoPieMail'
        ),
        'ALTER TABLE `entorno` ADD COLUMN `telefonoPieMail` varchar(100) DEFAULT NULL AFTER `direccionPieMail`',
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
              AND COLUMN_NAME = 'emailPieMail'
        ),
        'ALTER TABLE `entorno` ADD COLUMN `emailPieMail` varchar(100) DEFAULT NULL AFTER `telefonoPieMail`',
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
              AND COLUMN_NAME = 'e_AnchoPapel'
        ),
        'ALTER TABLE `entorno` ADD COLUMN `e_AnchoPapel` decimal(8,2) DEFAULT 80.00 AFTER `emailPieMail`',
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
              AND COLUMN_NAME = 'e_AnchoEtiq'
        ),
        'ALTER TABLE `entorno` ADD COLUMN `e_AnchoEtiq` decimal(8,2) DEFAULT 35.00 AFTER `e_AnchoPapel`',
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
              AND COLUMN_NAME = 'e_AltoEtiq'
        ),
        'ALTER TABLE `entorno` ADD COLUMN `e_AltoEtiq` decimal(8,2) DEFAULT 20.00 AFTER `e_AnchoEtiq`',
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
              AND COLUMN_NAME = 'e_CantCol'
        ),
        'ALTER TABLE `entorno` ADD COLUMN `e_CantCol` tinyint(3) unsigned DEFAULT 2 AFTER `e_AltoEtiq`',
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
              AND COLUMN_NAME = 'e_GapX'
        ),
        'ALTER TABLE `entorno` ADD COLUMN `e_GapX` decimal(8,2) DEFAULT 2.00 AFTER `e_CantCol`',
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
              AND COLUMN_NAME = 'e_GapY'
        ),
        'ALTER TABLE `entorno` ADD COLUMN `e_GapY` decimal(8,2) DEFAULT 2.00 AFTER `e_GapX`',
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
              AND COLUMN_NAME = 'e_MarginTop'
        ),
        'ALTER TABLE `entorno` ADD COLUMN `e_MarginTop` decimal(8,2) DEFAULT 1.00 AFTER `e_GapY`',
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
              AND COLUMN_NAME = 'e_MarginBottom'
        ),
        'ALTER TABLE `entorno` ADD COLUMN `e_MarginBottom` decimal(8,2) DEFAULT 0.00 AFTER `e_MarginTop`',
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
              AND COLUMN_NAME = 'e_MarginLeft'
        ),
        'ALTER TABLE `entorno` ADD COLUMN `e_MarginLeft` decimal(8,2) DEFAULT 2.00 AFTER `e_MarginBottom`',
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
              AND COLUMN_NAME = 'e_MarginRight'
        ),
        'ALTER TABLE `entorno` ADD COLUMN `e_MarginRight` decimal(8,2) DEFAULT 0.00 AFTER `e_MarginLeft`',
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
              AND COLUMN_NAME = 'e_FontLinea1'
        ),
        'ALTER TABLE `entorno` ADD COLUMN `e_FontLinea1` tinyint(3) unsigned DEFAULT 18 AFTER `e_MarginRight`',
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
              AND COLUMN_NAME = 'e_FontLinea2'
        ),
        'ALTER TABLE `entorno` ADD COLUMN `e_FontLinea2` tinyint(3) unsigned DEFAULT 12 AFTER `e_FontLinea1`',
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
              AND COLUMN_NAME = 'e_FontLinea3'
        ),
        'ALTER TABLE `entorno` ADD COLUMN `e_FontLinea3` tinyint(3) unsigned DEFAULT 11 AFTER `e_FontLinea2`',
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
              AND COLUMN_NAME = 'e_FontLinea4'
        ),
        'ALTER TABLE `entorno` ADD COLUMN `e_FontLinea4` tinyint(3) unsigned DEFAULT 8 AFTER `e_FontLinea3`',
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
              AND COLUMN_NAME = 'e_MaxLargoLinea2'
        ),
        'ALTER TABLE `entorno` ADD COLUMN `e_MaxLargoLinea2` tinyint(3) unsigned DEFAULT 21 AFTER `e_FontLinea4`',
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
              AND COLUMN_NAME = 'e_MaxLargoLinea3'
        ),
        'ALTER TABLE `entorno` ADD COLUMN `e_MaxLargoLinea3` tinyint(3) unsigned DEFAULT 25 AFTER `e_MaxLargoLinea2`',
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
              AND COLUMN_NAME = 'e_Borde'
        ),
        'ALTER TABLE `entorno` ADD COLUMN `e_Borde` tinyint(1) DEFAULT 0 AFTER `e_MaxLargoLinea3`',
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
              AND TABLE_NAME = 'pacientes'
              AND COLUMN_NAME = 'idCuentasdetalle'
        ),
        'ALTER TABLE `pacientes` ADD COLUMN `idCuentasdetalle` int(11) NOT NULL DEFAULT 0 AFTER `idRazas`',
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
              AND COLUMN_NAME = 'dni'
        ),
        'ALTER TABLE `pacientes` ADD COLUMN `dni` varchar(8) NOT NULL DEFAULT \'\' AFTER `propietario`',
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
              AND COLUMN_NAME = 'cuit'
        ),
        'ALTER TABLE `pacientes` ADD COLUMN `cuit` varchar(11) NOT NULL DEFAULT \'\' AFTER `dni`',
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
              AND COLUMN_NAME = 'obsInterna'
        ),
        'ALTER TABLE `pacientes` ADD COLUMN `obsInterna` text DEFAULT NULL AFTER `observaciones`',
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
              AND TABLE_NAME = 'renglones'
              AND COLUMN_NAME = 'duplic'
        ),
        'ALTER TABLE `renglones` ADD COLUMN `duplic` int(1) DEFAULT NULL AFTER `mostrar`',
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
              AND TABLE_NAME = 'tipodeterminaciones'
              AND COLUMN_NAME = 'derivacion'
        ),
        'ALTER TABLE `tipodeterminaciones` ADD COLUMN `derivacion` int(1) NOT NULL DEFAULT 0 AFTER `perfil`',
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
              AND COLUMN_NAME = 'permisoAfip'
        ),
        'ALTER TABLE `usuarios` ADD COLUMN `permisoAfip` int(1) NOT NULL DEFAULT 0 AFTER `permisos_ia`',
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
              AND COLUMN_NAME = 'cuit'
        ),
        'ALTER TABLE `usuarios` ADD COLUMN `cuit` varchar(11) NOT NULL DEFAULT \'0\' AFTER `permisoAfip`',
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
              AND COLUMN_NAME = 'razonSocial'
        ),
        'ALTER TABLE `usuarios` ADD COLUMN `razonSocial` varchar(100) NOT NULL DEFAULT \'0\' AFTER `cuit`',
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
              AND COLUMN_NAME = 'domicComerc'
        ),
        'ALTER TABLE `usuarios` ADD COLUMN `domicComerc` varchar(50) NOT NULL DEFAULT \'0\' AFTER `razonSocial`',
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
              AND COLUMN_NAME = 'condIva'
        ),
        'ALTER TABLE `usuarios` ADD COLUMN `condIva` varchar(30) NOT NULL DEFAULT \'0\' AFTER `domicComerc`',
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
              AND COLUMN_NAME = 'ingresosBrutos'
        ),
        'ALTER TABLE `usuarios` ADD COLUMN `ingresosBrutos` varchar(30) NOT NULL DEFAULT \'0\' AFTER `condIva`',
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
              AND COLUMN_NAME = 'inicioActiv'
        ),
        'ALTER TABLE `usuarios` ADD COLUMN `inicioActiv` date DEFAULT NULL AFTER `ingresosBrutos`',
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
              AND COLUMN_NAME = 'PtoVta'
        ),
        'ALTER TABLE `usuarios` ADD COLUMN `PtoVta` int(2) NOT NULL DEFAULT 0 AFTER `inicioActiv`',
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
              AND COLUMN_NAME = 'CbteTipo'
        ),
        'ALTER TABLE `usuarios` ADD COLUMN `CbteTipo` int(2) NOT NULL DEFAULT 0 AFTER `PtoVta`',
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
              AND COLUMN_NAME = 'NtaCredTipo'
        ),
        'ALTER TABLE `usuarios` ADD COLUMN `NtaCredTipo` int(2) NOT NULL DEFAULT 0 AFTER `CbteTipo`',
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
              AND COLUMN_NAME = 'Concepto'
        ),
        'ALTER TABLE `usuarios` ADD COLUMN `Concepto` int(2) NOT NULL DEFAULT 0 AFTER `NtaCredTipo`',
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
              AND COLUMN_NAME = 'DocTipo'
        ),
        'ALTER TABLE `usuarios` ADD COLUMN `DocTipo` int(2) NOT NULL DEFAULT 0 AFTER `Concepto`',
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
              AND COLUMN_NAME = 'CondicionIVAReceptorId'
        ),
        'ALTER TABLE `usuarios` ADD COLUMN `CondicionIVAReceptorId` int(2) NOT NULL DEFAULT 0 AFTER `DocTipo`',
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
              AND COLUMN_NAME = 'key'
        ),
        'ALTER TABLE `usuarios` ADD COLUMN `key` varchar(100) NOT NULL DEFAULT \'0\' AFTER `CondicionIVAReceptorId`',
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
              AND COLUMN_NAME = 'crt'
        ),
        'ALTER TABLE `usuarios` ADD COLUMN `crt` varchar(100) NOT NULL DEFAULT \'0\' AFTER `key`',
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
              AND TABLE_NAME = 'pacientes'
              AND INDEX_NAME = 'idCuentasdetalle'
        ),
        'ALTER TABLE `pacientes` ADD KEY `idCuentasdetalle` (`idCuentasdetalle`)',
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
              AND TABLE_NAME = 'pacientes'
              AND INDEX_NAME = 'tipoRegistro'
        ),
        'ALTER TABLE `pacientes` ADD KEY `tipoRegistro` (`tipoRegistro`)',
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
-- clientes.whatsapp
--   modelo : `whatsapp` varchar(20) NOT NULL DEFAULT ''
--   destino: `whatsapp` varchar(20) DEFAULT NULL
-- clientes.descuento
--   modelo : `descuento` decimal(6,2) DEFAULT NULL
--   destino: `descuento` float(6,2) DEFAULT NULL
-- entorno.nombreListaPrecio
--   modelo : `nombreListaPrecio` varchar(200) DEFAULT NULL
--   destino: `nombreListaPrecio` varchar(200) NOT NULL DEFAULT ''
-- itemsinforme.nombreItem
--   modelo : `nombreItem` varchar(200) NOT NULL DEFAULT ''
--   destino: `nombreItem` varchar(500) NOT NULL DEFAULT ''
-- itemsinforme.idAnalizador
--   modelo : `idAnalizador` varchar(20) DEFAULT NULL
--   destino: `idAnalizador` varchar(40) DEFAULT NULL
-- pacientes.tipoRegistro
--   modelo : `tipoRegistro` int(1) NOT NULL
--   destino: `tipoRegistro` int(1) NOT NULL DEFAULT 0
-- pacientes.fechhoy
--   modelo : `fechhoy` datetime NOT NULL
--   destino: `fechhoy` date NOT NULL
-- renglones.idAnalizador
--   modelo : `idAnalizador` varchar(20) DEFAULT NULL
--   destino: `idAnalizador` varchar(40) DEFAULT NULL

-- ---------------------------------------------------------------------------
-- Solo en destino (no se eliminan)
-- ---------------------------------------------------------------------------
-- tabla extra: conceptos
-- tabla extra: movimientos
-- tabla extra: proveedores
-- tabla extra: tipomovimiento
-- columna extra: clientes.tipoCliente
-- columna extra: entorno.ano
-- columna extra: mediodepago.abreviatura
-- columna extra: mediodepago.orden
-- columna extra: pacientes.idCuentasDetalle
-- columna extra: pacientes.cadete
-- columna extra: pacientes.obsPriv

SET FOREIGN_KEY_CHECKS = 1;
