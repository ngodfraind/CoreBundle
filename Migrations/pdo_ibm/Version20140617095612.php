<?php

namespace Claroline\CoreBundle\Migrations\pdo_ibm;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2014/06/17 09:56:13
 */
class Version20140617095612 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            CREATE TABLE claro_facet_role (
                facet_id INTEGER NOT NULL, 
                role_id INTEGER NOT NULL, 
                PRIMARY KEY(facet_id, role_id)
            )
        ");
        $this->addSql("
            CREATE INDEX IDX_CDD5845DFC889F24 ON claro_facet_role (facet_id)
        ");
        $this->addSql("
            CREATE INDEX IDX_CDD5845DD60322AC ON claro_facet_role (role_id)
        ");
        $this->addSql("
            CREATE TABLE claro_field_facet_role (
                id INTEGER GENERATED BY DEFAULT AS IDENTITY NOT NULL, 
                role_id INTEGER NOT NULL, 
                canOpen SMALLINT NOT NULL, 
                canEdit SMALLINT NOT NULL, 
                fieldFacet_id INTEGER NOT NULL, 
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("
            CREATE INDEX IDX_12F52A52D60322AC ON claro_field_facet_role (role_id)
        ");
        $this->addSql("
            CREATE INDEX IDX_12F52A529F9239AF ON claro_field_facet_role (fieldFacet_id)
        ");
        $this->addSql("
            ALTER TABLE claro_facet_role 
            ADD CONSTRAINT FK_CDD5845DFC889F24 FOREIGN KEY (facet_id) 
            REFERENCES claro_facet (id) 
            ON DELETE CASCADE
        ");
        $this->addSql("
            ALTER TABLE claro_facet_role 
            ADD CONSTRAINT FK_CDD5845DD60322AC FOREIGN KEY (role_id) 
            REFERENCES claro_role (id) 
            ON DELETE CASCADE
        ");
        $this->addSql("
            ALTER TABLE claro_field_facet_role 
            ADD CONSTRAINT FK_12F52A52D60322AC FOREIGN KEY (role_id) 
            REFERENCES claro_role (id) 
            ON DELETE CASCADE
        ");
        $this->addSql("
            ALTER TABLE claro_field_facet_role 
            ADD CONSTRAINT FK_12F52A529F9239AF FOREIGN KEY (fieldFacet_id) 
            REFERENCES claro_field_facet (id) 
            ON DELETE CASCADE
        ");
        $this->addSql("
            ALTER TABLE claro_field_facet_value ALTER stringValue stringValue VARCHAR(255) DEFAULT NULL ALTER floatValue floatValue DOUBLE PRECISION DEFAULT NULL ALTER dateValue dateValue TIMESTAMP(0) DEFAULT NULL
        ");
        $this->addSql("
            DROP INDEX UNIQ_F6C21DB25E237E06
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            DROP TABLE claro_facet_role
        ");
        $this->addSql("
            DROP TABLE claro_field_facet_role
        ");
        $this->addSql("
            CREATE UNIQUE INDEX UNIQ_F6C21DB25E237E06 ON claro_field_facet (name)
        ");
        $this->addSql("
            ALTER TABLE claro_field_facet_value ALTER stringValue stringValue VARCHAR(255) NOT NULL ALTER floatValue floatValue DOUBLE PRECISION NOT NULL ALTER dateValue dateValue TIMESTAMP(0) NOT NULL
        ");
    }
}