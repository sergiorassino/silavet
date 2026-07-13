-- Tabla de imágenes por renglón (tipoItem = 10).
-- Compatible con blank ScriptCase subirImagen.
-- Solo ejecutar si la tabla NO existe.

CREATE TABLE IF NOT EXISTS `imagenesxrenglon` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idRenglones` int(11) NOT NULL,
  `nombreImagen` varchar(255) NOT NULL,
  `observacion` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idRenglones` (`idRenglones`),
  KEY `nombreImagen` (`nombreImagen`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;
