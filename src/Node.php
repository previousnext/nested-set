<?php

namespace PNX\NestedSet;

/**
 * Model object that represents a node in a tree.
 */
class Node {

  /**
   * The node key.
   *
   * @var NodeKey
   */
  protected $nodeKey;

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
   * The depth.
   *
   * @var int
   */
  protected $depth;

  /**
   * Node constructor.
   *
   * @param NodeKey $nodeKey
   *   The node key.
   * @param int $left
   *   The left value.
   * @param int $right
   *   The right value.
   * @param int $depth
   *   The depth.
   */
  public function __construct(NodeKey $nodeKey, $left, $right, $depth) {
    if ($nodeKey == NULL) {
      throw new \InvalidArgumentException("Node key cannot be NULL");
    }
    $this->nodeKey = $nodeKey;
    if ($left < 1) {
      throw new \InvalidArgumentException("Left value must be > 0");
    }
    $this->left = $left;
    if ($right < 1) {
      throw new \InvalidArgumentException("Right value must be > 0");
    }
    $this->right = $right;
    if ($depth < 0) {
      throw new \InvalidArgumentException("Depth value must be >= 0");
    }
    $this->depth = $depth;
  }

  /**
   * Gets the ID.
   *
   * @return int|string
   *   The ID.
   */
  public function getId() {
    return $this->nodeKey->getId();
  }

  /**
   * Gets the revision ID.
   *
   * @return int|string
   *   The revision ID.
   */
  public function getRevisionId() {
    return $this->nodeKey->getRevisionId();
  }

  /**
   * Gets the node key.
   *
   * @return \PNX\NestedSet\NodeKey
   *   The node key.
   */
  public function getNodeKey() {
    return $this->nodeKey;
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

  /**
   * Gets the depth.
   *
   * @return int
   *   The depth.
   */
  public function getDepth() {
    return $this->depth;
  }

}
