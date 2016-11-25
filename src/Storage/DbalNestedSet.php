<?php

namespace PNX\NestedSet\Storage;

use Doctrine\DBAL\Connection;
use Exception;
use PNX\NestedSet\Node;
use PNX\NestedSet\NestedSetInterface;

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
  public function addRootNode(Node $node) {
    $maxRight = $this->connection->fetchColumn('SELECT MAX(nested_right) FROM tree');
    if ($maxRight === FALSE) {
      $maxRight = 0;
    }
    return $this->doInsertNode($node->getId(), $node->getRevisionId(), $maxRight + 1, $maxRight + 2, 0);
  }

  /**
   * {@inheritdoc}
   */
  public function addNodeBelow(Node $target, Node $node) {
    $newLeftPosition = $target->getRight();
    $depth = $target->getDepth() + 1;
    return $this->insertNodeAtPostion($newLeftPosition, $depth, $node);
  }

  /**
   * {@inheritdoc}
   */
  public function addNodeBefore(Node $target, Node $node) {
    $newLeftPosition = $target->getLeft();
    $depth = $target->getDepth();
    return $this->insertNodeAtPostion($newLeftPosition, $depth, $node);
  }

  /**
   * {@inheritdoc}
   */
  public function addNodeAfter(Node $target, Node $node) {
    $newLeftPosition = $target->getRight() + 1;
    $depth = $target->getDepth();
    return $this->insertNodeAtPostion($newLeftPosition, $depth, $node);
  }

  /**
   * Inserts a node to the target position.
   *
   * @param int $newLeftPosition
   *   The new left position.
   * @param int $depth
   *   The new depth.
   * @param \PNX\NestedSet\Node $node
   *   The node to insert.
   *
   * @return \PNX\NestedSet\Node
   *   The new node with updated position.
   *
   * @throws \Exception
   *   If a transaction error occurs.
   */
  protected function insertNodeAtPostion($newLeftPosition, $depth, Node $node) {

    try {
      $this->connection->beginTransaction();

      // Make space for inserting node.
      $this->connection->executeUpdate('UPDATE tree SET nested_right = nested_right + 2 WHERE nested_right >= ?',
        [$newLeftPosition]
      );
      $this->connection->executeUpdate('UPDATE tree SET nested_left = nested_left + 2  WHERE nested_left >= ?',
        [$newLeftPosition]
      );

      // Insert the node.
      $newNode = $this->doInsertNode($node->getId(), $node->getRevisionId(), $newLeftPosition, $newLeftPosition + 1, $depth);

      $this->connection->commit();
    }
    catch (Exception $e) {
      $this->connection->rollBack();
      throw $e;
    }
    return $newNode;

  }

  /**
   * Inserts a new node by its parameters.
   *
   * @param int|string $id
   *   The node ID.
   * @param int|string $revisionId
   *   The node revision ID.
   * @param int $left
   *   The left position.
   * @param int $right
   *   The right position.
   * @param int $depth
   *   The depth.
   *
   * @return \PNX\NestedSet\Node
   *   The new node.
   */
  protected function doInsertNode($id, $revisionId, $left, $right, $depth) {
    // Create a new node object to be returned.
    $newNode = new Node($id, $revisionId, $left, $right, $depth);

    // Insert the new node.
    $this->connection->insert('tree', [
      'id' => $newNode->getId(),
      'revision_id' => $newNode->getRevisionId(),
      'nested_left' => $newNode->getLeft(),
      'nested_right' => $newNode->getRight(),
      'depth' => $newNode->getDepth(),
    ]);

    return $newNode;
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
  public function findChildren(Node $node) {
    // Only find descendants one level deep.
    return $this->findDescendants($node, 1);
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
   * Finds the root node for this node.
   *
   * @param \PNX\NestedSet\Node $node
   *   The start node.
   *
   * @return \PNX\NestedSet\Node
   *   The root node.
   */
  public function findRoot(Node $node) {
    $ancestors = $this->findAncestors($node);
    if (!empty($ancestors)) {
      return array_shift($ancestors);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function findParent(Node $node) {
    $ancestors = $this->findAncestors($node);
    if (count($ancestors) > 1) {
      // Parent is 2nd-last element.
      return $ancestors[count($ancestors) - 2];
    }
    return NULL;
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
   * @param \PNX\NestedSet\Node $node
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

}
