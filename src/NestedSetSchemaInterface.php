<?php

namespace PNX\NestedSet;

/**
 * Provides Nested Set schema operations.
 */
interface NestedSetSchemaInterface {

  /**
   * Creates the nested set table.
   */
  public function createTable();

  /**
   * Drops the nested set table.
   */
  public function dropTable();

}
