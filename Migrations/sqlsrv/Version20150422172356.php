<?php

namespace Claroline\CoreBundle\Migrations\sqlsrv;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2015/04/22 05:23:58
 */
class Version20150422172356 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            CREATE TABLE claro_role_translation (
                id INT IDENTITY NOT NULL, 
                locale NVARCHAR(8) NOT NULL, 
                object_class NVARCHAR(255) NOT NULL, 
                field NVARCHAR(32) NOT NULL, 
                foreign_key NVARCHAR(64) NOT NULL, 
                content VARCHAR(MAX), 
                PRIMARY KEY (id)
            )
        ");
        $this->addSql("
            CREATE INDEX role_translation_idx ON claro_role_translation (
                locale, object_class, field, foreign_key
            )
        ");
        $this->addSql("
            ALTER TABLE claro_role 
            ADD displayedName NVARCHAR(255)
        ");
        $this->addSql("
            ALTER TABLE claro_role 
            DROP COLUMN translation_key
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            DROP TABLE claro_role_translation
        ");
        $this->addSql("
            ALTER TABLE claro_role 
            ADD translation_key NVARCHAR(255) NOT NULL
        ");
        $this->addSql("
            ALTER TABLE claro_role 
            DROP COLUMN displayedName
        ");
    }
}