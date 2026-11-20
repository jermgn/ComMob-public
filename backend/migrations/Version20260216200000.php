<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260216200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add OTP fields to user table for 2FA';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD otp_code VARCHAR(6) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD otp_expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN "user".otp_expires_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" DROP otp_code');
        $this->addSql('ALTER TABLE "user" DROP otp_expires_at');
    }
}
