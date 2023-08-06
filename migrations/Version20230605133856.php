<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230605133856 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Create shopping_cart table';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS `shopping_cart` (`id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,`product_id` int NOT NULL, `product_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL, `product_price` int NOT NULL, `qty` int NOT NULL, `product_image` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL, `total` int NOT NULL,
        `created_at` timestamp NULL DEFAULT NULL, `updated_at` timestamp NULL DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema) : void
    {

    }
}
