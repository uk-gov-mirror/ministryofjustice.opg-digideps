<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version229 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE court_order (id SERIAL NOT NULL, client_id INT DEFAULT NULL, organisation_id INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_E824C019EB6921 ON court_order (client_id)');
        $this->addSql('CREATE INDEX IDX_E824C09E6B1585 ON court_order (organisation_id)');
        $this->addSql('CREATE TABLE court_order_deputies (courtorder_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(courtorder_id, user_id))');
        $this->addSql('CREATE INDEX IDX_7BB4CAE0DEB088F7 ON court_order_deputies (courtorder_id)');
        $this->addSql('CREATE INDEX IDX_7BB4CAE0A76ED395 ON court_order_deputies (user_id)');
        $this->addSql('ALTER TABLE court_order ADD CONSTRAINT FK_E824C019EB6921 FOREIGN KEY (client_id) REFERENCES client (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE court_order ADD CONSTRAINT FK_E824C09E6B1585 FOREIGN KEY (organisation_id) REFERENCES organisation (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE court_order_deputies ADD CONSTRAINT FK_7BB4CAE0DEB088F7 FOREIGN KEY (courtorder_id) REFERENCES court_order (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE court_order_deputies ADD CONSTRAINT FK_7BB4CAE0A76ED395 FOREIGN KEY (user_id) REFERENCES dd_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE report ADD court_order_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE report ADD CONSTRAINT FK_C42F7784A8D7D89C FOREIGN KEY (court_order_id) REFERENCES court_order (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_C42F7784A8D7D89C ON report (court_order_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE court_order_deputies DROP CONSTRAINT FK_7BB4CAE0DEB088F7');
        $this->addSql('ALTER TABLE report DROP CONSTRAINT FK_C42F7784A8D7D89C');
        $this->addSql('DROP TABLE court_order');
        $this->addSql('DROP TABLE court_order_deputies');
        $this->addSql('DROP INDEX IDX_C42F7784A8D7D89C');
        $this->addSql('ALTER TABLE report DROP court_order_id');
    }
}
