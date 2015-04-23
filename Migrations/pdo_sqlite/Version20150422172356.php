<?php

namespace Claroline\CoreBundle\Migrations\pdo_sqlite;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2015/04/22 05:23:57
 */
class Version20150422172356 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            CREATE TABLE claro_role_translation (
                id INTEGER NOT NULL, 
                locale VARCHAR(8) NOT NULL, 
                object_class VARCHAR(255) NOT NULL, 
                field VARCHAR(32) NOT NULL, 
                foreign_key VARCHAR(64) NOT NULL, 
                content CLOB DEFAULT NULL, 
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("
            CREATE INDEX role_translation_idx ON claro_role_translation (
                locale, object_class, field, foreign_key
            )
        ");
        $this->addSql("
            DROP INDEX UNIQ_317774715E237E06
        ");
        $this->addSql("
            DROP INDEX IDX_3177747182D40A1F
        ");
        $this->addSql("
            CREATE TEMPORARY TABLE __temp__claro_role AS 
            SELECT id, 
            workspace_id, 
            name, 
            is_read_only, 
            type, 
            maxUsers, 
            personal_workspace_creation_enabled 
            FROM claro_role
        ");
        $this->addSql("
            DROP TABLE claro_role
        ");
        $this->addSql("
            CREATE TABLE claro_role (
                id INTEGER NOT NULL, 
                workspace_id INTEGER DEFAULT NULL, 
                name VARCHAR(255) NOT NULL, 
                is_read_only BOOLEAN NOT NULL, 
                type INTEGER NOT NULL, 
                maxUsers INTEGER DEFAULT NULL, 
                personal_workspace_creation_enabled BOOLEAN NOT NULL, 
                displayedName VARCHAR(255) DEFAULT NULL, 
                PRIMARY KEY(id), 
                CONSTRAINT FK_3177747182D40A1F FOREIGN KEY (workspace_id) 
                REFERENCES claro_workspace (id) 
                ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
            )
        ");
        $this->addSql("
            INSERT INTO claro_role (
                id, workspace_id, name, is_read_only, 
                type, maxUsers, personal_workspace_creation_enabled
            ) 
            SELECT id, 
            workspace_id, 
            name, 
            is_read_only, 
            type, 
            maxUsers, 
            personal_workspace_creation_enabled 
            FROM __temp__claro_role
        ");
        $this->addSql("
            DROP TABLE __temp__claro_role
        ");
        $this->addSql("
            CREATE UNIQUE INDEX UNIQ_317774715E237E06 ON claro_role (name)
        ");
        $this->addSql("
            CREATE INDEX IDX_3177747182D40A1F ON claro_role (workspace_id)
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            DROP TABLE claro_role_translation
        ");
        $this->addSql("
            DROP INDEX UNIQ_317774715E237E06
        ");
        $this->addSql("
            DROP INDEX IDX_3177747182D40A1F
        ");
        $this->addSql("
            CREATE TEMPORARY TABLE __temp__claro_role AS 
            SELECT id, 
            workspace_id, 
            name, 
            is_read_only, 
            type, 
            maxUsers, 
            personal_workspace_creation_enabled 
            FROM claro_role
        ");
        $this->addSql("
            DROP TABLE claro_role
        ");
        $this->addSql("
            CREATE TABLE claro_role (
                id INTEGER NOT NULL, 
                workspace_id INTEGER DEFAULT NULL, 
                name VARCHAR(255) NOT NULL, 
                is_read_only BOOLEAN NOT NULL, 
                type INTEGER NOT NULL, 
                maxUsers INTEGER DEFAULT NULL, 
                personal_workspace_creation_enabled BOOLEAN NOT NULL, 
                translation_key VARCHAR(255) NOT NULL, 
                PRIMARY KEY(id), 
                CONSTRAINT FK_3177747182D40A1F FOREIGN KEY (workspace_id) 
                REFERENCES claro_workspace (id) 
                ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
            )
        ");
        $this->addSql("
            INSERT INTO claro_role (
                id, workspace_id, name, is_read_only, 
                type, maxUsers, personal_workspace_creation_enabled
            ) 
            SELECT id, 
            workspace_id, 
            name, 
            is_read_only, 
            type, 
            maxUsers, 
            personal_workspace_creation_enabled 
            FROM __temp__claro_role
        ");
        $this->addSql("
            DROP TABLE __temp__claro_role
        ");
        $this->addSql("
            CREATE UNIQUE INDEX UNIQ_317774715E237E06 ON claro_role (name)
        ");
        $this->addSql("
            CREATE INDEX IDX_3177747182D40A1F ON claro_role (workspace_id)
        ");
    }
}