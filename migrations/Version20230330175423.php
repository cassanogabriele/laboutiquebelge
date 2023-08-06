<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230330175423 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE address CHANGE company company VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE `order` ADD reference VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE address CHANGE company company VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE `order` DROP reference, CHANGE user_id user_id INT DEFAULT NULL, CHANGE carrier_name carrier_name VARCHAR(255) DEFAULT NULL, CHANGE carrier_price carrier_price DOUBLE PRECISION DEFAULT NULL, CHANGE delivery delivery LONGTEXT DEFAULT NULL');
    }
}
