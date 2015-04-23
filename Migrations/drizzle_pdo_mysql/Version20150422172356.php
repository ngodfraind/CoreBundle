<?php

namespace Claroline\CoreBundle\Migrations\drizzle_pdo_mysql;

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
                id INT AUTO_INCREMENT NOT NULL, 
                locale VARCHAR(8) NOT NULL, 
                object_class VARCHAR(255) NOT NULL, 
                field VARCHAR(32) NOT NULL, 
                foreign_key VARCHAR(64) NOT NULL, 
                content TEXT DEFAULT NULL, 
                PRIMARY KEY(id), 
                INDEX role_translation_idx (
                    locale, object_class, field, foreign_key
                )
            )
        ");
        $this->addSql("
            ALTER TABLE claro_role 
            ADD displayedName VARCHAR(255) DEFAULT NULL, 
            DROP translation_key
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            DROP TABLE claro_role_translation
        ");
        $this->addSql("
            ALTER TABLE claro_role 
            ADD translation_key VARCHAR(255) NOT NULL, 
            DROP displayedName
        ");
    }
}