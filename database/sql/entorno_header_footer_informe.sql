-- Header y footer de imagen para el informe de protocolo (entorno)
-- Idempotente: agrega solo las columnas que falten, en TODAS las BD lb_*
-- que tengan tabla `entorno`. No modifica valores ya existentes.
--
-- Rutas relativas a public/, misma carpeta que el logo:
--   public/entorno/logos/{TENANT_SLUG}/header-informe.{ext}
--   public/entorno/logos/{TENANT_SLUG}/footer-informe.{ext}
--
-- Ejecutar manualmente en el cliente MySQL del servidor (re-ejecutable).
-- Alternativa por laboratorio: php artisan lb:switch <slug> && php artisan lb:migrate-legacy --force

DROP PROCEDURE IF EXISTS `silavet_entorno_hf_add_col`;
DROP PROCEDURE IF EXISTS `silavet_entorno_hf_en_schema`;
DROP PROCEDURE IF EXISTS `silavet_entorno_hf_todos`;

DELIMITER $$

CREATE PROCEDURE `silavet_entorno_hf_add_col`(
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

CREATE PROCEDURE `silavet_entorno_hf_en_schema`(IN p_schema VARCHAR(64))
BEGIN
    CALL `silavet_entorno_hf_add_col`(p_schema, 'headerInforme', 'VARCHAR(255) NULL');
    CALL `silavet_entorno_hf_add_col`(p_schema, 'footerInforme', 'VARCHAR(255) NULL');
END$$

CREATE PROCEDURE `silavet_entorno_hf_todos`()
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
        CALL `silavet_entorno_hf_en_schema`(v_schema);
    END LOOP;
    CLOSE cur;
END$$

DELIMITER ;

CALL `silavet_entorno_hf_todos`();

DROP PROCEDURE IF EXISTS `silavet_entorno_hf_todos`;
DROP PROCEDURE IF EXISTS `silavet_entorno_hf_en_schema`;
DROP PROCEDURE IF EXISTS `silavet_entorno_hf_add_col`;
