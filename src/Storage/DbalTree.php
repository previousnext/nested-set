<?php

namespace PNX\Tree\Storage;

use Doctrine\DBAL\Connection;
use PDO;
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

    $this->connection->beginTransaction();

    // Find right most child.
    /** @var Leaf $rightChild */
    $rightChild = $this->findRightMostChild($parent);

    // Move everything across two places.
    $query = $this->connection->createQueryBuilder();
    $query->update('tree')
      ->set('nested_right', $rightChild->getRight() + 2)
      ->where('nested_right > ?')
      ->setParameter(0, $rightChild->getRight(), PDO::PARAM_INT)
      ->execute();

    $query = $this->connection->createQueryBuilder();
    $query->update('tree')
      ->set('nested_left', $rightChild->getRight() + 2)
      ->where('nested_left > ?')
      ->setParameter(0, $rightChild->getRight(), PDO::PARAM_INT)
      ->execute();

    $newLeaf = new Leaf(
      $child->getId(),
      $child->getRevisionId(),
      $rightChild->getRight() + 1,
      $rightChild->getRight() + 2
    );

    // Insert the new leaf.
    $this->connection->insert('tree', [
      'id' => $newLeaf->getId(),
      'revision_id' => $newLeaf->getRevisionId(),
      'nested_left' => $newLeaf->getLeft(),
      'nested_right' => $newLeaf->getRight(),
    ]);

    $this->connection->commit();

    return $newLeaf;

  }

  /**
   * Finds the right-most child leaf.
   *
   * @param \PNX\Tree\Leaf $parent
   *   The parent leaf.
   *
   * @return \PNX\Tree\Leaf
   *   The right-most child leaf.
   */
  protected function findRightMostChild(Leaf $parent) {
    $result = $this->connection->fetchAssoc('SELECT t.id, t.revision_id, t.nested_left, t.nested_right FROM tree t WHERE t.nested_right = ? - 1',
      [$parent->getRight()]);
    return new Leaf($result['id'], $result['revision_id'], $result['nested_left'], $result['nested_right']);
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
