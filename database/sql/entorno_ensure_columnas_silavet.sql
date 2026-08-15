-- Alinea `entorno` con las columnas que SILAVET espera (legacy + aditivas).
-- Idempotente: agrega solo las que falten, en TODAS las BD lb_* con tabla `entorno`.
-- No modifica tipos ni valores ya existentes. Sin AFTER (no falla si falta el ancla).
--
-- Ejecutar manualmente en el cliente MySQL del servidor (re-ejecutable).
-- Alternativa por laboratorio: php artisan lb:switch <slug> && php artisan lb:migrate-legacy --force

DROP PROCEDURE IF EXISTS `silavet_entorno_ensure_add_col`;
DROP PROCEDURE IF EXISTS `silavet_entorno_ensure_en_schema`;
DROP PROCEDURE IF EXISTS `silavet_entorno_ensure_todos`;

DELIMITER $$

CREATE PROCEDURE `silavet_entorno_ensure_add_col`(
    IN p_schema VARCHAR(64),
    IN p_column VARCHAR(64),
    IN p_definition VARCHAR(512)
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

CREATE PROCEDURE `silavet_entorno_ensure_en_schema`(IN p_schema VARCHAR(64))
BEGIN
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'formulas', 'text NULL');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'nombreListaPrecio', 'varchar(200) DEFAULT NULL');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'listaPreciosPdf', 'varchar(255) DEFAULT NULL');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'carpeta', 'varchar(30) DEFAULT NULL');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'logo', 'varchar(255) DEFAULT NULL');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'headerInforme', 'varchar(255) DEFAULT NULL');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'footerInforme', 'varchar(255) DEFAULT NULL');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'fondo', 'varchar(255) DEFAULT NULL');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'direLabo', 'varchar(255) DEFAULT NULL');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'teleLabo', 'varchar(80) DEFAULT NULL');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'emailLabo', 'varchar(120) DEFAULT NULL');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'colorInforme', 'varchar(20) DEFAULT NULL');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'colorFondoSistema', 'varchar(20) DEFAULT NULL');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'texto1footerIzq', 'varchar(255) DEFAULT NULL');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'texto2footerIzq', 'varchar(255) DEFAULT NULL');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'texto1footerCentro', 'varchar(255) DEFAULT NULL');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'texto2footerCentro', 'varchar(255) DEFAULT NULL');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'texto1footerDer', 'varchar(255) DEFAULT NULL');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'texto2footerDer', 'varchar(255) DEFAULT NULL');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'firmaIzq', 'varchar(255) DEFAULT NULL');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'firmaCentro', 'varchar(255) DEFAULT NULL');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'firmaDer', 'varchar(255) DEFAULT NULL');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'ctaEnvioMail', 'varchar(120) DEFAULT NULL');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'passEnvioMail', 'varchar(255) DEFAULT NULL');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'fromMail', 'varchar(120) DEFAULT NULL');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'nombrePieMail', 'varchar(120) DEFAULT NULL');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'direccionPieMail', 'varchar(255) DEFAULT NULL');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'telefonoPieMail', 'varchar(80) DEFAULT NULL');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'emailPieMail', 'varchar(120) DEFAULT NULL');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'e_AnchoPapel', 'decimal(8,2) DEFAULT 80');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'e_AnchoEtiq', 'decimal(8,2) DEFAULT 35');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'e_AltoEtiq', 'decimal(8,2) DEFAULT 20');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'e_CantCol', 'tinyint unsigned DEFAULT 2');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'e_GapX', 'decimal(8,2) DEFAULT 2');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'e_GapY', 'decimal(8,2) DEFAULT 2');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'e_MarginTop', 'decimal(8,2) DEFAULT 1');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'e_MarginBottom', 'decimal(8,2) DEFAULT 0');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'e_MarginLeft', 'decimal(8,2) DEFAULT 2');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'e_MarginRight', 'decimal(8,2) DEFAULT 0');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'e_FontLinea1', 'tinyint unsigned DEFAULT 18');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'e_FontLinea2', 'tinyint unsigned DEFAULT 12');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'e_FontLinea3', 'tinyint unsigned DEFAULT 11');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'e_FontLinea4', 'tinyint unsigned DEFAULT 8');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'e_MaxLargoLinea2', 'tinyint unsigned DEFAULT 21');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'e_MaxLargoLinea3', 'tinyint unsigned DEFAULT 25');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'e_Borde', 'tinyint(1) DEFAULT 0');
    CALL `silavet_entorno_ensure_add_col`(p_schema, 'afipFormatoImpresion', 'varchar(20) NOT NULL DEFAULT ''A4''');
END$$

CREATE PROCEDURE `silavet_entorno_ensure_todos`()
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
        CALL `silavet_entorno_ensure_en_schema`(v_schema);
    END LOOP;
    CLOSE cur;
END$$

DELIMITER ;

-- BD actual (aunque no se llame lb_*) y el resto de esquemas lb_%.
CALL `silavet_entorno_ensure_en_schema`(DATABASE());
CALL `silavet_entorno_ensure_todos`();

DROP PROCEDURE IF EXISTS `silavet_entorno_ensure_todos`;
DROP PROCEDURE IF EXISTS `silavet_entorno_ensure_en_schema`;
DROP PROCEDURE IF EXISTS `silavet_entorno_ensure_add_col`;
