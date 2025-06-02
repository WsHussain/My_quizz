<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250602134047 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE quiz_result (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, categorie_id INT NOT NULL, result DOUBLE PRECISION NOT NULL, INDEX IDX_FE2E314AA76ED395 (user_id), INDEX IDX_FE2E314ABCF5E72D (categorie_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz_result ADD CONSTRAINT FK_FE2E314AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz_result ADD CONSTRAINT FK_FE2E314ABCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz_result DROP FOREIGN KEY FK_FE2E314AA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz_result DROP FOREIGN KEY FK_FE2E314ABCF5E72D
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE quiz_result
        SQL);
    }
}
