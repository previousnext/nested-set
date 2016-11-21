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

    $this->connection->beginTransaction();

    // Find right most child.
    /** @var Leaf $rightChild */
    $rightChild = $this->findRightMostChild($parent);

    // Move everything across two places.
    $this->connection->executeUpdate('UPDATE tree SET nested_right = nested_right + 2 WHERE nested_right > ?',
      [$rightChild->getRight()]
    );
    $this->connection->executeUpdate('UPDATE tree SET nested_left = nested_left + 2  WHERE nested_left > ?',
      [$rightChild->getRight()]
    );

    $newLeaf = new Leaf(
      $child->getId(),
      $child->getRevisionId(),
      $rightChild->getRight() + 1,
      $rightChild->getRight() + 2,
      $rightChild->getDepth()
    );

    // Insert the new leaf.
    $this->connection->insert('tree', [
      'id' => $newLeaf->getId(),
      'revision_id' => $newLeaf->getRevisionId(),
      'nested_left' => $newLeaf->getLeft(),
      'nested_right' => $newLeaf->getRight(),
      'depth' => $newLeaf->getDepth(),
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
    $result = $this->connection->fetchAssoc('SELECT id, revision_id, nested_left, nested_right, depth FROM tree WHERE nested_right = ? - 1',
      [$parent->getRight()]);
    return new Leaf($result['id'], $result['revision_id'], $result['nested_left'], $result['nested_right'], $result['depth']);
  }

  /**
   * {@inheritdoc}
   */
  public function findDescendants(Leaf $leaf, $depth = 0) {
    $descendants = [];
    $query = $this->connection->createQueryBuilder();
    $query->select('child.id', 'child.revision_id', 'child.nested_left', 'child.nested_right', 'child.depth')
      ->from('tree', 'child')
      ->from('tree', 'parent')
      ->where('child.nested_left > parent.nested_left')
      ->andWhere('child.nested_right < parent.nested_right')
      ->andWhere('parent.id = :id')
      ->andWhere('parent.revision_id = :revision_id')
      ->setParameter(':id', $leaf->getId())
      ->setParameter(':revision_id', $leaf->getRevisionId());
    if ($depth > 0) {
      $query->andWhere('child.depth <= parent.depth + :depth')
        ->setParameter(':depth', $depth);
    }
    $stmt = $query->execute();
    while ($row = $stmt->fetch()) {
      $descendants[] = new Leaf($row['id'], $row['revision_id'], $row['nested_left'], $row['nested_right'], $row['depth']);
    }
    return $descendants;
  }

  /**
   * {@inheritdoc}
   */
  public function getLeaf($id, $revision_id) {
    $result = $this->connection->fetchAssoc("SELECT id, revision_id, nested_left, nested_right, depth FROM tree WHERE id = ? AND revision_id = ?",
      [$id, $revision_id]
    );
    return new Leaf($id, $revision_id, $result['nested_left'], $result['nested_right'], $result['depth']);
  }

  /**
   * {@inheritdoc}
   */
  public function findAncestors(Leaf $leaf) {
    $ancestors = [];
    $query = $this->connection->createQueryBuilder();
    $query->select('id', 'revision_id', 'nested_left', 'nested_right', 'depth')
      ->from('tree', 't')
      ->where('nested_left < :nested_left')
      ->andWhere('nested_right > :nested_right')
      ->setParameter(':nested_left', $leaf->getLeft())
      ->setParameter(':nested_right', $leaf->getRight());
    $stmt = $query->execute();
    while ($row = $stmt->fetch()) {
      $ancestors[] = new Leaf($row['id'], $row['revision_id'], $row['nested_left'], $row['nested_right'], $row['depth']);
    }
    return $ancestors;
  }

  /**
   * Fetches the entire tree.
   *
   * @return array
   *   The tree.
   */
  public function getTree() {
    return $this->connection->fetchAll('SELECT id, revision_id, nested_left, nested_right, depth FROM tree');
  }

  /**
   * {@inheritdoc}
   */
  public function getParent(Leaf $leaf) {
    return NULL;
  }

}
