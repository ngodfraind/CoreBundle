<?php

namespace Claroline\CoreBundle\Migrations\drizzle_pdo_mysql;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2013/09/18 04:41:04
 */
class Version20130918164103 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            ALTER TABLE claro_widget 
            ADD is_displayable_in_workspace BOOLEAN NOT NULL, 
            ADD is_displayable_in_desktop BOOLEAN NOT NULL
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            ALTER TABLE claro_widget 
            DROP is_displayable_in_workspace, 
            DROP is_displayable_in_desktop
        ");
    }
}