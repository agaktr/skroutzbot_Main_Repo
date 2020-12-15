<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201211165109 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE price ADD updated INT NOT NULL');
        $this->addSql('ALTER TABLE product ADD updated INT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7F2F2678D17F50A6 ON profile_raw_data (uuid)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE price DROP updated');
        $this->addSql('ALTER TABLE product DROP updated');
        $this->addSql('DROP INDEX UNIQ_7F2F2678D17F50A6 ON profile_raw_data');
    }
}
