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
  public function create() {
    $schema = new Schema();
    $tree = $schema->createTable($this->tableName);
    $tree->addColumn("id", "integer", ["unsigned" => TRUE]);
    $tree->addColumn("revision_id", "integer", ["unsigned" => TRUE]);
    $tree->addColumn("left_pos", "integer", ["unsigned" => TRUE]);
    $tree->addColumn("right_pos", "integer", ["unsigned" => TRUE]);
    $tree->addColumn("depth", "integer", ["unsigned" => TRUE]);

    $tree->setPrimaryKey(['id', 'revision_id']);
    $tree->addIndex(['id', 'revision_id', 'left_pos', 'right_pos', 'depth']);
    $tree->addIndex(['left_pos', 'right_pos']);
    $tree->addIndex(['right_pos']);

    foreach ($schema->toSql($this->connection->getDatabasePlatform()) as $sql) {
      $this->connection->exec($sql);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function drop() {
    $schema = new Schema();
    $schema->dropTable($this->tableName);
    foreach ($schema->toSql($this->connection->getDatabasePlatform()) as $sql) {
      $this->connection->exec($sql);
    }
  }

}
