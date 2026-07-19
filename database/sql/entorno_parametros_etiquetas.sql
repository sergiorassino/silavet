-- Parámetros de etiquetas térmicas en entorno (columnas e_*)
-- Idempotente: agrega solo las columnas que falten, en TODAS las BD lb_*
-- que tengan tabla `entorno`. No modifica valores ya existentes.
--
-- Ejecutar manualmente en el cliente MySQL del servidor (una sola vez; re-ejecutable).
-- Alternativa por laboratorio: php artisan lb:switch <slug> && php artisan lb:migrate-legacy --force

DROP PROCEDURE IF EXISTS `silavet_entorno_add_col`;
DROP PROCEDURE IF EXISTS `silavet_entorno_etiquetas_en_schema`;
DROP PROCEDURE IF EXISTS `silavet_entorno_etiquetas_todos`;

DELIMITER $$

CREATE PROCEDURE `silavet_entorno_add_col`(
    IN p_schema VARCHAR(64),
    IN p_column VARCHAR(64),
    IN p_definition VARCHAR(255)
)
BEGIN
    IF EXISTS (
        SELECT 1
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = p_schema
          AND TABLE_NAME = 'entorno'
    ) AND NOT EXISTS (
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = p_schema
          AND TABLE_NAME = 'entorno'
          AND COLUMN_NAME = p_column
    ) THEN
        SET @silavet_sql := CONCAT(
            'ALTER TABLE `', p_schema, '`.`entorno` ADD COLUMN `', p_column, '` ', p_definition
        );
        PREPARE silavet_stmt FROM @silavet_sql;
        EXECUTE silavet_stmt;
        DEALLOCATE PREPARE silavet_stmt;
    END IF;
END$$

CREATE PROCEDURE `silavet_entorno_etiquetas_en_schema`(IN p_schema VARCHAR(64))
BEGIN
    CALL `silavet_entorno_add_col`(p_schema, 'e_AnchoPapel', 'DECIMAL(8,2) NULL DEFAULT 80');
    CALL `silavet_entorno_add_col`(p_schema, 'e_AnchoEtiq', 'DECIMAL(8,2) NULL DEFAULT 35');
    CALL `silavet_entorno_add_col`(p_schema, 'e_AltoEtiq', 'DECIMAL(8,2) NULL DEFAULT 20');
    CALL `silavet_entorno_add_col`(p_schema, 'e_CantCol', 'TINYINT UNSIGNED NULL DEFAULT 2');
    CALL `silavet_entorno_add_col`(p_schema, 'e_GapX', 'DECIMAL(8,2) NULL DEFAULT 2');
    CALL `silavet_entorno_add_col`(p_schema, 'e_GapY', 'DECIMAL(8,2) NULL DEFAULT 2');
    CALL `silavet_entorno_add_col`(p_schema, 'e_MarginTop', 'DECIMAL(8,2) NULL DEFAULT 1');
    CALL `silavet_entorno_add_col`(p_schema, 'e_MarginBottom', 'DECIMAL(8,2) NULL DEFAULT 0');
    CALL `silavet_entorno_add_col`(p_schema, 'e_MarginLeft', 'DECIMAL(8,2) NULL DEFAULT 2');
    CALL `silavet_entorno_add_col`(p_schema, 'e_MarginRight', 'DECIMAL(8,2) NULL DEFAULT 0');
    CALL `silavet_entorno_add_col`(p_schema, 'e_FontLinea1', 'TINYINT UNSIGNED NULL DEFAULT 18');
    CALL `silavet_entorno_add_col`(p_schema, 'e_FontLinea2', 'TINYINT UNSIGNED NULL DEFAULT 12');
    CALL `silavet_entorno_add_col`(p_schema, 'e_FontLinea3', 'TINYINT UNSIGNED NULL DEFAULT 11');
    CALL `silavet_entorno_add_col`(p_schema, 'e_FontLinea4', 'TINYINT UNSIGNED NULL DEFAULT 8');
    CALL `silavet_entorno_add_col`(p_schema, 'e_MaxLargoLinea2', 'TINYINT UNSIGNED NULL DEFAULT 21');
    CALL `silavet_entorno_add_col`(p_schema, 'e_MaxLargoLinea3', 'TINYINT UNSIGNED NULL DEFAULT 25');
    CALL `silavet_entorno_add_col`(p_schema, 'e_Borde', 'TINYINT(1) NULL DEFAULT 0');
END$$

CREATE PROCEDURE `silavet_entorno_etiquetas_todos`()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE v_schema VARCHAR(64);
    DECLARE cur CURSOR FOR
        SELECT DISTINCT TABLE_SCHEMA
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_NAME = 'entorno'
          AND TABLE_SCHEMA LIKE 'lb\_%' ESCAPE '\\'
        ORDER BY TABLE_SCHEMA;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    OPEN cur;
    read_loop: LOOP
        FETCH cur INTO v_schema;
        IF done = 1 THEN
            LEAVE read_loop;
        END IF;
        CALL `silavet_entorno_etiquetas_en_schema`(v_schema);
    END LOOP;
    CLOSE cur;
END$$

DELIMITER ;

CALL `silavet_entorno_etiquetas_todos`();

DROP PROCEDURE IF EXISTS `silavet_entorno_etiquetas_todos`;
DROP PROCEDURE IF EXISTS `silavet_entorno_etiquetas_en_schema`;
DROP PROCEDURE IF EXISTS `silavet_entorno_add_col`;

-- Verificación (opcional): columnas e_* por laboratorio
-- SELECT TABLE_SCHEMA, COLUMN_NAME, COLUMN_TYPE, COLUMN_DEFAULT
-- FROM INFORMATION_SCHEMA.COLUMNS
-- WHERE TABLE_NAME = 'entorno'
--   AND COLUMN_NAME LIKE 'e\_%' ESCAPE '\\'
--   AND TABLE_SCHEMA LIKE 'lb\_%' ESCAPE '\\'
-- ORDER BY TABLE_SCHEMA, ORDINAL_POSITION;
