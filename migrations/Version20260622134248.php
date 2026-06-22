<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260622134248 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE ref_assureurs');
        $this->addSql('DROP TABLE resil_preuves');
        $this->addSql('DROP TABLE resiliations');
        $this->addSql('ALTER TABLE cancellation_proof ADD CONSTRAINT FK_E98362441C92F001 FOREIGN KEY (cancellation_id) REFERENCES cancellations (id)');
        $this->addSql('ALTER TABLE cancellation_proof ADD CONSTRAINT FK_E9836244C33F7837 FOREIGN KEY (document_id) REFERENCES document (id)');
        $this->addSql('ALTER TABLE cancellations ADD CONSTRAINT FK_12434D7DA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE cancellations ADD CONSTRAINT FK_12434D7DC33F7837 FOREIGN KEY (document_id) REFERENCES document (id)');
        $this->addSql('ALTER TABLE cancellations ADD CONSTRAINT FK_12434D7D895854C7 FOREIGN KEY (insurer_id) REFERENCES insurer (id)');
        $this->addSql('ALTER TABLE invoices ADD CONSTRAINT FK_6A2F2F95A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE invoices ADD CONSTRAINT FK_6A2F2F951919B217 FOREIGN KEY (pack_id) REFERENCES packs (id)');
        $this->addSql('ALTER TABLE invoices ADD CONSTRAINT FK_6A2F2F95C33F7837 FOREIGN KEY (document_id) REFERENCES document (id)');
        $this->addSql('ALTER TABLE packs ADD CONSTRAINT FK_B9FE6027A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ref_assureurs (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, aliases JSON DEFAULT NULL, lre_email VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, active TINYINT DEFAULT 1 NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = MyISAM COMMENT = \'\' ');
        $this->addSql('CREATE TABLE resil_preuves (id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, type VARCHAR(30) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, event_date DATETIME NOT NULL, proof_pdf_path LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, raw_payload JSON DEFAULT NULL, created_at DATETIME NOT NULL, resiliation_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_3F056333F83639DB (resiliation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = MyISAM COMMENT = \'\' ');
        $this->addSql('CREATE TABLE resiliations (id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, souscripteur JSON DEFAULT NULL, assureur_nom VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, type_contrat VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, contrat_ref VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, motif LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, extra_text LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, status VARCHAR(30) CHARACTER SET utf8mb4 DEFAULT \'draft\' NOT NULL COLLATE `utf8mb4_unicode_ci`, letter_pdf_path LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, mandat_pdf_path LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, universign_tx_id VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, letreco_envoi_id VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, letreco_pli_number VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_1EDE9E13A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = MyISAM COMMENT = \'\' ');
        $this->addSql('ALTER TABLE cancellation_proof DROP FOREIGN KEY FK_E98362441C92F001');
        $this->addSql('ALTER TABLE cancellation_proof DROP FOREIGN KEY FK_E9836244C33F7837');
        $this->addSql('ALTER TABLE cancellations DROP FOREIGN KEY FK_12434D7DA76ED395');
        $this->addSql('ALTER TABLE cancellations DROP FOREIGN KEY FK_12434D7DC33F7837');
        $this->addSql('ALTER TABLE cancellations DROP FOREIGN KEY FK_12434D7D895854C7');
        $this->addSql('ALTER TABLE invoices DROP FOREIGN KEY FK_6A2F2F95A76ED395');
        $this->addSql('ALTER TABLE invoices DROP FOREIGN KEY FK_6A2F2F951919B217');
        $this->addSql('ALTER TABLE invoices DROP FOREIGN KEY FK_6A2F2F95C33F7837');
        $this->addSql('ALTER TABLE packs DROP FOREIGN KEY FK_B9FE6027A76ED395');
    }
}
