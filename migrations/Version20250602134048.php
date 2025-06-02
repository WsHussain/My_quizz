<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250602134048 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add total_questions to quiz_result';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quiz_result ADD total_questions INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE quiz_result MODIFY COLUMN result INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quiz_result DROP COLUMN total_questions');
        $this->addSql('ALTER TABLE quiz_result MODIFY COLUMN result DOUBLE PRECISION NOT NULL');
    }
}
