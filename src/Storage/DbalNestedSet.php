<?php

namespace PNX\Tree\Storage;

use Doctrine\DBAL\Connection;
use Exception;
use PNX\Tree\Node;
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
  public function addNode(Node $parent, Node $child) {

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

      // Create a new node object to be returned.
      $newNode = new Node(
        $child->getId(),
        $child->getRevisionId(),
        $right + 1,
        $right + 2,
        $depth
      );

      // Insert the new node.
      $this->connection->insert('tree', [
        'id' => $newNode->getId(),
        'revision_id' => $newNode->getRevisionId(),
        'nested_left' => $newNode->getLeft(),
        'nested_right' => $newNode->getRight(),
        'depth' => $newNode->getDepth(),
      ]);

      $this->connection->commit();
    }
    catch (Exception $e) {
      $this->connection->rollBack();
      throw $e;
    }
    return $newNode;

  }

  /**
   * Finds the right-most child node.
   *
   * @param \PNX\Tree\Node $parent
   *   The parent node.
   *
   * @return \PNX\Tree\Node
   *   The right-most child node.
   */
  protected function findRightMostChild(Node $parent) {
    $result = $this->connection->fetchAssoc('SELECT id, revision_id, nested_left, nested_right, depth FROM tree WHERE nested_right = ? - 1',
      [$parent->getRight()]);
    if ($result) {
      return new Node($result['id'], $result['revision_id'], $result['nested_left'], $result['nested_right'], $result['depth']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function findDescendants(Node $node, $depth = 0) {
    $descendants = [];
    $query = $this->connection->createQueryBuilder();
    $query->select('child.id', 'child.revision_id', 'child.nested_left', 'child.nested_right', 'child.depth')
      ->from('tree', 'child')
      ->from('tree', 'parent')
      ->where('child.nested_left > parent.nested_left')
      ->andWhere('child.nested_right < parent.nested_right')
      ->andWhere('parent.id = :id')
      ->andWhere('parent.revision_id = :revision_id')
      ->setParameter(':id', $node->getId())
      ->setParameter(':revision_id', $node->getRevisionId());
    if ($depth > 0) {
      $query->andWhere('child.depth <= parent.depth + :depth')
        ->setParameter(':depth', $depth);
    }
    $stmt = $query->execute();
    while ($row = $stmt->fetch()) {
      $descendants[] = new Node($row['id'], $row['revision_id'], $row['nested_left'], $row['nested_right'], $row['depth']);
    }
    return $descendants;
  }

  /**
   * {@inheritdoc}
   */
  public function getNode($id, $revision_id) {
    $result = $this->connection->fetchAssoc("SELECT id, revision_id, nested_left, nested_right, depth FROM tree WHERE id = ? AND revision_id = ?",
      [$id, $revision_id]
    );
    if ($result) {
      return new Node($id, $revision_id, $result['nested_left'], $result['nested_right'], $result['depth']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function findAncestors(Node $node) {
    $ancestors = [];
    $stmt = $this->connection->executeQuery('SELECT parent.id, parent.revision_id, parent.nested_left, parent.nested_right, parent.depth FROM tree AS child, tree AS parent WHERE child.nested_left BETWEEN parent.nested_left AND parent.nested_right AND child.id = ? AND child.revision_id = ? ORDER BY parent.nested_left',
      [$node->getId(), $node->getRevisionId()]
    );
    while ($row = $stmt->fetch()) {
      $ancestors[] = new Node($row['id'], $row['revision_id'], $row['nested_left'], $row['nested_right'], $row['depth']);
    }
    return $ancestors;
  }

  /**
   * {@inheritdoc}
   */
  public function getTree() {
    $tree = [];
    $stmt = $this->connection->executeQuery('SELECT id, revision_id, nested_left, nested_right, depth FROM tree ORDER BY nested_left');
    while ($row = $stmt->fetch()) {
      $tree[] = new Node($row['id'], $row['revision_id'], $row['nested_left'], $row['nested_right'], $row['depth']);
    }
    return $tree;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteNode(Node $node) {
    $left = $node->getLeft();
    $right = $node->getRight();
    $width = $right - $left + 1;

    try {
      $this->connection->setAutoCommit(FALSE);
      $this->connection->beginTransaction();

      // Delete the node.
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
  public function deleteSubTree(Node $node) {
    $left = $node->getLeft();
    $right = $node->getRight();
    $width = $right - $left + 1;

    try {
      $this->connection->beginTransaction();

      // Delete the node.
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
  public function moveSubTreeBelow(Node $target, Node $node) {
    $newLeftPosition = $target->getLeft() + 1;
    $this->moveSubTreeToPosition($newLeftPosition, $node);
  }

  /**
   * {@inheritdoc}
   */
  public function moveSubTreeBefore(Node $target, Node $node) {
    $newLeftPosition = $target->getLeft();
    $this->moveSubTreeToPosition($newLeftPosition, $node);
  }

  /**
   * {@inheritdoc}
   */
  public function moveSubTreeAfter(Node $target, Node $node) {
    $newLeftPosition = $target->getRight() + 1;
    $this->moveSubTreeToPosition($newLeftPosition, $node);
  }

  /**
   * Moves a subtree to a new position.
   *
   * @param int $newLeftPosition
   *   The new left position.
   * @param \PNX\Tree\Node $node
   *   The node to move.
   *
   * @throws \Exception
   *   If a transaction error occurs.
   */
  protected function moveSubTreeToPosition($newLeftPosition, Node $node) {
    try {
      // Calculate position adjustment variables.
      $width = $node->getRight() - $node->getLeft() + 1;
      $distance = $newLeftPosition - $node->getLeft();
      $tempPos = $node->getLeft();

      $this->connection->beginTransaction();

      // Calculate depth difference.
      $newNode = $this->getNodeAtPosition($newLeftPosition);
      $depthDiff = $newNode->getDepth() - $node->getDepth();

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
        [$width, $node->getRight()]
      );

      $this->connection->executeUpdate('UPDATE tree SET nested_right = nested_right - ? WHERE nested_right > ?',
        [$width, $node->getRight()]
      );
    }
    catch (Exception $e) {
      $this->connection->rollBack();
      throw $e;
    }

  }

  /**
   * Determines if this node is a 'leaf', i.e. has no children.
   *
   * @param \PNX\Tree\Node $node
   *   The node to check.
   *
   * @return bool
   *   TRUE if there are no children. FALSE otherwise.
   */
  protected function isLeaf(Node $node) {
    return $node->getRight() - $node->getLeft() === 1;
  }

  /**
   * {@inheritdoc}
   */
  public function getNodeAtPosition($left) {
    $result = $this->connection->fetchAssoc("SELECT id, revision_id, nested_left, nested_right, depth FROM tree WHERE nested_left = ?",
      [$left]
    );
    if ($result) {
      return new Node($result['id'], $result['revision_id'], $result['nested_left'], $result['nested_right'], $result['depth']);
    }
  }

  /**
   * Gets the insertion position under the given parent.
   *
   * Takes into account if the parent has no children.
   *
   * @param \PNX\Tree\Node $parent
   *   The parent node.
   *
   * @return int[]
   *   The right and depth postiions.
   */
  protected function getInsertionPosition(Node $parent) {
    if ($this->isLeaf($parent)) {
      // We are on a leaf node.
      $right = $parent->getLeft();
      $depth = $parent->getDepth() + 1;
    }
    else {
      // Find right most child.
      /** @var Node $rightChild */
      $rightChild = $this->findRightMostChild($parent);
      $right = $rightChild->getRight();
      $depth = $rightChild->getDepth();
    }
    return [$right, $depth];
  }

}
