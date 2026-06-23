<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260623130106 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE api_log (id INT AUTO_INCREMENT NOT NULL, api_name VARCHAR(255) NOT NULL, method VARCHAR(10) NOT NULL, uri VARCHAR(255) NOT NULL, payload JSON DEFAULT NULL, response JSON DEFAULT NULL, status_code INT NOT NULL, duration_ms INT DEFAULT NULL, created_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE cancellation_proof (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT NULL, cancellation_id CHAR(36) DEFAULT NULL, document_id INT DEFAULT NULL, INDEX IDX_E98362441C92F001 (cancellation_id), UNIQUE INDEX UNIQ_E9836244C33F7837 (document_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE cancellations (id CHAR(36) NOT NULL, subscriber JSON DEFAULT NULL, contract_type VARCHAR(255) DEFAULT NULL, contract_reference VARCHAR(100) DEFAULT NULL, reason LONGTEXT DEFAULT NULL, extra_text LONGTEXT DEFAULT NULL, status VARCHAR(255) NOT NULL, let_reco_id VARCHAR(100) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id CHAR(36) NOT NULL, document_id INT DEFAULT NULL, insurer_id INT DEFAULT NULL, INDEX IDX_12434D7DA76ED395 (user_id), UNIQUE INDEX UNIQ_12434D7DC33F7837 (document_id), INDEX IDX_12434D7D895854C7 (insurer_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE document (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE insurer (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, active TINYINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE invoices (id CHAR(36) NOT NULL, invoice_number VARCHAR(30) NOT NULL, amount_ht NUMERIC(10, 2) NOT NULL, tva_rate NUMERIC(5, 2) DEFAULT \'20.00\' NOT NULL, amount_ttc NUMERIC(10, 2) NOT NULL, issued_at DATETIME NOT NULL, paid_at DATETIME DEFAULT NULL, user_id CHAR(36) NOT NULL, pack_id CHAR(36) DEFAULT NULL, document_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_6A2F2F952DA68207 (invoice_number), INDEX IDX_6A2F2F95A76ED395 (user_id), INDEX IDX_6A2F2F951919B217 (pack_id), UNIQUE INDEX UNIQ_6A2F2F95C33F7837 (document_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE packs (id CHAR(36) NOT NULL, qty_purchased INT NOT NULL, qty_remaining INT NOT NULL, unit_price NUMERIC(8, 2) NOT NULL, amount_ttc NUMERIC(10, 2) NOT NULL, expires_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, user_id CHAR(36) NOT NULL, INDEX IDX_B9FE6027A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE users (id CHAR(36) NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, roles JSON NOT NULL, profile VARCHAR(255) NOT NULL, profession VARCHAR(255) NOT NULL, raison_sociale VARCHAR(255) DEFAULT NULL, registry_type VARCHAR(50) DEFAULT NULL, registry_number VARCHAR(100) DEFAULT NULL, siret VARCHAR(14) DEFAULT NULL, email_verified_at DATETIME DEFAULT NULL, verification_code VARCHAR(6) DEFAULT NULL, verification_code_expires_at DATETIME DEFAULT NULL, credits INT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, let_reco_user_id VARCHAR(255) DEFAULT NULL, let_reco_password VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
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
        $this->addSql('ALTER TABLE cancellation_proof DROP FOREIGN KEY FK_E98362441C92F001');
        $this->addSql('ALTER TABLE cancellation_proof DROP FOREIGN KEY FK_E9836244C33F7837');
        $this->addSql('ALTER TABLE cancellations DROP FOREIGN KEY FK_12434D7DA76ED395');
        $this->addSql('ALTER TABLE cancellations DROP FOREIGN KEY FK_12434D7DC33F7837');
        $this->addSql('ALTER TABLE cancellations DROP FOREIGN KEY FK_12434D7D895854C7');
        $this->addSql('ALTER TABLE invoices DROP FOREIGN KEY FK_6A2F2F95A76ED395');
        $this->addSql('ALTER TABLE invoices DROP FOREIGN KEY FK_6A2F2F951919B217');
        $this->addSql('ALTER TABLE invoices DROP FOREIGN KEY FK_6A2F2F95C33F7837');
        $this->addSql('ALTER TABLE packs DROP FOREIGN KEY FK_B9FE6027A76ED395');
        $this->addSql('DROP TABLE api_log');
        $this->addSql('DROP TABLE cancellation_proof');
        $this->addSql('DROP TABLE cancellations');
        $this->addSql('DROP TABLE document');
        $this->addSql('DROP TABLE insurer');
        $this->addSql('DROP TABLE invoices');
        $this->addSql('DROP TABLE packs');
        $this->addSql('DROP TABLE users');
    }
}
