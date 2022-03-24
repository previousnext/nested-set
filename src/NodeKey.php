<?php

namespace PNX\NestedSet;

/**
 * Represents the unique key used for a node.
 */
class NodeKey {

  /**
   * The node ID.
   *
   * @var string|int
   */
  protected string|int $id;

  /**
   * The revision ID.
   *
   * @var string|int
   */
  protected string|int $revisionId;

  /**
   * NodeKey constructor.
   *
   * @param int|string $id
   *   The node id.
   * @param int|string $revisionId
   *   The node revision id.
   */
  public function __construct(string|int $id, string|int $revisionId) {
    $this->id = $id;
    $this->revisionId = $revisionId;
  }

  /**
   * Gets the node id.
   *
   * @return int|string
   *   The node id.
   */
  public function getId(): string|int {
    return $this->id;
  }

  /**
   * Gets the node revision id.
   *
   * @return int|string
   *   The node revision id.
   */
  public function getRevisionId(): string|int {
    return $this->revisionId;
  }

}
