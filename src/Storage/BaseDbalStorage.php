<?php

namespace PNX\NestedSet\Storage;

use Doctrine\DBAL\Connection;

/**
 * Abstract base class for DBAL storage classes.
 */
abstract class BaseDbalStorage {

  /**
   * The regex for validating table names.
   */
  const VALID_TABLE_REGEX = '/^[a-zA-Z]\w{1,64}$/';

  protected Connection $connection;
  protected string $tableName;

  /**
   * DbalTree constructor.
   *
   * @param \Doctrine\DBAL\Connection $connection
   *   The database connection.
   * @param string $tableName
   *   (optional) The table name to use.
   */
  public function __construct(Connection $connection, string $tableName = 'tree') {
    $this->connection = $connection;
    if (!$this->validTableName($tableName)) {
      throw new \InvalidArgumentException("Table name must match the regex " . self::VALID_TABLE_REGEX);
    }
    $this->tableName = $tableName;
  }

  /**
   * Checks if the table name is valid.
   *
   * Table names must:
   * - start with a letter
   * - only contain letters, numbers, and underscores
   * - be maximum 64 characters.
   *
   * @param string $tableName
   *   The table name.
   *
   * @return bool
   *   TRUE if the table name is valid.
   */
  protected function validTableName(string $tableName): bool {
    return (bool) preg_match(self::VALID_TABLE_REGEX, $tableName);
  }

}
