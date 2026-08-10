-- Alinea `usuarios` con la estructura de lb_alqu (referencia SILAVET):
-- idRoles + campos AFIP (permisoAfip … crt).
-- Idempotente. Ejecutar manualmente en el cliente MySQL (no desde el agente).
-- Alternativa por lab: php artisan lb:switch <slug> && php artisan lb:migrate-legacy --force
--
-- Agrega solo columnas/índices faltantes. No modifica tipos existentes.
-- No toca permisos_ia (migración 2026_07_03_000001 / permisos_ia_catalogo_inicial.sql).

SET @silavet_schema := DATABASE();

-- ---- idRoles ----
SET @silavet_sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = @silavet_schema AND TABLE_NAME = 'usuarios'
        ) AND NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = @silavet_schema
              AND TABLE_NAME = 'usuarios'
              AND COLUMN_NAME = 'idRoles'
        ),
        IF(
            EXISTS (
                SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = @silavet_schema
                  AND TABLE_NAME = 'usuarios'
                  AND COLUMN_NAME = 'idClientes'
            ),
            'ALTER TABLE `usuarios` ADD COLUMN `idRoles` int(11) DEFAULT NULL AFTER `idClientes`',
            IF(
                EXISTS (
                    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_SCHEMA = @silavet_schema
                      AND TABLE_NAME = 'usuarios'
                      AND COLUMN_NAME = 'idUsuarios'
                ),
                'ALTER TABLE `usuarios` ADD COLUMN `idRoles` int(11) DEFAULT NULL AFTER `idUsuarios`',
                'ALTER TABLE `usuarios` ADD COLUMN `idRoles` int(11) DEFAULT NULL'
            )
        ),
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

-- ---- índice idRoles (como lb_alqu) ----
SET @silavet_sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = @silavet_schema
              AND TABLE_NAME = 'usuarios'
              AND COLUMN_NAME = 'idRoles'
        ) AND NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = @silavet_schema
              AND TABLE_NAME = 'usuarios'
              AND INDEX_NAME = 'idRoles'
        ),
        'ALTER TABLE `usuarios` ADD INDEX `idRoles` (`idRoles`)',
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

-- ---- permisoAfip ----
SET @silavet_sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = @silavet_schema AND TABLE_NAME = 'usuarios'
        ) AND NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = @silavet_schema
              AND TABLE_NAME = 'usuarios'
              AND COLUMN_NAME = 'permisoAfip'
        ),
        IF(
            EXISTS (
                SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = @silavet_schema
                  AND TABLE_NAME = 'usuarios'
                  AND COLUMN_NAME = 'permisos_ia'
            ),
            'ALTER TABLE `usuarios` ADD COLUMN `permisoAfip` int(1) NOT NULL DEFAULT 0 AFTER `permisos_ia`',
            IF(
                EXISTS (
                    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_SCHEMA = @silavet_schema
                      AND TABLE_NAME = 'usuarios'
                      AND COLUMN_NAME = 'password'
                ),
                'ALTER TABLE `usuarios` ADD COLUMN `permisoAfip` int(1) NOT NULL DEFAULT 0 AFTER `password`',
                'ALTER TABLE `usuarios` ADD COLUMN `permisoAfip` int(1) NOT NULL DEFAULT 0'
            )
        ),
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

-- ---- cuit ----
SET @silavet_sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = @silavet_schema AND TABLE_NAME = 'usuarios'
        ) AND NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = @silavet_schema
              AND TABLE_NAME = 'usuarios'
              AND COLUMN_NAME = 'cuit'
        ),
        IF(
            EXISTS (
                SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = @silavet_schema
                  AND TABLE_NAME = 'usuarios'
                  AND COLUMN_NAME = 'permisoAfip'
            ),
            'ALTER TABLE `usuarios` ADD COLUMN `cuit` varchar(11) NOT NULL DEFAULT \'0\' AFTER `permisoAfip`',
            'ALTER TABLE `usuarios` ADD COLUMN `cuit` varchar(11) NOT NULL DEFAULT \'0\''
        ),
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

-- ---- razonSocial ----
SET @silavet_sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = @silavet_schema AND TABLE_NAME = 'usuarios'
        ) AND NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = @silavet_schema
              AND TABLE_NAME = 'usuarios'
              AND COLUMN_NAME = 'razonSocial'
        ),
        IF(
            EXISTS (
                SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = @silavet_schema
                  AND TABLE_NAME = 'usuarios'
                  AND COLUMN_NAME = 'cuit'
            ),
            'ALTER TABLE `usuarios` ADD COLUMN `razonSocial` varchar(100) NOT NULL DEFAULT \'0\' AFTER `cuit`',
            'ALTER TABLE `usuarios` ADD COLUMN `razonSocial` varchar(100) NOT NULL DEFAULT \'0\''
        ),
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

