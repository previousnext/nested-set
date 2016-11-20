<?php

namespace PNX\Tree\Storage;

use Doctrine\DBAL\Connection;
use PNX\Tree\Leaf;
use PNX\Tree\TreeInterface;

/**
 * Provides a DBAL implementation of a Tree.
 */
class DbalTree implements TreeInterface {

  /**
   * The database connection.
   *
   * @var \Doctrine\DBAL\Connection
   */
  protected $connection;

  /**
   * DbalTree constructor.
   *
   * @param \Doctrine\DBAL\Connection $connection
   *   The database connection.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function addLeaf(Leaf $parent, Leaf $child) {
  }

  /**
   * {@inheritdoc}
   */
  public function findDescendants(Leaf $leaf) {
    $descendants = [];
    $query = $this->connection->createQueryBuilder();
    $query->select('t.id', 't.revision_id', 't.nested_left', 't.nested_right')
      ->from('tree', 't')
      ->where('t.nested_left > :nested_left')
      ->andWhere('t.nested_right < :nested_right')
      ->setParameter(':nested_left', $leaf->getLeft())
      ->setParameter(':nested_right', $leaf->getRight());
    $stmt = $query->execute();
    while ($row = $stmt->fetch()) {
      $descendants[] = new Leaf($row['id'], $row['revision_id'], $row['nested_left'], $row['nested_right']);
    }
    return $descendants;
  }

  /**
   * {@inheritdoc}
   */
  public function getLeaf($id, $revision_id) {
    $result = $this->connection->fetchAssoc("SELECT id, revision_id, nested_left, nested_right FROM tree WHERE id = ? AND revision_id = ?",
      [$id, $revision_id]
    );
    return new Leaf($id, $revision_id, $result['nested_left'], $result['nested_right']);
  }

  /**
   * {@inheritdoc}
   */
  public function findAncestors(Leaf $leaf) {
    $ancestors = [];
    $query = $this->connection->createQueryBuilder();
    $query->select('t.id', 't.revision_id', 't.nested_left', 't.nested_right')
      ->from('tree', 't')
      ->where('t.nested_left < :nested_left')
      ->andWhere('t.nested_right > :nested_right')
      ->setParameter(':nested_left', $leaf->getLeft())
      ->setParameter(':nested_right', $leaf->getRight());
    $stmt = $query->execute();
    while ($row = $stmt->fetch()) {
      $ancestors[] = new Leaf($row['id'], $row['revision_id'], $row['nested_left'], $row['nested_right']);
    }
    return $ancestors;
  }

  /**
   * {@inheritdoc}
   */
  public function getParent(Leaf $leaf) {
    return NULL;
  }

}
