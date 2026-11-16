<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261116154346 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE animal DROP FOREIGN KEY `FK_6AAB231F804C8213`');
        $this->addSql('DROP INDEX IDX_6AAB231F804C8213 ON animal');
        $this->addSql('ALTER TABLE animal DROP veterinarian_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE animal ADD veterinarian_id INT NOT NULL');
        $this->addSql('ALTER TABLE animal ADD CONSTRAINT `FK_6AAB231F804C8213` FOREIGN KEY (veterinarian_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_6AAB231F804C8213 ON animal (veterinarian_id)');
    }
}