-- ---- domicComerc ----
SET @silavet_sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = @silavet_schema AND TABLE_NAME = 'usuarios'
        ) AND NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = @silavet_schema
              AND TABLE_NAME = 'usuarios'
              AND COLUMN_NAME = 'domicComerc'
        ),
        IF(
            EXISTS (
                SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = @silavet_schema
                  AND TABLE_NAME = 'usuarios'
                  AND COLUMN_NAME = 'razonSocial'
            ),
            'ALTER TABLE `usuarios` ADD COLUMN `domicComerc` varchar(50) NOT NULL DEFAULT \'0\' AFTER `razonSocial`',
            'ALTER TABLE `usuarios` ADD COLUMN `domicComerc` varchar(50) NOT NULL DEFAULT \'0\''
        ),
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

-- ---- condIva ----
SET @silavet_sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = @silavet_schema AND TABLE_NAME = 'usuarios'
        ) AND NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = @silavet_schema
              AND TABLE_NAME = 'usuarios'
              AND COLUMN_NAME = 'condIva'
        ),
        IF(
            EXISTS (
                SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = @silavet_schema
                  AND TABLE_NAME = 'usuarios'
                  AND COLUMN_NAME = 'domicComerc'
            ),
            'ALTER TABLE `usuarios` ADD COLUMN `condIva` varchar(30) NOT NULL DEFAULT \'0\' AFTER `domicComerc`',
            'ALTER TABLE `usuarios` ADD COLUMN `condIva` varchar(30) NOT NULL DEFAULT \'0\''
        ),
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

-- ---- ingresosBrutos ----
SET @silavet_sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = @silavet_schema AND TABLE_NAME = 'usuarios'
        ) AND NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = @silavet_schema
              AND TABLE_NAME = 'usuarios'
              AND COLUMN_NAME = 'ingresosBrutos'
        ),
        IF(
            EXISTS (
                SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = @silavet_schema
                  AND TABLE_NAME = 'usuarios'
                  AND COLUMN_NAME = 'condIva'
            ),
            'ALTER TABLE `usuarios` ADD COLUMN `ingresosBrutos` varchar(30) NOT NULL DEFAULT \'0\' AFTER `condIva`',
            'ALTER TABLE `usuarios` ADD COLUMN `ingresosBrutos` varchar(30) NOT NULL DEFAULT \'0\''
        ),
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

-- ---- inicioActiv ----
SET @silavet_sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = @silavet_schema AND TABLE_NAME = 'usuarios'
        ) AND NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = @silavet_schema
              AND TABLE_NAME = 'usuarios'
              AND COLUMN_NAME = 'inicioActiv'
        ),
        IF(
            EXISTS (
                SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = @silavet_schema
                  AND TABLE_NAME = 'usuarios'
                  AND COLUMN_NAME = 'ingresosBrutos'
            ),
            'ALTER TABLE `usuarios` ADD COLUMN `inicioActiv` date DEFAULT NULL AFTER `ingresosBrutos`',
            'ALTER TABLE `usuarios` ADD COLUMN `inicioActiv` date DEFAULT NULL'
        ),
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

-- ---- PtoVta ----
SET @silavet_sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = @silavet_schema AND TABLE_NAME = 'usuarios'
        ) AND NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = @silavet_schema
              AND TABLE_NAME = 'usuarios'
              AND COLUMN_NAME = 'PtoVta'
        ),
        IF(
            EXISTS (
                SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = @silavet_schema
                  AND TABLE_NAME = 'usuarios'
                  AND COLUMN_NAME = 'inicioActiv'
            ),
            'ALTER TABLE `usuarios` ADD COLUMN `PtoVta` int(2) NOT NULL DEFAULT 0 AFTER `inicioActiv`',
            'ALTER TABLE `usuarios` ADD COLUMN `PtoVta` int(2) NOT NULL DEFAULT 0'
        ),
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

-- ---- CbteTipo ----
SET @silavet_sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = @silavet_schema AND TABLE_NAME = 'usuarios'
        ) AND NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = @silavet_schema
              AND TABLE_NAME = 'usuarios'
              AND COLUMN_NAME = 'CbteTipo'
        ),
        IF(
            EXISTS (
                SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = @silavet_schema
                  AND TABLE_NAME = 'usuarios'
                  AND COLUMN_NAME = 'PtoVta'
            ),
            'ALTER TABLE `usuarios` ADD COLUMN `CbteTipo` int(2) NOT NULL DEFAULT 0 AFTER `PtoVta`',
            'ALTER TABLE `usuarios` ADD COLUMN `CbteTipo` int(2) NOT NULL DEFAULT 0'
        ),
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

