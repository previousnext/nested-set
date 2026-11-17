<?php

namespace PNX\NestedSet\Storage;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use PNX\NestedSet\NestedSetSchemaInterface;

/**
 * Provides DBAL Nested set schema operations.
 */
class DbalNestedSetSchema extends BaseDbalStorage implements NestedSetSchemaInterface {

  /**
   * The table columns.
   */
  private const COLUMNS = ['id', 'revision_id', 'left_pos', 'right_pos', 'depth'];

  /**
   * The primary key columns.
   */
  private const PRIMARY_KEY = ['id', 'revision_id'];

  /**
   * The columns of each index.
   */
  private const INDEXES = [
    ['revision_id'],
    ['id', 'revision_id', 'left_pos', 'right_pos', 'depth'],
    ['left_pos', 'right_pos'],
    ['right_pos'],
  ];

  /**
   * {@inheritdoc}
   */
  public function create(): void {
    $schema = new Schema([$this->buildTable()]);
    foreach ($schema->toSql($this->connection->getDatabasePlatform()) as $sql) {
      $this->connection->executeStatement($sql);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function drop(): void {
    $this->connection->executeStatement(
      $this->connection->getDatabasePlatform()->getDropTableSQL($this->tableName)
    );
  }

  /**
   * Builds the nested set table definition.
   */
  private function buildTable(): Table {
    // Version shim: only constant for a single installed dbal version.
    // @phpstan-ignore function.alreadyNarrowedType
    if (\method_exists(Table::class, 'editor')) {
      // doctrine/dbal >= 4.5.
      $editor = Table::editor()->setUnquotedName($this->tableName);
      foreach (self::COLUMNS as $column) {
        // @phpstan-ignore staticMethod.notFound
        $editor->addColumn(Column::editor()->setUnquotedName($column)->setTypeName('integer')->setUnsigned(TRUE)->create());
      }
      // @phpstan-ignore class.notFound
      $editor->addPrimaryKeyConstraint($this->buildPrimaryKeyConstraint());
      foreach (self::INDEXES as $columns) {
        // @phpstan-ignore staticMethod.notFound
        $editor->addIndex(Index::editor()->setUnquotedColumnNames(...$columns));
      }
      return $editor->create();
    }

    // doctrine/dbal 3 and < 4.5.
    $table = new Table($this->tableName);
    foreach (self::COLUMNS as $column) {
      // @phpstan-ignore method.deprecated
      $table->addColumn($column, 'integer', ['unsigned' => TRUE]);
    }
    if (\class_exists(UnqualifiedName::class)) {
      // doctrine/dbal 4.
      // @phpstan-ignore method.deprecated, method.notFound
      $table->addPrimaryKeyConstraint($this->buildPrimaryKeyConstraint());
    }
    else {
      // doctrine/dbal 3.
      // @phpstan-ignore method.deprecated
      $table->setPrimaryKey(self::PRIMARY_KEY);
    }
    foreach (self::INDEXES as $columns) {
      // @phpstan-ignore method.deprecated
      $table->addIndex($columns);
    }
    return $table;
  }

  /**
   * Builds the primary key constraint for doctrine/dbal 4.
   *
   * @return \Doctrine\DBAL\Schema\PrimaryKeyConstraint
   *   The primary key constraint.
   */
  // @phpstan-ignore class.notFound
  private function buildPrimaryKeyConstraint(): object {
    $columnNames = [];
    foreach (self::PRIMARY_KEY as $column) {
      // @phpstan-ignore class.notFound
      $columnNames[] = UnqualifiedName::unquoted($column);
    }
    // @phpstan-ignore class.notFound
    $name = UnqualifiedName::unquoted('primary');
    // @phpstan-ignore class.notFound
    return new PrimaryKeyConstraint($name, $columnNames, FALSE);
  }

}
