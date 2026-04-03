<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260404145006 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE currencies (code VARCHAR(3) NOT NULL, name VARCHAR(64) NOT NULL, PRIMARY KEY (code))');
        $this->addSql('CREATE TABLE legal_persons (legal_address VARCHAR(255) DEFAULT NULL, director_fio VARCHAR(255) DEFAULT NULL, chief_accountant_fio VARCHAR(255) DEFAULT NULL, kpp VARCHAR(20) DEFAULT NULL, ogrn VARCHAR(20) DEFAULT NULL, okpo VARCHAR(20) DEFAULT NULL, oktmo VARCHAR(20) DEFAULT NULL, ad_channel_id INT DEFAULT NULL, id INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE natural_persons (birth_date DATE DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, actual_address VARCHAR(255) DEFAULT NULL, place_of_birth VARCHAR(255) DEFAULT NULL, photo_link JSON DEFAULT NULL, nationality INT DEFAULT NULL, snils VARCHAR(16) DEFAULT NULL, additional_info TEXT DEFAULT NULL, warning_message TEXT DEFAULT NULL, loyalty_card_number VARCHAR(64) DEFAULT NULL, loyalty_card_discount JSON DEFAULT NULL, bonuses INT DEFAULT 0 NOT NULL, ad_channel_id INT DEFAULT NULL, id INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE legal_persons ADD CONSTRAINT FK_656759BDBF396750 FOREIGN KEY (id) REFERENCES clients (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE natural_persons ADD CONSTRAINT FK_3EE2DFD3BF396750 FOREIGN KEY (id) REFERENCES clients (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE payments DROP CONSTRAINT fk_65d29b3245db9dc4b9bf5aa2');
        $this->addSql('DROP TABLE api_logs');
        $this->addSql('DROP TABLE payments');
        $this->addSql('ALTER TABLE clients ADD inn VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE clients ADD date_added DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE clients ADD client_type VARCHAR(32) NOT NULL');
        $this->addSql('CREATE INDEX idx_client_phone ON clients (phone)');
        $this->addSql('CREATE INDEX idx_client_email ON clients (email)');
        $this->addSql('CREATE INDEX idx_client_inn ON clients (inn)');
        $this->addSql('DROP INDEX uniq_13d36afd9f75d7b0');
        $this->addSql('ALTER TABLE pawn_good_categories ADD system_category BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE pawn_good_categories DROP external_id');
        $this->addSql('ALTER TABLE pawn_good_categories ALTER id DROP IDENTITY');
        $this->addSql('ALTER TABLE pawn_goods ADD external_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pawn_goods ADD article INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pawn_goods ADD serial_number VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE pawn_goods ADD storage VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE pawn_goods ADD status INT NOT NULL');
        $this->addSql('ALTER TABLE pawn_goods ADD test_operation BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE pawn_goods ADD comment TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE pawn_goods ADD images_links JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE pawn_goods ADD jewelry_extra JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE pawn_goods ADD vehicle_extra JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE pawn_goods ADD currency_code VARCHAR(3) DEFAULT NULL');
        $this->addSql('ALTER TABLE pawn_goods ADD workplace_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pawn_goods ADD CONSTRAINT FK_1D5E232AFDA273EC FOREIGN KEY (currency_code) REFERENCES currencies (code) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE pawn_goods ADD CONSTRAINT FK_1D5E232AAC25FB46 FOREIGN KEY (workplace_id) REFERENCES workplaces (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_1D5E232AFDA273EC ON pawn_goods (currency_code)');
        $this->addSql('CREATE INDEX IDX_1D5E232AAC25FB46 ON pawn_goods (workplace_id)');
        $this->addSql('ALTER TABLE pawn_tickets ADD pawn_chain_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pawn_tickets ADD tariff_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pawn_tickets ADD duration INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pawn_tickets ADD paid_percents NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE pawn_tickets ADD pawn_ticket_debt JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE pawn_tickets ADD comment TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE pawn_tickets ADD entity_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pawn_tickets ADD test_operation BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE pawn_tickets ADD currency_code VARCHAR(3) DEFAULT NULL');
        $this->addSql('ALTER TABLE pawn_tickets ADD CONSTRAINT FK_BDD6954FDA273EC FOREIGN KEY (currency_code) REFERENCES currencies (code) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_BDD6954FDA273EC ON pawn_tickets (currency_code)');
        $this->addSql('CREATE INDEX idx_ticket_workplace_status_due ON pawn_tickets (workplace_id, status, due_date)');
        $this->addSql('DROP INDEX uniq_5c6cfbe69f75d7b0');
        $this->addSql('DROP INDEX idx_workplace_external_id');
        $this->addSql('ALTER TABLE workplaces ADD okato VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE workplaces ADD state INT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE workplaces ADD image_links JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE workplaces DROP external_id');
        $this->addSql('ALTER TABLE workplaces ALTER id DROP IDENTITY');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE api_logs (id INT GENERATED BY DEFAULT AS IDENTITY NOT NULL, endpoint VARCHAR(255) NOT NULL, request_summary VARCHAR(500) DEFAULT NULL, response_summary VARCHAR(500) DEFAULT NULL, status_code INT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_apilog_created ON api_logs (created_at)');
        $this->addSql('CREATE TABLE payments (id INT GENERATED BY DEFAULT AS IDENTITY NOT NULL, amount NUMERIC(10, 2) NOT NULL, payment_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, payment_type VARCHAR(50) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, pawn_ticket_number VARCHAR(10) NOT NULL, pawn_ticket_workplace_id INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_65d29b3245db9dc4b9bf5aa2 ON payments (pawn_ticket_number, pawn_ticket_workplace_id)');
        $this->addSql('ALTER TABLE payments ADD CONSTRAINT fk_65d29b3245db9dc4b9bf5aa2 FOREIGN KEY (pawn_ticket_number, pawn_ticket_workplace_id) REFERENCES pawn_tickets (ticket_number, workplace_id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE legal_persons DROP CONSTRAINT FK_656759BDBF396750');
        $this->addSql('ALTER TABLE natural_persons DROP CONSTRAINT FK_3EE2DFD3BF396750');
        $this->addSql('DROP TABLE currencies');
        $this->addSql('DROP TABLE legal_persons');
        $this->addSql('DROP TABLE natural_persons');
        $this->addSql('DROP INDEX idx_client_phone');
        $this->addSql('DROP INDEX idx_client_email');
        $this->addSql('DROP INDEX idx_client_inn');
        $this->addSql('ALTER TABLE clients DROP inn');
        $this->addSql('ALTER TABLE clients DROP date_added');
        $this->addSql('ALTER TABLE clients DROP client_type');
        $this->addSql('ALTER TABLE pawn_good_categories ADD external_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pawn_good_categories DROP system_category');
        $this->addSql('ALTER TABLE pawn_good_categories ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('CREATE UNIQUE INDEX uniq_13d36afd9f75d7b0 ON pawn_good_categories (external_id)');
        $this->addSql('ALTER TABLE pawn_goods DROP CONSTRAINT FK_1D5E232AFDA273EC');
        $this->addSql('ALTER TABLE pawn_goods DROP CONSTRAINT FK_1D5E232AAC25FB46');
        $this->addSql('DROP INDEX IDX_1D5E232AFDA273EC');
        $this->addSql('DROP INDEX IDX_1D5E232AAC25FB46');
        $this->addSql('ALTER TABLE pawn_goods DROP external_id');
        $this->addSql('ALTER TABLE pawn_goods DROP article');
        $this->addSql('ALTER TABLE pawn_goods DROP serial_number');
        $this->addSql('ALTER TABLE pawn_goods DROP storage');
        $this->addSql('ALTER TABLE pawn_goods DROP status');
        $this->addSql('ALTER TABLE pawn_goods DROP test_operation');
        $this->addSql('ALTER TABLE pawn_goods DROP comment');
        $this->addSql('ALTER TABLE pawn_goods DROP images_links');
        $this->addSql('ALTER TABLE pawn_goods DROP jewelry_extra');
        $this->addSql('ALTER TABLE pawn_goods DROP vehicle_extra');
        $this->addSql('ALTER TABLE pawn_goods DROP currency_code');
        $this->addSql('ALTER TABLE pawn_goods DROP workplace_id');
        $this->addSql('ALTER TABLE pawn_tickets DROP CONSTRAINT FK_BDD6954FDA273EC');
        $this->addSql('DROP INDEX IDX_BDD6954FDA273EC');
        $this->addSql('DROP INDEX idx_ticket_workplace_status_due');
        $this->addSql('ALTER TABLE pawn_tickets DROP pawn_chain_id');
        $this->addSql('ALTER TABLE pawn_tickets DROP tariff_id');
        $this->addSql('ALTER TABLE pawn_tickets DROP duration');
        $this->addSql('ALTER TABLE pawn_tickets DROP paid_percents');
        $this->addSql('ALTER TABLE pawn_tickets DROP pawn_ticket_debt');
        $this->addSql('ALTER TABLE pawn_tickets DROP comment');
        $this->addSql('ALTER TABLE pawn_tickets DROP entity_id');
        $this->addSql('ALTER TABLE pawn_tickets DROP test_operation');
        $this->addSql('ALTER TABLE pawn_tickets DROP currency_code');
        $this->addSql('ALTER TABLE workplaces ADD external_id INT NOT NULL');
        $this->addSql('ALTER TABLE workplaces DROP okato');
        $this->addSql('ALTER TABLE workplaces DROP state');
        $this->addSql('ALTER TABLE workplaces DROP image_links');
        $this->addSql('ALTER TABLE workplaces ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('CREATE UNIQUE INDEX uniq_5c6cfbe69f75d7b0 ON workplaces (external_id)');
        $this->addSql('CREATE INDEX idx_workplace_external_id ON workplaces (external_id)');
    }
}
