-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Versión del servidor:         5.7.28-log - MySQL Community Server (GPL)
-- SO del servidor:              Win64
-- HeidiSQL Versión:             12.6.0.6765
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Volcando estructura para tabla lb_neolab.clientes
DROP TABLE IF EXISTS `clientes`;
CREATE TABLE IF NOT EXISTS `clientes` (
  `idClientes` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(200) COLLATE utf8_spanish_ci NOT NULL DEFAULT '',
  `direccion` varchar(200) COLLATE utf8_spanish_ci NOT NULL DEFAULT '',
  `telefono1` varchar(50) COLLATE utf8_spanish_ci NOT NULL DEFAULT '',
  `telefono2` varchar(50) COLLATE utf8_spanish_ci NOT NULL DEFAULT '',
  `email` varchar(150) COLLATE utf8_spanish_ci NOT NULL DEFAULT '',
  `whatsapp` varchar(20) COLLATE utf8_spanish_ci NOT NULL DEFAULT '',
  `cuit` varchar(11) COLLATE utf8_spanish_ci DEFAULT NULL,
  PRIMARY KEY (`idClientes`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=208 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla lb_neolab.compafip
DROP TABLE IF EXISTS `compafip`;
CREATE TABLE IF NOT EXISTS `compafip` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idPacientes` varchar(100) COLLATE utf8_spanish_ci NOT NULL DEFAULT '0',
  `cuit` varchar(11) COLLATE utf8_spanish_ci NOT NULL DEFAULT '0',
  `PtoVta` int(2) NOT NULL DEFAULT '0',
  `CbteTipo` int(3) NOT NULL DEFAULT '0',
  `Concepto` int(2) NOT NULL DEFAULT '0',
  `DocTipo` int(2) NOT NULL DEFAULT '0',
  `DocNro` varchar(11) COLLATE utf8_spanish_ci NOT NULL DEFAULT '0',
  `razonSocial` varchar(100) COLLATE utf8_spanish_ci NOT NULL DEFAULT '0',
  `domicComerc` varchar(50) COLLATE utf8_spanish_ci NOT NULL DEFAULT '0',
  `razonSocialCliente` varchar(100) COLLATE utf8_spanish_ci NOT NULL DEFAULT '0',
  `importe` float(15,2) NOT NULL DEFAULT '0.00',
  `FechServDesde` date DEFAULT NULL,
  `FechServHasta` date DEFAULT NULL,
  `fechaComprobante` date DEFAULT NULL,
  `CbteHasta` int(10) DEFAULT NULL,
  `CondicionIVAReceptorId` int(2) NOT NULL DEFAULT '0',
  `conceptoFacturado` varchar(200) COLLATE utf8_spanish_ci NOT NULL DEFAULT '0',
  `CAE` varchar(30) COLLATE utf8_spanish_ci NOT NULL DEFAULT '0',
  `CAEFchVto` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idCuotasPagos` (`idPacientes`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=904 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla lb_neolab.cuentas
DROP TABLE IF EXISTS `cuentas`;
CREATE TABLE IF NOT EXISTS `cuentas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombreCuenta` varchar(80) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla lb_neolab.cuentasdetalle
DROP TABLE IF EXISTS `cuentasdetalle`;
CREATE TABLE IF NOT EXISTS `cuentasdetalle` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idCuentas` int(11) DEFAULT NULL,
  `nombreCuentasDetalle` varchar(80) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_cuentasdetalle_cuentas` (`idCuentas`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla lb_neolab.derivaciones
DROP TABLE IF EXISTS `derivaciones`;
CREATE TABLE IF NOT EXISTS `derivaciones` (
  `idDerivaciones` int(11) NOT NULL AUTO_INCREMENT,
  `derivacion` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`idDerivaciones`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla lb_neolab.determinaciones
DROP TABLE IF EXISTS `determinaciones`;
CREATE TABLE IF NOT EXISTS `determinaciones` (
  `idDeterminaciones` int(11) NOT NULL AUTO_INCREMENT,
  `idClientes` int(11) NOT NULL,
  `idPacientes` int(11) NOT NULL,
  `idTipodeterminaciones` int(11) NOT NULL,
  `precio` decimal(20,2) NOT NULL DEFAULT '0.00',
  `descuento` decimal(20,2) NOT NULL DEFAULT '0.00',
  `idDerivaciones` int(11) NOT NULL,
  PRIMARY KEY (`idDeterminaciones`),
  KEY `FK_determinaciones_pacientes` (`idPacientes`),
  KEY `FK_determinaciones_tipodeterminaciones` (`idTipodeterminaciones`),
  KEY `idClientes` (`idClientes`),
  KEY `idDerivaciones` (`idDerivaciones`),
  CONSTRAINT `FK_determinaciones_pacientes` FOREIGN KEY (`idPacientes`) REFERENCES `pacientes` (`idPacientes`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `FK_determinaciones_tipodeterminaciones` FOREIGN KEY (`idTipodeterminaciones`) REFERENCES `tipodeterminaciones` (`idTipodeterminaciones`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=4611 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla lb_neolab.entorno
DROP TABLE IF EXISTS `entorno`;
CREATE TABLE IF NOT EXISTS `entorno` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `formulas` text COLLATE utf8_spanish_ci NOT NULL,
  `nombreListaPrecio` varchar(200) COLLATE utf8_spanish_ci DEFAULT NULL,
  `carpeta` varchar(30) COLLATE utf8_spanish_ci DEFAULT NULL,
  `logo` varchar(60) COLLATE utf8_spanish_ci DEFAULT NULL,
  `fondo` varchar(60) COLLATE utf8_spanish_ci DEFAULT NULL,
  `direLabo` varchar(100) COLLATE utf8_spanish_ci DEFAULT NULL,
  `teleLabo` varchar(100) COLLATE utf8_spanish_ci DEFAULT NULL,
  `emailLabo` varchar(100) COLLATE utf8_spanish_ci DEFAULT NULL,
  `colorInforme` varchar(20) COLLATE utf8_spanish_ci DEFAULT NULL,
  `texto1footerIzq` varchar(60) COLLATE utf8_spanish_ci DEFAULT NULL,
  `texto2footerIzq` varchar(60) COLLATE utf8_spanish_ci DEFAULT NULL,
  `texto1footerCentro` varchar(60) COLLATE utf8_spanish_ci DEFAULT NULL,
  `texto2footerCentro` varchar(60) COLLATE utf8_spanish_ci DEFAULT NULL,
  `texto1footerDer` varchar(60) COLLATE utf8_spanish_ci DEFAULT NULL,
  `texto2footerDer` varchar(60) COLLATE utf8_spanish_ci DEFAULT NULL,
  `firmaIzq` varchar(60) COLLATE utf8_spanish_ci DEFAULT NULL,
  `firmaCentro` varchar(60) COLLATE utf8_spanish_ci DEFAULT NULL,
  `firmaDer` varchar(60) COLLATE utf8_spanish_ci DEFAULT NULL,
  `ctaEnvioMail` varchar(100) COLLATE utf8_spanish_ci DEFAULT NULL,
  `passEnvioMail` varchar(20) COLLATE utf8_spanish_ci DEFAULT NULL,
  `fromMail` varchar(50) COLLATE utf8_spanish_ci DEFAULT NULL,
  `nombrePieMail` varchar(100) COLLATE utf8_spanish_ci DEFAULT NULL,
  `direccionPieMail` varchar(100) COLLATE utf8_spanish_ci DEFAULT NULL,
  `telefonoPieMail` varchar(100) COLLATE utf8_spanish_ci DEFAULT NULL,
  `emailPieMail` varchar(100) COLLATE utf8_spanish_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla lb_neolab.equipos
DROP TABLE IF EXISTS `equipos`;
CREATE TABLE IF NOT EXISTS `equipos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombreEquipo` varchar(20) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla lb_neolab.especies
DROP TABLE IF EXISTS `especies`;
CREATE TABLE IF NOT EXISTS `especies` (
  `idEspecies` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) COLLATE utf8_spanish_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`idEspecies`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla lb_neolab.estimacioncostos
DROP TABLE IF EXISTS `estimacioncostos`;
CREATE TABLE IF NOT EXISTS `estimacioncostos` (
  `idEstimacioncostos` int(11) NOT NULL AUTO_INCREMENT,
  `idClientes` int(11) NOT NULL,
  `idTipodeterminaciones` int(11) NOT NULL,
  `precio` decimal(20,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`idEstimacioncostos`) USING BTREE,
  KEY `idClientes` (`idClientes`),
  KEY `idTipodeterminaciones` (`idTipodeterminaciones`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla lb_neolab.grupos
DROP TABLE IF EXISTS `grupos`;
CREATE TABLE IF NOT EXISTS `grupos` (
  `idGrupos` int(11) NOT NULL AUTO_INCREMENT,
  `nombreGrupo` varchar(50) COLLATE utf8_spanish_ci NOT NULL DEFAULT '',
  `orden` int(4) DEFAULT NULL,
  PRIMARY KEY (`idGrupos`),
  KEY `orden` (`orden`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla lb_neolab.imagenesxrenglon
DROP TABLE IF EXISTS `imagenesxrenglon`;
CREATE TABLE IF NOT EXISTS `imagenesxrenglon` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idRenglones` int(11) DEFAULT NULL,
  `nombreImagen` varchar(50) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `observacion` text COLLATE utf8mb4_spanish_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6767 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla lb_neolab.itemsinforme
DROP TABLE IF EXISTS `itemsinforme`;
CREATE TABLE IF NOT EXISTS `itemsinforme` (
  `idItems` int(11) NOT NULL AUTO_INCREMENT,
  `idGrupos` int(11) DEFAULT NULL,
  `nombreItem` varchar(200) COLLATE utf8_spanish_ci NOT NULL DEFAULT '',
  `tipoItem` int(2) DEFAULT NULL,
  `estiloNum` int(2) DEFAULT '0',
  `textos` text COLLATE utf8_spanish_ci,
  `letra` int(2) DEFAULT '7',
  `negrita` int(1) DEFAULT '0',
  `unidadMedida` varchar(10) COLLATE utf8_spanish_ci DEFAULT NULL,
  `unidadMedida2` varchar(10) COLLATE utf8_spanish_ci DEFAULT NULL,
  `refCaninos` varchar(200) COLLATE utf8_spanish_ci DEFAULT NULL,
  `refFelinos` varchar(200) COLLATE utf8_spanish_ci DEFAULT NULL,
  `refEquinos` varchar(200) COLLATE utf8_spanish_ci DEFAULT NULL,
  `refBovinos` varchar(200) COLLATE utf8_spanish_ci DEFAULT NULL,
  `refPorcinos` varchar(200) COLLATE utf8_spanish_ci DEFAULT NULL,
  `refOvinos` varchar(200) COLLATE utf8_spanish_ci DEFAULT NULL,
  `refComun` varchar(200) COLLATE utf8_spanish_ci DEFAULT NULL,
  `actualiza` int(1) DEFAULT '0',
  `idAnalizador` varchar(20) COLLATE utf8_spanish_ci DEFAULT NULL,
  PRIMARY KEY (`idItems`),
  KEY `idGrupos` (`idGrupos`),
  KEY `tipoItem` (`tipoItem`),
  KEY `idAnalizador` (`idAnalizador`)
) ENGINE=InnoDB AUTO_INCREMENT=344 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla lb_neolab.mediodepago
DROP TABLE IF EXISTS `mediodepago`;
CREATE TABLE IF NOT EXISTS `mediodepago` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombreMedioPago` varchar(50) COLLATE utf8_spanish_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla lb_neolab.notificaciones
DROP TABLE IF EXISTS `notificaciones`;
CREATE TABLE IF NOT EXISTS `notificaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fechaCreacion` datetime DEFAULT NULL,
  `idClientes` int(11) NOT NULL,
  `idPacientes` int(11) NOT NULL,
  `notificacion` varchar(255) NOT NULL DEFAULT '',
  `leido` int(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fechaCreacion` (`fechaCreacion`),
  KEY `idClientes` (`idClientes`),
  KEY `idPacientes` (`idPacientes`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla lb_neolab.pacientes
DROP TABLE IF EXISTS `pacientes`;
CREATE TABLE IF NOT EXISTS `pacientes` (
  `idPacientes` int(11) NOT NULL AUTO_INCREMENT,
  `idClientes` int(11) NOT NULL,
  `idUsuarios` int(11) NOT NULL DEFAULT '0',
  `idEspecies` int(11) NOT NULL DEFAULT '0',
  `idRazas` int(11) NOT NULL DEFAULT '0',
  `idCuentasdetalle` int(11) NOT NULL DEFAULT '0',
  `tipoRegistro` int(1) NOT NULL,
  `fechhoy` datetime NOT NULL,
  `nombreProtocolo` varchar(50) COLLATE utf8_spanish_ci NOT NULL DEFAULT '',
  `nombre` varchar(50) COLLATE utf8_spanish_ci NOT NULL DEFAULT '',
  `propietario` varchar(100) COLLATE utf8_spanish_ci NOT NULL DEFAULT '',
  `cuit` varchar(11) COLLATE utf8_spanish_ci NOT NULL DEFAULT '0',
  `email` varchar(150) COLLATE utf8_spanish_ci NOT NULL DEFAULT '',
  `whatsapp` varchar(20) COLLATE utf8_spanish_ci NOT NULL DEFAULT '',
  `sexo` varchar(100) COLLATE utf8_spanish_ci NOT NULL DEFAULT '',
  `fechnaci` varchar(100) COLLATE utf8_spanish_ci NOT NULL DEFAULT '',
  `edad` varchar(50) COLLATE utf8_spanish_ci NOT NULL DEFAULT '',
  `estado` varchar(10) COLLATE utf8_spanish_ci NOT NULL DEFAULT '',
  `precio` decimal(20,2) NOT NULL DEFAULT '0.00',
  `pagado` decimal(20,2) NOT NULL DEFAULT '0.00',
  `descuento` decimal(20,2) NOT NULL DEFAULT '0.00',
  `saldo` decimal(20,2) NOT NULL DEFAULT '0.00',
  `idMediodepago` int(11) NOT NULL DEFAULT '0',
  `urlExcel` varchar(500) COLLATE utf8_spanish_ci NOT NULL DEFAULT '0',
  `urlPdf` varchar(100) COLLATE utf8_spanish_ci NOT NULL DEFAULT '',
  `adjunto` varchar(100) COLLATE utf8_spanish_ci DEFAULT NULL,
  `observaciones` text COLLATE utf8_spanish_ci,
  `fechaEnvioDeriv` date DEFAULT '0000-00-00',
  `clinica` text COLLATE utf8_spanish_ci,
  PRIMARY KEY (`idPacientes`),
  KEY `FK_pacientes_clientes` (`idClientes`),
  KEY `idEspecies` (`idEspecies`),
  KEY `idRazas` (`idRazas`),
  KEY `idUsuarios` (`idUsuarios`),
  KEY `idCuentasdetalle` (`idCuentasdetalle`),
  KEY `tipoRegistro` (`tipoRegistro`),
  KEY `fechhoy` (`fechhoy`),
  KEY `nombreProtocolo` (`nombreProtocolo`),
  KEY `idMediodepago` (`idMediodepago`),
  KEY `urlExcel` (`urlExcel`),
  KEY `fechaEnvioDeriv` (`fechaEnvioDeriv`),
  CONSTRAINT `FK_pacientes_clientes` FOREIGN KEY (`idClientes`) REFERENCES `clientes` (`idClientes`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=2238 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla lb_neolab.rangovalores
DROP TABLE IF EXISTS `rangovalores`;
CREATE TABLE IF NOT EXISTS `rangovalores` (
  `idRangovalores` int(11) NOT NULL AUTO_INCREMENT,
  `idItems` int(11) NOT NULL,
  `especie` varchar(50) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `sexo` varchar(10) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `valorMin` decimal(10,2) DEFAULT NULL,
  `valorMax` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`idRangovalores`) USING BTREE,
  KEY `fk_items_valores` (`idItems`),
  CONSTRAINT `fk_items_valores` FOREIGN KEY (`idItems`) REFERENCES `itemsinforme` (`idItems`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla lb_neolab.razas
DROP TABLE IF EXISTS `razas`;
CREATE TABLE IF NOT EXISTS `razas` (
  `idRazas` int(11) NOT NULL AUTO_INCREMENT,
  `idEspecies` int(11) NOT NULL,
  `nombre` varchar(150) COLLATE utf8_spanish_ci DEFAULT NULL,
  PRIMARY KEY (`idRazas`),
  KEY `FK_razas_especies` (`idEspecies`),
  CONSTRAINT `FK_razas_especies` FOREIGN KEY (`idEspecies`) REFERENCES `especies` (`idEspecies`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=95 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla lb_neolab.reactivos
DROP TABLE IF EXISTS `reactivos`;
CREATE TABLE IF NOT EXISTS `reactivos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reactivo` varchar(50) COLLATE utf8_spanish_ci NOT NULL,
  `cantidad` int(5) NOT NULL DEFAULT '0',
  `minAviso` int(5) DEFAULT '0',
  `existIdeal` int(5) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla lb_neolab.reactivoxdeterminacion
DROP TABLE IF EXISTS `reactivoxdeterminacion`;
CREATE TABLE IF NOT EXISTS `reactivoxdeterminacion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idTipodeterminaciones` int(11) NOT NULL,
  `idReactivos` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idTipodeterminaciones` (`idTipodeterminaciones`),
  KEY `idReactivos` (`idReactivos`)
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla lb_neolab.renglones
DROP TABLE IF EXISTS `renglones`;
CREATE TABLE IF NOT EXISTS `renglones` (
  `idRenglones` int(11) NOT NULL AUTO_INCREMENT,
  `idClientes` int(11) DEFAULT NULL,
  `idPacientes` int(11) DEFAULT NULL,
  `idGrupos` int(11) DEFAULT NULL,
  `idTipodeterminacion` int(11) DEFAULT NULL,
  `orden` int(4) DEFAULT NULL,
  `tipoItem` int(3) DEFAULT NULL,
  `idItems` int(11) DEFAULT NULL,
  `valor` text COLLATE utf8_spanish_ci,
  `valor2` varchar(100) COLLATE utf8_spanish_ci DEFAULT NULL,
  `tipoHtml` int(3) DEFAULT NULL,
  `idAnalizador` varchar(20) COLLATE utf8_spanish_ci DEFAULT NULL,
  `mostrar` int(1) DEFAULT '1',
  `duplic` int(1) DEFAULT NULL,
  PRIMARY KEY (`idRenglones`) USING BTREE,
  KEY `idClientes` (`idClientes`),
  KEY `idPacientes` (`idPacientes`),
  KEY `idGrupos` (`idGrupos`),
  KEY `idTipodeterminacion` (`idTipodeterminacion`),
  KEY `orden` (`orden`),
  KEY `tipoItem` (`tipoItem`),
  KEY `idItems` (`idItems`),
  KEY `idAnalizador` (`idAnalizador`)
) ENGINE=InnoDB AUTO_INCREMENT=85810 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla lb_neolab.renglonesxdeterminacion
DROP TABLE IF EXISTS `renglonesxdeterminacion`;
CREATE TABLE IF NOT EXISTS `renglonesxdeterminacion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idTipodeterminaciones` int(11) NOT NULL,
  `idItemsinforme` int(11) NOT NULL,
  `orden` int(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idTipodeterminaciones` (`idTipodeterminaciones`),
  KEY `idItemsinforme` (`idItemsinforme`),
  KEY `orden` (`orden`)
) ENGINE=InnoDB AUTO_INCREMENT=1182 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla lb_neolab.requerimientos
DROP TABLE IF EXISTS `requerimientos`;
CREATE TABLE IF NOT EXISTS `requerimientos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(30) NOT NULL DEFAULT '',
  `requerimiento` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla lb_neolab.reqxtipodet
DROP TABLE IF EXISTS `reqxtipodet`;
CREATE TABLE IF NOT EXISTS `reqxtipodet` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idRequerimientos` int(11) NOT NULL,
  `idTipodeterminaciones` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idRequerimientos` (`idRequerimientos`),
  KEY `idTipodeterminaciones` (`idTipodeterminaciones`)
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8mb4;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla lb_neolab.roles
DROP TABLE IF EXISTS `roles`;
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rol` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla lb_neolab.tipodeterminaciones
DROP TABLE IF EXISTS `tipodeterminaciones`;
CREATE TABLE IF NOT EXISTS `tipodeterminaciones` (
  `idTipodeterminaciones` int(11) NOT NULL AUTO_INCREMENT,
  `orden` int(4) NOT NULL,
  `nombre` varchar(50) COLLATE utf8_spanish_ci NOT NULL DEFAULT '',
  `precio` decimal(20,2) NOT NULL DEFAULT '0.00',
  `filaDesde` int(4) NOT NULL DEFAULT '0',
  `filasCant` int(4) NOT NULL DEFAULT '0',
  `destino` int(1) NOT NULL DEFAULT '0',
  `perfil` int(1) NOT NULL DEFAULT '0',
  `derivacion` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`idTipodeterminaciones`) USING BTREE,
  KEY `orden` (`orden`)
) ENGINE=InnoDB AUTO_INCREMENT=194 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla lb_neolab.usermenu
DROP TABLE IF EXISTS `usermenu`;
CREATE TABLE IF NOT EXISTS `usermenu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item` varchar(60) COLLATE utf8_spanish_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla lb_neolab.usuarios
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE IF NOT EXISTS `usuarios` (
  `idUsuarios` int(11) NOT NULL AUTO_INCREMENT,
  `idClientes` int(11) DEFAULT NULL,
  `idRoles` int(11) DEFAULT NULL,
  `apenom` varchar(150) COLLATE utf8_spanish_ci NOT NULL DEFAULT '',
  `dni` varchar(10) COLLATE utf8_spanish_ci NOT NULL DEFAULT '',
  `password` varchar(10) COLLATE utf8_spanish_ci NOT NULL DEFAULT '0',
  `permisoAfip` int(1) NOT NULL DEFAULT '0',
  `cuit` varchar(11) COLLATE utf8_spanish_ci NOT NULL DEFAULT '0',
  `razonSocial` varchar(100) COLLATE utf8_spanish_ci NOT NULL DEFAULT '0',
  `domicComerc` varchar(50) COLLATE utf8_spanish_ci NOT NULL DEFAULT '0',
  `condIva` varchar(30) COLLATE utf8_spanish_ci NOT NULL DEFAULT '0',
  `ingresosBrutos` varchar(30) COLLATE utf8_spanish_ci NOT NULL DEFAULT '0',
  `inicioActiv` date DEFAULT NULL,
  `PtoVta` int(2) NOT NULL DEFAULT '0',
  `CbteTipo` int(2) NOT NULL DEFAULT '0',
  `NtaCredTipo` int(2) NOT NULL DEFAULT '0',
  `Concepto` int(2) NOT NULL DEFAULT '0',
  `DocTipo` int(2) NOT NULL DEFAULT '0',
  `CondicionIVAReceptorId` int(2) NOT NULL DEFAULT '0',
  `key` varchar(100) COLLATE utf8_spanish_ci NOT NULL DEFAULT '0',
  `crt` varchar(100) COLLATE utf8_spanish_ci NOT NULL DEFAULT '0',
  PRIMARY KEY (`idUsuarios`),
  KEY `idClientes` (`idClientes`),
  KEY `idRoles` (`idRoles`)
) ENGINE=InnoDB AUTO_INCREMENT=199 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- La exportación de datos fue deseleccionada.

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
