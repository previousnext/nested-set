<?php

namespace PNX\Tree;

/**
 * Model object that represents a leaf in a tree.
 */
class Leaf {

  /**
   * The Leaf ID.
   *
   * @var string|int
   */
  protected $id;

  /**
   * The revision ID.
   *
   * @var string|int
   */
  protected $revisionId;

  /**
   * The left value.
   *
   * @var int
   */
  protected $left;

  /**
   * The right value.
   *
   * @var int
   */
  protected $right;

  /**
   * Leaf constructor.
   *
   * @param int|string $id
   *   The ID.
   * @param int|string $revisionId
   *   The revision ID.
   * @param int $left
   *   The left value.
   * @param int $right
   *   The right value.
   */
  public function __construct($id, $revisionId = 0, $left = 0, $right = 0) {
    $this->id = $id;
    $this->revisionId = $revisionId;
    $this->left = $left;
    $this->right = $right;
  }

  /**
   * Gets the ID.
   *
   * @return int|string
   *   The ID.
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Gets the revision ID.
   *
   * @return int|string
   *   The revision ID.
   */
  public function getRevisionId() {
    return $this->revisionId;
  }

  /**
   * Gets the left value.
   *
   * @return int
   *   The left value.
   */
  public function getLeft() {
    return $this->left;
  }

  /**
   * Gets the right value.
   *
   * @return int
   *   The right value.
   */
  public function getRight() {
    return $this->right;
  }



}
