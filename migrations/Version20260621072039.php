<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260621072039 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE document (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE invoices ADD document_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE invoices ADD CONSTRAINT FK_6A2F2F95A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE invoices ADD CONSTRAINT FK_6A2F2F951919B217 FOREIGN KEY (pack_id) REFERENCES packs (id)');
        $this->addSql('ALTER TABLE invoices ADD CONSTRAINT FK_6A2F2F95C33F7837 FOREIGN KEY (document_id) REFERENCES document (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6A2F2F95C33F7837 ON invoices (document_id)');
        $this->addSql('ALTER TABLE packs ADD CONSTRAINT FK_B9FE6027A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE resil_preuves ADD CONSTRAINT FK_3F056333F83639DB FOREIGN KEY (resiliation_id) REFERENCES resiliations (id)');
        $this->addSql('ALTER TABLE resiliations ADD CONSTRAINT FK_1EDE9E13A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE document');
        $this->addSql('ALTER TABLE invoices DROP FOREIGN KEY FK_6A2F2F95A76ED395');
        $this->addSql('ALTER TABLE invoices DROP FOREIGN KEY FK_6A2F2F951919B217');
        $this->addSql('ALTER TABLE invoices DROP FOREIGN KEY FK_6A2F2F95C33F7837');
        $this->addSql('DROP INDEX UNIQ_6A2F2F95C33F7837 ON invoices');
        $this->addSql('ALTER TABLE invoices DROP document_id');
        $this->addSql('ALTER TABLE packs DROP FOREIGN KEY FK_B9FE6027A76ED395');
        $this->addSql('ALTER TABLE resil_preuves DROP FOREIGN KEY FK_3F056333F83639DB');
        $this->addSql('ALTER TABLE resiliations DROP FOREIGN KEY FK_1EDE9E13A76ED395');
    }
}
