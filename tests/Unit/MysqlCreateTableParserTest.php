<?php

namespace Tests\Unit;

use App\Support\Schema\MysqlCreateTableParser;
use PHPUnit\Framework\TestCase;

class MysqlCreateTableParserTest extends TestCase
{
    public function test_parsea_columnas_indices_y_fk(): void
    {
        $sql = <<<'SQL'
CREATE TABLE `pacientes` (
  `idPacientes` int(11) NOT NULL AUTO_INCREMENT,
  `idClientes` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`idPacientes`),
  KEY `FK_pacientes_clientes` (`idClientes`),
  CONSTRAINT `FK_pacientes_clientes` FOREIGN KEY (`idClientes`) REFERENCES `clientes` (`idClientes`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=2822 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci
SQL;

        $parsed = (new MysqlCreateTableParser)->parse($sql);

        $this->assertSame('pacientes', $parsed->table);
        $this->assertCount(3, $parsed->columns);
        $this->assertSame('idPacientes', $parsed->columns[0]['name']);
        $this->assertSame('idClientes', $parsed->columns[1]['name']);
        $this->assertSame('idPacientes', $parsed->columns[1]['after']);
        $this->assertCount(2, $parsed->indexes);
        $this->assertSame('PRIMARY', $parsed->indexes[0]['name']);
        $this->assertCount(1, $parsed->foreignKeys);
        $this->assertSame('FK_pacientes_clientes', $parsed->foreignKeys[0]['name']);
        $this->assertStringNotContainsString('AUTO_INCREMENT=', $parsed->options);
    }

    public function test_create_if_not_exists_omite_fk_y_auto_increment(): void
    {
        $parser = new MysqlCreateTableParser;
        $parsed = $parser->parse(<<<'SQL'
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rol` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci
SQL);

        $out = $parser->createTableIfNotExists($parsed);

        $this->assertStringStartsWith('CREATE TABLE IF NOT EXISTS `roles`', $out);
        $this->assertStringNotContainsString('AUTO_INCREMENT=', $out);
        $this->assertStringNotContainsString('FOREIGN KEY', $out);
        $this->assertStringContainsString('PRIMARY KEY (`id`)', $out);
    }

    public function test_make_addable_agrega_default_en_not_null_sin_default(): void
    {
        $parser = new MysqlCreateTableParser;

        $this->assertStringContainsString(
            'DEFAULT 0',
            $parser->makeAddable('`tipoRegistro` int(1) NOT NULL')
        );
        $this->assertStringContainsString(
            'NULL',
            $parser->makeAddable('`observaciones` text NOT NULL')
        );
        $this->assertSame(
            '`email` varchar(150) DEFAULT NULL',
            $parser->makeAddable('`email` varchar(150) DEFAULT NULL')
        );
    }
}
