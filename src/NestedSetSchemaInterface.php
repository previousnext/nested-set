<?php

namespace PNX\NestedSet;

/**
 * Provides Nested Set schema operations.
 */
interface NestedSetSchemaInterface {

  /**
   * Creates the nested set table.
   */
  public function create(): void;

  /**
   * Drops the nested set table.
   */
  public function drop(): void;

}
