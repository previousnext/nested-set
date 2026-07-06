<?php

namespace PNX\NestedSet\Storage;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use PNX\NestedSet\NestedSetSchemaInterface;

/**
 * Provides DBAL Nested set schema operations.
 */
class DbalNestedSetSchema extends BaseDbalStorage implements NestedSetSchemaInterface {

  /**
   * {@inheritdoc}
   */
  public function create(): void {
    $schema = new Schema();
    $tree = $schema->createTable($this->tableName);
    $tree->addColumn("id", "integer", ["unsigned" => TRUE]);
    $tree->addColumn("revision_id", "integer", ["unsigned" => TRUE]);
    $tree->addColumn("left_pos", "integer", ["unsigned" => TRUE]);
    $tree->addColumn("right_pos", "integer", ["unsigned" => TRUE]);
    $tree->addColumn("depth", "integer", ["unsigned" => TRUE]);

    if (\class_exists('\Doctrine\DBAL\Schema\Name\UnqualifiedName')) {
      // doctrine/dbal 4.
      // @phpstan-ignore method.notFound, class.notFound
      $tree->addPrimaryKeyConstraint(new PrimaryKeyConstraint(UnqualifiedName::unquoted('primary'), [
        UnqualifiedName::unquoted('id'),
        UnqualifiedName::unquoted('revision_id'),
      ], FALSE));
    }
    else {
      // doctrine/dbal 3.
      // @phpstan-ignore method.deprecated
      $tree->setPrimaryKey(['id', 'revision_id']);
    }
    $tree->addIndex(['revision_id']);
    $tree->addIndex(['id', 'revision_id', 'left_pos', 'right_pos', 'depth']);
    $tree->addIndex(['left_pos', 'right_pos']);
    $tree->addIndex(['right_pos']);

    foreach ($schema->toSql($this->connection->getDatabasePlatform()) as $sql) {
      $this->connection->executeStatement($sql);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function drop(): void {
    $schema = new Schema();
    $schema->dropTable($this->tableName);
    foreach ($schema->toSql($this->connection->getDatabasePlatform()) as $sql) {
      $this->connection->executeStatement($sql);
    }
  }

}
