-- Ficha fiscal del laboratorio (ARCA/AFIP) en entorno.
-- Idempotente: agrega cada columna solo si falta, en TODAS las BD lb_*
-- que tengan tabla `entorno`. No modifica valores ya existentes.
--
-- Alternativa por laboratorio: php artisan migrate
--
-- Columnas:
--   afipCuit, afipRazonSocial, afipDomicComerc, afipCondIva, afipIngresosBrutos,
--   afipInicioActiv, afipPtoVta, afipConcepto, afipKey, afipCrt, afipCrtVencimiento
-- Los archivos de certificado van en afipSE/cert/entorno/ (en la tabla solo el nombre).

DROP PROCEDURE IF EXISTS `silavet_entorno_arca_add_col`;
DROP PROCEDURE IF EXISTS `silavet_entorno_arca_en_schema`;
DROP PROCEDURE IF EXISTS `silavet_entorno_arca_todos`;

DELIMITER $$

CREATE PROCEDURE `silavet_entorno_arca_add_col`(
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

CREATE PROCEDURE `silavet_entorno_arca_en_schema`(IN p_schema VARCHAR(64))
BEGIN
    CALL `silavet_entorno_arca_add_col`(p_schema, 'afipCuit', 'VARCHAR(11) NOT NULL DEFAULT \'\'');
    CALL `silavet_entorno_arca_add_col`(p_schema, 'afipRazonSocial', 'VARCHAR(100) NOT NULL DEFAULT \'\'');
    CALL `silavet_entorno_arca_add_col`(p_schema, 'afipDomicComerc', 'VARCHAR(50) NOT NULL DEFAULT \'\'');
    CALL `silavet_entorno_arca_add_col`(p_schema, 'afipCondIva', 'VARCHAR(30) NOT NULL DEFAULT \'\'');
    CALL `silavet_entorno_arca_add_col`(p_schema, 'afipIngresosBrutos', 'VARCHAR(30) NOT NULL DEFAULT \'\'');
    CALL `silavet_entorno_arca_add_col`(p_schema, 'afipInicioActiv', 'DATE NULL');
    CALL `silavet_entorno_arca_add_col`(p_schema, 'afipPtoVta', 'INT UNSIGNED NOT NULL DEFAULT 0');
    CALL `silavet_entorno_arca_add_col`(p_schema, 'afipConcepto', 'TINYINT UNSIGNED NOT NULL DEFAULT 2');
    CALL `silavet_entorno_arca_add_col`(p_schema, 'afipKey', 'VARCHAR(100) NOT NULL DEFAULT \'\'');
    CALL `silavet_entorno_arca_add_col`(p_schema, 'afipCrt', 'VARCHAR(100) NOT NULL DEFAULT \'\'');
    CALL `silavet_entorno_arca_add_col`(p_schema, 'afipCrtVencimiento', 'DATE NULL');
END$$

CREATE PROCEDURE `silavet_entorno_arca_todos`()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE v_schema VARCHAR(64);
    DECLARE cur CURSOR FOR
        SELECT SCHEMA_NAME
        FROM INFORMATION_SCHEMA.SCHEMATA
        WHERE SCHEMA_NAME LIKE 'lb\\_%' ESCAPE '\\'
        ORDER BY SCHEMA_NAME;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    OPEN cur;
    read_loop: LOOP
        FETCH cur INTO v_schema;
        IF done = 1 THEN
            LEAVE read_loop;
        END IF;
        CALL `silavet_entorno_arca_en_schema`(v_schema);
    END LOOP;
    CLOSE cur;
END$$

DELIMITER ;

CALL `silavet_entorno_arca_todos`();

DROP PROCEDURE IF EXISTS `silavet_entorno_arca_todos`;
DROP PROCEDURE IF EXISTS `silavet_entorno_arca_en_schema`;
DROP PROCEDURE IF EXISTS `silavet_entorno_arca_add_col`;
