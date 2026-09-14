-- Recorta espacios en códigos de autoanalizador (ej. "CAIII " → "CAIII").
-- LabVet Ciudad: el ítem Calcio (idItems 46) tenía un espacio final y no
-- coincidía con el CSV del Metrolab CM 250.
-- BINARY: en collations PAD SPACE, TRIM() = columna no detectaría el espacio.
--
-- Tablas: itemsinforme, renglones. Idempotente. No borra valores de resultado.

UPDATE `itemsinforme`
SET `idAnalizador` = TRIM(`idAnalizador`)
WHERE `idAnalizador` IS NOT NULL
  AND `idAnalizador` <> ''
  AND BINARY `idAnalizador` <> BINARY TRIM(`idAnalizador`);

UPDATE `renglones`
SET `idAnalizador` = TRIM(`idAnalizador`)
WHERE `idAnalizador` IS NOT NULL
  AND `idAnalizador` <> ''
  AND BINARY `idAnalizador` <> BINARY TRIM(`idAnalizador`);
