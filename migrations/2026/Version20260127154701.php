<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260127154701 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE task_skill (
              weight NUMERIC(5, 2) NOT NULL,
              task_id BIGINT NOT NULL,
              skill_id SMALLINT NOT NULL,
              PRIMARY KEY (task_id, skill_id)
            )
        SQL);
        $this->addSql('CREATE INDEX tasks_skills_task_id_idx ON task_skill (task_id)');
        $this->addSql('CREATE INDEX tasks_skills_skill_id_idx ON task_skill (skill_id)');
        $this->addSql('CREATE UNIQUE INDEX tasks_skills_unique ON task_skill (task_id, skill_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              task_skill
            ADD
              CONSTRAINT tasks_skills_task_id_foreign FOREIGN KEY (task_id) REFERENCES task (id) NOT DEFERRABLE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              task_skill
            ADD
              CONSTRAINT tasks_skills_skill_id_foreign FOREIGN KEY (skill_id) REFERENCES skill (id) NOT DEFERRABLE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE task_skill DROP CONSTRAINT tasks_skills_task_id_foreign');
        $this->addSql('ALTER TABLE task_skill DROP CONSTRAINT tasks_skills_skill_id_foreign');
        $this->addSql('DROP TABLE task_skill');
    }
}
