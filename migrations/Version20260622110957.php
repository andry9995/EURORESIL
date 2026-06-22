<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260622110957 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cancellation_proof ADD CONSTRAINT FK_E98362441C92F001 FOREIGN KEY (cancellation_id) REFERENCES cancellations (id)');
        $this->addSql('ALTER TABLE cancellation_proof ADD CONSTRAINT FK_E9836244C33F7837 FOREIGN KEY (document_id) REFERENCES document (id)');
        $this->addSql('ALTER TABLE cancellations ADD document_id INT DEFAULT NULL, CHANGE contract_type contract_type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE cancellations ADD CONSTRAINT FK_12434D7DA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE cancellations ADD CONSTRAINT FK_12434D7DC33F7837 FOREIGN KEY (document_id) REFERENCES document (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_12434D7DC33F7837 ON cancellations (document_id)');
        $this->addSql('ALTER TABLE invoices ADD CONSTRAINT FK_6A2F2F95A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE invoices ADD CONSTRAINT FK_6A2F2F951919B217 FOREIGN KEY (pack_id) REFERENCES packs (id)');
        $this->addSql('ALTER TABLE invoices ADD CONSTRAINT FK_6A2F2F95C33F7837 FOREIGN KEY (document_id) REFERENCES document (id)');
        $this->addSql('ALTER TABLE packs ADD CONSTRAINT FK_B9FE6027A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE resil_preuves ADD CONSTRAINT FK_3F056333F83639DB FOREIGN KEY (resiliation_id) REFERENCES resiliations (id)');
        $this->addSql('ALTER TABLE resiliations ADD CONSTRAINT FK_1EDE9E13A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cancellation_proof DROP FOREIGN KEY FK_E98362441C92F001');
        $this->addSql('ALTER TABLE cancellation_proof DROP FOREIGN KEY FK_E9836244C33F7837');
        $this->addSql('ALTER TABLE cancellations DROP FOREIGN KEY FK_12434D7DA76ED395');
        $this->addSql('ALTER TABLE cancellations DROP FOREIGN KEY FK_12434D7DC33F7837');
        $this->addSql('DROP INDEX UNIQ_12434D7DC33F7837 ON cancellations');
        $this->addSql('ALTER TABLE cancellations DROP document_id, CHANGE contract_type contract_type VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE invoices DROP FOREIGN KEY FK_6A2F2F95A76ED395');
        $this->addSql('ALTER TABLE invoices DROP FOREIGN KEY FK_6A2F2F951919B217');
        $this->addSql('ALTER TABLE invoices DROP FOREIGN KEY FK_6A2F2F95C33F7837');
        $this->addSql('ALTER TABLE packs DROP FOREIGN KEY FK_B9FE6027A76ED395');
        $this->addSql('ALTER TABLE resil_preuves DROP FOREIGN KEY FK_3F056333F83639DB');
        $this->addSql('ALTER TABLE resiliations DROP FOREIGN KEY FK_1EDE9E13A76ED395');
    }
}
