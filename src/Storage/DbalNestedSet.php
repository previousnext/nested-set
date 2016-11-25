<?php

namespace PNX\Tree\Storage;

use Doctrine\DBAL\Connection;
use Exception;
use PNX\Tree\Leaf;
use PNX\Tree\NestedSetInterface;

/**
 * Provides a DBAL implementation of a Tree.
 */
class DbalNestedSet implements NestedSetInterface {

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

    try {
      $this->connection->beginTransaction();

      list($right, $depth) = $this->getInsertionPosition($parent);

      // Move everything across two places.
      $this->connection->executeUpdate('UPDATE tree SET nested_right = nested_right + 2 WHERE nested_right > ?',
        [$right]
      );
      $this->connection->executeUpdate('UPDATE tree SET nested_left = nested_left + 2  WHERE nested_left > ?',
        [$right]
      );

      // Create a new leaf object to be returned.
      $newLeaf = new Leaf(
        $child->getId(),
        $child->getRevisionId(),
        $right + 1,
        $right + 2,
        $depth
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
    }
    catch (Exception $e) {
      $this->connection->rollBack();
      throw $e;
    }
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
    if ($result) {
      return new Leaf($result['id'], $result['revision_id'], $result['nested_left'], $result['nested_right'], $result['depth']);
    }
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
    if ($result) {
      return new Leaf($id, $revision_id, $result['nested_left'], $result['nested_right'], $result['depth']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function findAncestors(Leaf $leaf) {
    $ancestors = [];
    $stmt = $this->connection->executeQuery('SELECT parent.id, parent.revision_id, parent.nested_left, parent.nested_right, parent.depth FROM tree AS child, tree AS parent WHERE child.nested_left BETWEEN parent.nested_left AND parent.nested_right AND child.id = ? AND child.revision_id = ? ORDER BY parent.nested_left',
      [$leaf->getId(), $leaf->getRevisionId()]
    );
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
    $tree = [];
    $stmt = $this->connection->executeQuery('SELECT id, revision_id, nested_left, nested_right, depth FROM tree ORDER BY nested_left');
    while ($row = $stmt->fetch()) {
      $tree[] = new Leaf($row['id'], $row['revision_id'], $row['nested_left'], $row['nested_right'], $row['depth']);
    }
    return $tree;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteLeaf(Leaf $leaf) {
    $left = $leaf->getLeft();
    $right = $leaf->getRight();
    $width = $right - $left + 1;

    try {
      $this->connection->setAutoCommit(FALSE);
      $this->connection->beginTransaction();

      // Delete the leaf.
      $this->connection->executeUpdate('DELETE FROM tree WHERE nested_left = ?',
        [$left]
      );

      // Move children up a level.
      $this->connection->executeUpdate('UPDATE tree SET nested_right = nested_right - 1, nested_left = nested_left - 1, depth = depth -1 WHERE nested_left BETWEEN ? AND ?',
        [$left, $right]
      );

      // Move everything back two places.
      $this->connection->executeUpdate('UPDATE tree SET nested_right = nested_right - 2 WHERE nested_right > ?',
        [$right]
      );
      $this->connection->executeUpdate('UPDATE tree SET nested_left = tree.nested_left - 2 WHERE nested_left > ?',
        [$right]
      );

      $this->connection->commit();

    }
    catch (Exception $e) {
      $this->connection->rollBack();
      throw $e;
    }
    finally {
      $this->connection->setAutoCommit(TRUE);
    }

  }

  /**
   * {@inheritdoc}
   */
  public function deleteSubTree(Leaf $leaf) {
    $left = $leaf->getLeft();
    $right = $leaf->getRight();
    $width = $right - $left + 1;

    try {
      $this->connection->beginTransaction();

      // Delete the leaf.
      $this->connection->executeUpdate('DELETE FROM tree WHERE nested_left BETWEEN ? AND ?',
        [$left, $right]
      );

      // Move everything back two places.
      $this->connection->executeUpdate('UPDATE tree SET nested_right = nested_right - ? WHERE nested_right > ?',
        [$width, $right]
      );
      $this->connection->executeUpdate('UPDATE tree SET nested_left = nested_left - ?  WHERE nested_left > ?',
        [$width, $right]
      );

      $this->connection->commit();
    }
    catch (Exception $e) {
      $this->connection->rollBack();
      throw $e;
    }

  }

  /**
   * {@inheritdoc}
   */
  public function moveSubTreeBelow(Leaf $target, Leaf $leaf) {
    $newLeftPosition = $target->getLeft() + 1;
    $this->moveSubTreeToPosition($newLeftPosition, $leaf);
  }

  /**
   * {@inheritdoc}
   */
  public function moveSubTreeBefore(Leaf $target, Leaf $leaf) {
    $newLeftPosition = $target->getLeft();
    $this->moveSubTreeToPosition($newLeftPosition, $leaf);
  }

  /**
   * {@inheritdoc}
   */
  public function moveSubTreeAfter(Leaf $target, Leaf $leaf) {
    $newLeftPosition = $target->getRight() + 1;
    $this->moveSubTreeToPosition($newLeftPosition, $leaf);
  }

  /**
   * Moves a subtree to a new position.
   *
   * @param int $newLeftPosition
   *   The new left position.
   * @param \PNX\Tree\Leaf $leaf
   *   The leaf to move.
   *
   * @throws \Exception
   *   If a transaction error occurs.
   */
  protected function moveSubTreeToPosition($newLeftPosition, Leaf $leaf) {
    try {
      // Calculate position adjustment variables.
      $width = $leaf->getRight() - $leaf->getLeft() + 1;
      $distance = $newLeftPosition - $leaf->getLeft();
      $tempPos = $leaf->getLeft();

      $this->connection->beginTransaction();

      // Calculate depth difference.
      $newLeaf = $this->getLeafAtPosition($newLeftPosition);
      $depthDiff = $newLeaf->getDepth() - $leaf->getDepth();

      // Backwards movement must account for new space.
      if ($distance < 0) {
        $distance -= $width;
        $tempPos += $width;
      }

      // Create new space for subtree.
      $this->connection->executeUpdate('UPDATE tree SET nested_left = nested_left + ? WHERE nested_left >= ?',
        [$width, $newLeftPosition]
      );

      $this->connection->executeUpdate('UPDATE tree SET nested_right = nested_right + ? WHERE nested_right >= ?',
        [$width, $newLeftPosition]
      );

      // Move subtree into new space.
      $this->connection->executeUpdate('UPDATE tree SET nested_left = nested_left + ?, nested_right = nested_right + ?, depth = depth + ? WHERE nested_left >= ? AND nested_right < ?',
        [$distance, $distance, $depthDiff, $tempPos, $tempPos + $width]
      );

      // Remove old space vacated by subtree.
      $this->connection->executeUpdate('UPDATE tree SET  nested_left = nested_left - ? WHERE nested_left > ?',
        [$width, $leaf->getRight()]
      );

      $this->connection->executeUpdate('UPDATE tree SET nested_right = nested_right - ? WHERE nested_right > ?',
        [$width, $leaf->getRight()]
      );
    }
    catch (Exception $e) {
      $this->connection->rollBack();
      throw $e;
    }

  }

  /**
   * Determines if this leaf is a 'leaf', i.e. has no children.
   *
   * @param \PNX\Tree\Leaf $leaf
   *   The leaf to check.
   *
   * @return bool
   *   TRUE if there are no children. FALSE otherwise.
   */
  protected function isLeaf(Leaf $leaf) {
    return $leaf->getRight() - $leaf->getLeft() === 1;
  }

  /**
   * {@inheritdoc}
   */
  public function getLeafAtPosition($left) {
    $result = $this->connection->fetchAssoc("SELECT id, revision_id, nested_left, nested_right, depth FROM tree WHERE nested_left = ?",
      [$left]
    );
    if ($result) {
      return new Leaf($result['id'], $result['revision_id'], $result['nested_left'], $result['nested_right'], $result['depth']);
    }
  }

  /**
   * Gets the insertion position under the given parent.
   *
   * Takes into account if the parent has no children.
   *
   * @param \PNX\Tree\Leaf $parent
   *   The parent leaf.
   *
   * @return int[]
   *   The right and depth postiions.
   */
  protected function getInsertionPosition(Leaf $parent) {
    if ($this->isLeaf($parent)) {
      // We are on a leaf node.
      $right = $parent->getLeft();
      $depth = $parent->getDepth() + 1;
    }
    else {
      // Find right most child.
      /** @var Leaf $rightChild */
      $rightChild = $this->findRightMostChild($parent);
      $right = $rightChild->getRight();
      $depth = $rightChild->getDepth();
    }
    return [$right, $depth];
  }

}
