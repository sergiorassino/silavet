-- SILAVET — sincronización aditiva de esquema
-- Generado por: php artisan lb:schema-sync
-- Modelo : lb_neolab
-- Destino: lb_civetfranca
-- Fecha  : 2026-08-16 08:24:28
--
-- ADITIVO: no elimina tablas/columnas ni modifica tipos existentes.
-- Ejecutar sobre la BD destino (USE `lb_civetfranca`).
-- Después: php artisan lb:switch <slug> && php artisan lb:migrate-legacy --force

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------------
-- Diferencias de tipo (NO se modifican; revisar a mano si hace falta)
-- ---------------------------------------------------------------------------
-- determinaciones.idDerivaciones
--   modelo : `idDerivaciones` int(11) NOT NULL
--   destino: `idDerivaciones` int(11) NOT NULL DEFAULT 0
-- pacientes.idEspecies
--   modelo : `idEspecies` int(11) NOT NULL DEFAULT 0
--   destino: `idEspecies` int(11) NOT NULL
-- pacientes.idRazas
--   modelo : `idRazas` int(11) NOT NULL DEFAULT 0
--   destino: `idRazas` int(11) NOT NULL
-- pacientes.tipoRegistro
--   modelo : `tipoRegistro` int(1) NOT NULL
--   destino: `tipoRegistro` int(1) NOT NULL DEFAULT 0
-- pacientes.fechhoy
--   modelo : `fechhoy` datetime NOT NULL
--   destino: `fechhoy` date NOT NULL
-- pacientes.urlExcel
--   modelo : `urlExcel` varchar(500) NOT NULL DEFAULT '0'
--   destino: `urlExcel` varchar(50) NOT NULL DEFAULT '0'
-- renglones.duplic
--   modelo : `duplic` int(1) DEFAULT NULL
--   destino: `duplic` int(1) DEFAULT 1

SET FOREIGN_KEY_CHECKS = 1;
