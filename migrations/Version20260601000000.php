<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration initiale EURORESIL — MariaDB 10.11+
 * Crée toutes les tables du projet.
 */
final class Version20260601000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Schema initial EURORESIL';
    }

    public function up(Schema $schema): void
    {
        // ── users ──────────────────────────────────────────────────────────
        $this->addSql(<<<SQL
            CREATE TABLE users (
                id            CHAR(36)      NOT NULL PRIMARY KEY,
                email         VARCHAR(255)  NOT NULL UNIQUE,
                password      VARCHAR(255)  NOT NULL,
                roles         JSON          NOT NULL,
                profile       ENUM('particulier','pro') NOT NULL DEFAULT 'particulier',
                profession    VARCHAR(50)   NULL,
                raison_sociale VARCHAR(255) NULL,
                registry_type VARCHAR(50)  NULL COMMENT 'orias|barreau|carte_pro|oec|autre',
                registry_number VARCHAR(100) NULL,
                siret         VARCHAR(14)   NULL,
                email_verified_at DATETIME  NULL,
                verification_code CHAR(6)   NULL,
                verification_code_expires_at DATETIME NULL,
                credits       INT           NOT NULL DEFAULT 0,
                created_at    DATETIME      NOT NULL,
                updated_at    DATETIME      NOT NULL,
                INDEX idx_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        SQL);

        // ── ref_assureurs ──────────────────────────────────────────────────
        $this->addSql(<<<SQL
            CREATE TABLE ref_assureurs (
                id        INT           NOT NULL AUTO_INCREMENT PRIMARY KEY,
                name      VARCHAR(255)  NOT NULL,
                aliases   JSON          NULL,
                lre_email VARCHAR(255)  NULL,
                active    TINYINT(1)    NOT NULL DEFAULT 1,
                INDEX idx_name (name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        SQL);

        // ── resiliations ───────────────────────────────────────────────────
        $this->addSql(<<<SQL
            CREATE TABLE resiliations (
                id                  CHAR(36)     NOT NULL PRIMARY KEY,
                user_id             CHAR(36)     NOT NULL,
                souscripteur        JSON         NULL,
                assureur_nom        VARCHAR(255) NULL,
                type_contrat        VARCHAR(100) NULL,
                contrat_ref         VARCHAR(100) NULL,
                motif               TEXT         NULL,
                extra_text          TEXT         NULL,
                status              ENUM('draft','signing','sending','sent','received','failed')
                                                 NOT NULL DEFAULT 'draft',
                letter_pdf_path     TEXT         NULL,
                mandat_pdf_path     TEXT         NULL,
                universign_tx_id    VARCHAR(100) NULL,
                letreco_envoi_id    VARCHAR(100) NULL,
                letreco_pli_number  VARCHAR(50)  NULL,
                created_at          DATETIME     NOT NULL,
                updated_at          DATETIME     NOT NULL,
                CONSTRAINT fk_resil_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user (user_id),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        SQL);

        // ── resil_preuves ──────────────────────────────────────────────────
        $this->addSql(<<<SQL
            CREATE TABLE resil_preuves (
                id              CHAR(36)    NOT NULL PRIMARY KEY,
                resiliation_id  CHAR(36)    NOT NULL,
                type            ENUM('depot','reception','retrait','negligence') NOT NULL,
                event_date      DATETIME    NOT NULL,
                proof_pdf_path  TEXT        NULL,
                raw_payload     JSON        NULL,
                created_at      DATETIME    NOT NULL,
                CONSTRAINT fk_preuve_resil FOREIGN KEY (resiliation_id) REFERENCES resiliations(id) ON DELETE CASCADE,
                INDEX idx_resiliation (resiliation_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        SQL);

        // ── packs ──────────────────────────────────────────────────────────
        $this->addSql(<<<SQL
            CREATE TABLE packs (
                id            CHAR(36)        NOT NULL PRIMARY KEY,
                user_id       CHAR(36)        NOT NULL,
                qty_purchased INT             NOT NULL,
                qty_remaining INT             NOT NULL,
                unit_price    DECIMAL(8,2)    NOT NULL,
                amount_ttc    DECIMAL(10,2)   NOT NULL,
                expires_at    DATETIME        NULL,
                created_at    DATETIME        NOT NULL,
                CONSTRAINT fk_pack_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        SQL);

        // ── invoices ───────────────────────────────────────────────────────
        $this->addSql(<<<SQL
            CREATE TABLE invoices (
                id             CHAR(36)        NOT NULL PRIMARY KEY,
                user_id        CHAR(36)        NOT NULL,
                pack_id        CHAR(36)        NULL,
                invoice_number VARCHAR(30)     NOT NULL UNIQUE,
                amount_ht      DECIMAL(10,2)   NOT NULL,
                tva_rate       DECIMAL(5,2)    NOT NULL DEFAULT 20.00,
                amount_ttc     DECIMAL(10,2)   NOT NULL,
                pdf_path       VARCHAR(500)    NULL,
                issued_at      DATETIME        NOT NULL,
                CONSTRAINT fk_invoice_user FOREIGN KEY (user_id) REFERENCES users(id),
                CONSTRAINT fk_invoice_pack FOREIGN KEY (pack_id)  REFERENCES packs(id),
                INDEX idx_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        SQL);

        // ── lrar_envois (LRAR generique multi-metiers) ─────────────────────
        $this->addSql(<<<SQL
            CREATE TABLE lrar_envois (
                id               CHAR(36)     NOT NULL PRIMARY KEY,
                user_id          CHAR(36)     NOT NULL,
                destinataire     VARCHAR(255) NOT NULL,
                email_dest       VARCHAR(255) NOT NULL,
                objet            VARCHAR(500) NOT NULL,
                message          LONGTEXT     NOT NULL,
                use_case         VARCHAR(50)  NOT NULL DEFAULT 'libre',
                status           ENUM('draft','sending','sent','received','failed') NOT NULL DEFAULT 'sending',
                letreco_envoi_id VARCHAR(100) NULL,
                letreco_pli_number VARCHAR(50) NULL,
                created_at       DATETIME     NOT NULL,
                updated_at       DATETIME     NOT NULL,
                CONSTRAINT fk_lrar_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        SQL);

        // ── Données de référence : assureurs ───────────────────────────────
        $this->addSql(<<<SQL
            INSERT INTO ref_assureurs (name, aliases, lre_email, active) VALUES
            ('AXA France',          '["AXA","Axa"]',          'resiliation@axa.fr',           1),
            ('Allianz France',       '["Allianz"]',             'resiliation@allianz.fr',        1),
            ('Groupama',             '["Groupama"]',            'resiliation@groupama.fr',       1),
            ('Macif',                '["Macif"]',               'resiliation@macif.fr',          1),
            ('Maif',                 '["Maif","La Maif"]',      'resiliation@maif.fr',           1),
            ('Matmut',               '["Matmut"]',              'resiliation@matmut.fr',         1),
            ('Generali France',      '["Generali"]',            'resiliation@generali.fr',       1),
            ('April',                '["April"]',               'resiliation@april.fr',          1),
            ('Swiss Life',           '["Swiss Life"]',          'resiliation@swisslife.fr',      1),
            ('Covea (GMF)',          '["GMF","Covea"]',         'resiliation@gmf.fr',            1),
            ('MAAF',                 '["MAAF"]',                'resiliation@maaf.fr',           1),
            ('Pacifica (Credit Agricole)', '["Pacifica"]',      'resiliation@pacifica.fr',       1),
            ('BNP Paribas Cardif',   '["Cardif","BNP Cardif"]','resiliation@cardif.fr',         1),
            ('Harmonie Mutuelle',    '["Harmonie"]',            'resiliation@harmonie-mutuelle.fr',1),
            ('Mutex',                '["Mutex"]',               'resiliation@mutex.fr',          1),
            ('AG2R La Mondiale',     '["AG2R","La Mondiale"]',  'resiliation@ag2r.fr',           1),
            ('CNP Assurances',       '["CNP"]',                 'resiliation@cnp.fr',            1),
            ('Alptis Assurances',    '["Alptis"]',              'resiliation@alptis.fr',         1),
            ('Sanafor',              '["Sanafor"]',             'resiliation@sanafor.fr',        1)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS lrar_envois');
        $this->addSql('DROP TABLE IF EXISTS invoices');
        $this->addSql('DROP TABLE IF EXISTS packs');
        $this->addSql('DROP TABLE IF EXISTS resil_preuves');
        $this->addSql('DROP TABLE IF EXISTS resiliations');
        $this->addSql('DROP TABLE IF EXISTS ref_assureurs');
        $this->addSql('DROP TABLE IF EXISTS users');
    }
}
