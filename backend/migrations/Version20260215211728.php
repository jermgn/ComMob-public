<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260215211728 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "user" ADD last_name VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD ai_description TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" DROP self_description');
        $this->addSql('ALTER TABLE "user" DROP ai_expectations');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE "user" ADD ai_expectations TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" DROP last_name');
        $this->addSql('ALTER TABLE "user" RENAME COLUMN ai_description TO self_description');
    }
}
