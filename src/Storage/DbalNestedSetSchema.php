<?php

namespace PNX\NestedSet\Storage;

use Doctrine\DBAL\Schema\Schema;
use PNX\NestedSet\NestedSetSchemaInterface;

/**
 * Provides DBAL Nested set schema operations.
 */
class DbalNestedSetSchema extends BaseDbalStorage implements NestedSetSchemaInterface {

  /**
   * {@inheritdoc}
   */
  public function createTable() {
    $schema = new Schema();
    $tree = $schema->createTable($this->tableName);
    $tree->addColumn("id", "integer", ["unsigned" => TRUE]);
    $tree->addColumn("revision_id", "integer", ["unsigned" => TRUE]);
    $tree->addColumn("left_pos", "integer", ["unsigned" => TRUE]);
    $tree->addColumn("right_pos", "integer", ["unsigned" => TRUE]);
    $tree->addColumn("depth", "integer", ["unsigned" => TRUE]);

    foreach ($schema->toSql($this->connection->getDatabasePlatform()) as $sql) {
      $this->connection->exec($sql);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function dropTable() {
    $schema = new Schema();
    $schema->dropTable("tree");
    foreach ($schema->toSql($this->connection->getDatabasePlatform()) as $sql) {
      $this->connection->exec($sql);
    }
  }

}
