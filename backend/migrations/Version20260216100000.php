<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260216100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add dialogue + dialogue_block tables, drop otp_code, remove unused user columns';
    }

    public function up(Schema $schema): void
    {
        // Dialogue table
        $this->addSql('CREATE TABLE dialogue (
            id SERIAL NOT NULL,
            user_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            context_type VARCHAR(100) NOT NULL,
            context_label VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IDX_DIALOGUE_USER ON dialogue (user_id)');
        $this->addSql('ALTER TABLE dialogue ADD CONSTRAINT FK_DIALOGUE_USER FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        // DialogueBlock table
        $this->addSql('CREATE TABLE dialogue_block (
            id SERIAL NOT NULL,
            dialogue_id INT NOT NULL,
            text TEXT NOT NULL,
            block_order INT NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IDX_DIALOGUE_BLOCK_DIALOGUE ON dialogue_block (dialogue_id)');
        $this->addSql('ALTER TABLE dialogue_block ADD CONSTRAINT FK_DIALOGUE_BLOCK FOREIGN KEY (dialogue_id) REFERENCES dialogue (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        // Drop OTP table
        $this->addSql('DROP TABLE IF EXISTS otp_code');

        // Remove unused user columns
        $this->addSql('ALTER TABLE "user"
            DROP COLUMN IF EXISTS primary_language,
            DROP COLUMN IF EXISTS secondary_language,
            DROP COLUMN IF EXISTS comprehension_level,
            DROP COLUMN IF EXISTS tone_preference,
            DROP COLUMN IF EXISTS sentence_length_preference,
            DROP COLUMN IF EXISTS speech_speed,
            DROP COLUMN IF EXISTS interests,
            DROP COLUMN IF EXISTS main_environment,
            DROP COLUMN IF EXISTS frequent_contacts,
            DROP COLUMN IF EXISTS needs_large_buttons,
            DROP COLUMN IF EXISTS needs_high_contrast,
            DROP COLUMN IF EXISTS simplified_mode
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE dialogue_block DROP CONSTRAINT FK_DIALOGUE_BLOCK');
        $this->addSql('ALTER TABLE dialogue DROP CONSTRAINT FK_DIALOGUE_USER');
        $this->addSql('DROP TABLE dialogue_block');
        $this->addSql('DROP TABLE dialogue');
    }
}
