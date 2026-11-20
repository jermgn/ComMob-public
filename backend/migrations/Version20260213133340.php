<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260213133340 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "user" ADD first_name VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD age INT DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD gender VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD primary_language VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD secondary_language VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD comprehension_level VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD tone_preference VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD sentence_length_preference VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD speech_speed DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD interests JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD main_environment VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD frequent_contacts JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD self_description TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD ai_expectations TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD needs_large_buttons BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD needs_high_contrast BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD simplified_mode BOOLEAN DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE "user" DROP first_name');
        $this->addSql('ALTER TABLE "user" DROP age');
        $this->addSql('ALTER TABLE "user" DROP gender');
        $this->addSql('ALTER TABLE "user" DROP primary_language');
        $this->addSql('ALTER TABLE "user" DROP secondary_language');
        $this->addSql('ALTER TABLE "user" DROP comprehension_level');
        $this->addSql('ALTER TABLE "user" DROP tone_preference');
        $this->addSql('ALTER TABLE "user" DROP sentence_length_preference');
        $this->addSql('ALTER TABLE "user" DROP speech_speed');
        $this->addSql('ALTER TABLE "user" DROP interests');
        $this->addSql('ALTER TABLE "user" DROP main_environment');
        $this->addSql('ALTER TABLE "user" DROP frequent_contacts');
        $this->addSql('ALTER TABLE "user" DROP self_description');
        $this->addSql('ALTER TABLE "user" DROP ai_expectations');
        $this->addSql('ALTER TABLE "user" DROP needs_large_buttons');
        $this->addSql('ALTER TABLE "user" DROP needs_high_contrast');
        $this->addSql('ALTER TABLE "user" DROP simplified_mode');
    }
}
