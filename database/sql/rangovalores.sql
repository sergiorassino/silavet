-- Tabla de valores de referencia por ítem, especie y sexo (hemograma auto / ABM).
-- Referencia: lb_demo, lb_labvetciudad.
-- Solo ejecutar si la tabla NO existe y existe `itemsinforme`.

CREATE TABLE IF NOT EXISTS `rangovalores` (
  `idRangovalores` int(11) NOT NULL AUTO_INCREMENT,
  `idItems` int(11) NOT NULL,
  `idEspecies` int(11) NOT NULL,
  `idSexos` int(11) NOT NULL,
  `valorMin` decimal(10,2) DEFAULT NULL,
  `valorMax` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`idRangovalores`) USING BTREE,
  KEY `fk_rangovalores_items` (`idItems`),
  CONSTRAINT `fk_rangovalores_items` FOREIGN KEY (`idItems`) REFERENCES `itemsinforme` (`idItems`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
