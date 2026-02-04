<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260204002816 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event ADD promoter_id INT DEFAULT NULL');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              event
            ADD
              CONSTRAINT FK_3BAE0AA74B84B276 FOREIGN KEY (promoter_id) REFERENCES "user" (id)
        SQL);
        $this->addSql('CREATE INDEX IDX_3BAE0AA74B84B276 ON event (promoter_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event DROP CONSTRAINT FK_3BAE0AA74B84B276');
        $this->addSql('DROP INDEX IDX_3BAE0AA74B84B276');
        $this->addSql('ALTER TABLE event DROP promoter_id');
    }
}
