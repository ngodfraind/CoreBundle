<?php

namespace Claroline\CoreBundle\Migrations\pdo_sqlite;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2014/07/03 01:40:34
 */
class Version20140703134033 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            CREATE TABLE claro_event_event_category (
                event_id INTEGER NOT NULL, 
                eventcategory_id INTEGER NOT NULL, 
                PRIMARY KEY(event_id, eventcategory_id)
            )
        ");
        $this->addSql("
            CREATE INDEX IDX_858F0D4C71F7E88B ON claro_event_event_category (event_id)
        ");
        $this->addSql("
            CREATE INDEX IDX_858F0D4C29E3B4B5 ON claro_event_event_category (eventcategory_id)
        ");
        $this->addSql("
            CREATE TABLE claro_event_category (
                id INTEGER NOT NULL, 
                name VARCHAR(255) NOT NULL, 
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("
            CREATE UNIQUE INDEX UNIQ_408DC8C05E237E06 ON claro_event_category (name)
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            DROP TABLE claro_event_event_category
        ");
        $this->addSql("
            DROP TABLE claro_event_category
        ");
    }
}