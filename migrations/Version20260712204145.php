<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260712204145 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store invoice amounts as integer minor units with an ISO currency code, add the invoice date';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE invoice ADD date DATE NOT NULL');
        $this->addSql('ALTER TABLE invoice ADD amount_minor_units BIGINT NOT NULL');
        $this->addSql('ALTER TABLE invoice ADD amount_currency VARCHAR(3) NOT NULL');
        $this->addSql('ALTER TABLE invoice DROP amount');
        $this->addSql('ALTER TABLE invoice DROP currency');
        $this->addSql('COMMENT ON COLUMN invoice.date IS \'(DC2Type:date_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE invoice ADD amount DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE invoice ADD currency VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE invoice DROP date');
        $this->addSql('ALTER TABLE invoice DROP amount_minor_units');
        $this->addSql('ALTER TABLE invoice DROP amount_currency');
    }
}