-- ---- NtaCredTipo ----
SET @silavet_sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = @silavet_schema AND TABLE_NAME = 'usuarios'
        ) AND NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = @silavet_schema
              AND TABLE_NAME = 'usuarios'
              AND COLUMN_NAME = 'NtaCredTipo'
        ),
        IF(
            EXISTS (
                SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = @silavet_schema
                  AND TABLE_NAME = 'usuarios'
                  AND COLUMN_NAME = 'CbteTipo'
            ),
            'ALTER TABLE `usuarios` ADD COLUMN `NtaCredTipo` int(2) NOT NULL DEFAULT 0 AFTER `CbteTipo`',
            'ALTER TABLE `usuarios` ADD COLUMN `NtaCredTipo` int(2) NOT NULL DEFAULT 0'
        ),
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

-- ---- Concepto ----
SET @silavet_sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = @silavet_schema AND TABLE_NAME = 'usuarios'
        ) AND NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = @silavet_schema
              AND TABLE_NAME = 'usuarios'
              AND COLUMN_NAME = 'Concepto'
        ),
        IF(
            EXISTS (
                SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = @silavet_schema
                  AND TABLE_NAME = 'usuarios'
                  AND COLUMN_NAME = 'NtaCredTipo'
            ),
            'ALTER TABLE `usuarios` ADD COLUMN `Concepto` int(2) NOT NULL DEFAULT 0 AFTER `NtaCredTipo`',
            'ALTER TABLE `usuarios` ADD COLUMN `Concepto` int(2) NOT NULL DEFAULT 0'
        ),
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

-- ---- DocTipo ----
SET @silavet_sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = @silavet_schema AND TABLE_NAME = 'usuarios'
        ) AND NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = @silavet_schema
              AND TABLE_NAME = 'usuarios'
              AND COLUMN_NAME = 'DocTipo'
        ),
        IF(
            EXISTS (
                SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = @silavet_schema
                  AND TABLE_NAME = 'usuarios'
                  AND COLUMN_NAME = 'Concepto'
            ),
            'ALTER TABLE `usuarios` ADD COLUMN `DocTipo` int(2) NOT NULL DEFAULT 0 AFTER `Concepto`',
            'ALTER TABLE `usuarios` ADD COLUMN `DocTipo` int(2) NOT NULL DEFAULT 0'
        ),
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

-- ---- CondicionIVAReceptorId ----
SET @silavet_sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = @silavet_schema AND TABLE_NAME = 'usuarios'
        ) AND NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = @silavet_schema
              AND TABLE_NAME = 'usuarios'
              AND COLUMN_NAME = 'CondicionIVAReceptorId'
        ),
        IF(
            EXISTS (
                SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = @silavet_schema
                  AND TABLE_NAME = 'usuarios'
                  AND COLUMN_NAME = 'DocTipo'
            ),
            'ALTER TABLE `usuarios` ADD COLUMN `CondicionIVAReceptorId` int(2) NOT NULL DEFAULT 0 AFTER `DocTipo`',
            'ALTER TABLE `usuarios` ADD COLUMN `CondicionIVAReceptorId` int(2) NOT NULL DEFAULT 0'
        ),
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

-- ---- key (palabra reservada) ----
SET @silavet_sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = @silavet_schema AND TABLE_NAME = 'usuarios'
        ) AND NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = @silavet_schema
              AND TABLE_NAME = 'usuarios'
              AND COLUMN_NAME = 'key'
        ),
        IF(
            EXISTS (
                SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = @silavet_schema
                  AND TABLE_NAME = 'usuarios'
                  AND COLUMN_NAME = 'CondicionIVAReceptorId'
            ),
            'ALTER TABLE `usuarios` ADD COLUMN `key` varchar(100) NOT NULL DEFAULT \'0\' AFTER `CondicionIVAReceptorId`',
            'ALTER TABLE `usuarios` ADD COLUMN `key` varchar(100) NOT NULL DEFAULT \'0\''
        ),
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;

-- ---- crt ----
SET @silavet_sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = @silavet_schema AND TABLE_NAME = 'usuarios'
        ) AND NOT EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = @silavet_schema
              AND TABLE_NAME = 'usuarios'
              AND COLUMN_NAME = 'crt'
        ),
        IF(
            EXISTS (
                SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = @silavet_schema
                  AND TABLE_NAME = 'usuarios'
                  AND COLUMN_NAME = 'key'
            ),
            'ALTER TABLE `usuarios` ADD COLUMN `crt` varchar(100) NOT NULL DEFAULT \'0\' AFTER `key`',
            'ALTER TABLE `usuarios` ADD COLUMN `crt` varchar(100) NOT NULL DEFAULT \'0\''
        ),
        'SELECT 1'
    )
);
PREPARE silavet_stmt FROM @silavet_sql;
EXECUTE silavet_stmt;
DEALLOCATE PREPARE silavet_stmt;
