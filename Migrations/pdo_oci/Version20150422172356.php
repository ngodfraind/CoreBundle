<?php

namespace Claroline\CoreBundle\Migrations\pdo_oci;

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
                id NUMBER(10) NOT NULL, 
                locale VARCHAR2(8) NOT NULL, 
                object_class VARCHAR2(255) NOT NULL, 
                field VARCHAR2(32) NOT NULL, 
                foreign_key VARCHAR2(64) NOT NULL, 
                content CLOB DEFAULT NULL, 
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("
            DECLARE constraints_Count NUMBER; BEGIN 
            SELECT COUNT(CONSTRAINT_NAME) INTO constraints_Count 
            FROM USER_CONSTRAINTS 
            WHERE TABLE_NAME = 'CLARO_ROLE_TRANSLATION' 
            AND CONSTRAINT_TYPE = 'P'; IF constraints_Count = 0 
            OR constraints_Count = '' THEN EXECUTE IMMEDIATE 'ALTER TABLE CLARO_ROLE_TRANSLATION ADD CONSTRAINT CLARO_ROLE_TRANSLATION_AI_PK PRIMARY KEY (ID)'; END IF; END;
        ");
        $this->addSql("
            CREATE SEQUENCE CLARO_ROLE_TRANSLATION_ID_SEQ START WITH 1 MINVALUE 1 INCREMENT BY 1
        ");
        $this->addSql("
            CREATE TRIGGER CLARO_ROLE_TRANSLATION_AI_PK BEFORE INSERT ON CLARO_ROLE_TRANSLATION FOR EACH ROW DECLARE last_Sequence NUMBER; last_InsertID NUMBER; BEGIN 
            SELECT CLARO_ROLE_TRANSLATION_ID_SEQ.NEXTVAL INTO :NEW.ID 
            FROM DUAL; IF (
                :NEW.ID IS NULL 
                OR :NEW.ID = 0
            ) THEN 
            SELECT CLARO_ROLE_TRANSLATION_ID_SEQ.NEXTVAL INTO :NEW.ID 
            FROM DUAL; ELSE 
            SELECT NVL(Last_Number, 0) INTO last_Sequence 
            FROM User_Sequences 
            WHERE Sequence_Name = 'CLARO_ROLE_TRANSLATION_ID_SEQ'; 
            SELECT :NEW.ID INTO last_InsertID 
            FROM DUAL; WHILE (last_InsertID > last_Sequence) LOOP 
            SELECT CLARO_ROLE_TRANSLATION_ID_SEQ.NEXTVAL INTO last_Sequence 
            FROM DUAL; END LOOP; END IF; END;
        ");
        $this->addSql("
            CREATE INDEX role_translation_idx ON claro_role_translation (
                locale, object_class, field, foreign_key
            )
        ");
        $this->addSql("
            ALTER TABLE claro_role 
            ADD (
                displayedName VARCHAR2(255) DEFAULT NULL
            )
        ");
        $this->addSql("
            ALTER TABLE claro_role 
            DROP (translation_key)
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            DROP TABLE claro_role_translation
        ");
        $this->addSql("
            ALTER TABLE claro_role 
            ADD (
                translation_key VARCHAR2(255) NOT NULL
            )
        ");
        $this->addSql("
            ALTER TABLE claro_role 
            DROP (displayedName)
        ");
    }
}