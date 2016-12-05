<?php

namespace PNX\NestedSet\Storage;

use Doctrine\DBAL\Connection;
use Exception;
use PNX\NestedSet\Node;
use PNX\NestedSet\NestedSetInterface;

/**
 * Provides a DBAL implementation of a Tree.
 */
class DbalNestedSet extends BaseDbalStorage implements NestedSetInterface {

  /**
   * {@inheritdoc}
   */
  public function addRootNode(Node $node) {
    $maxRight = $this->connection->fetchColumn('SELECT MAX(right_pos) FROM ' . $this->tableName);
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
      $this->connection->executeUpdate('UPDATE ' . $this->tableName . ' SET right_pos = right_pos + 2 WHERE right_pos >= ?',
        [$newLeftPosition]
      );
      $this->connection->executeUpdate('UPDATE ' . $this->tableName . ' SET left_pos = left_pos + 2  WHERE left_pos >= ?',
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
    $this->connection->insert($this->tableName, [
      'id' => $newNode->getId(),
      'revision_id' => $newNode->getRevisionId(),
      'left_pos' => $newNode->getLeft(),
      'right_pos' => $newNode->getRight(),
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
    $query->select('child.id', 'child.revision_id', 'child.left_pos', 'child.right_pos', 'child.depth')
      ->from($this->tableName, 'child')
      ->from($this->tableName, 'parent')
      ->where('child.left_pos > parent.left_pos')
      ->andWhere('child.right_pos < parent.right_pos')
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
      $descendants[] = new Node($row['id'], $row['revision_id'], $row['left_pos'], $row['right_pos'], $row['depth']);
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
    $result = $this->connection->fetchAssoc("SELECT id, revision_id, left_pos, right_pos, depth FROM " . $this->tableName . " WHERE id = ? AND revision_id = ?",
      [$id, $revision_id]
    );
    if ($result) {
      return new Node($id, $revision_id, $result['left_pos'], $result['right_pos'], $result['depth']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function findAncestors(Node $node) {
    $ancestors = [];
    $stmt = $this->connection->executeQuery('SELECT parent.id, parent.revision_id, parent.left_pos, parent.right_pos, parent.depth FROM ' . $this->tableName . ' AS child, ' . $this->tableName . ' AS parent WHERE child.left_pos BETWEEN parent.left_pos AND parent.right_pos AND child.id = ? AND child.revision_id = ? ORDER BY parent.left_pos',
      [$node->getId(), $node->getRevisionId()]
    );
    while ($row = $stmt->fetch()) {
      $ancestors[] = new Node($row['id'], $row['revision_id'], $row['left_pos'], $row['right_pos'], $row['depth']);
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
    $stmt = $this->connection->executeQuery('SELECT id, revision_id, left_pos, right_pos, depth FROM ' . $this->tableName . ' ORDER BY left_pos');
    while ($row = $stmt->fetch()) {
      $tree[] = new Node($row['id'], $row['revision_id'], $row['left_pos'], $row['right_pos'], $row['depth']);
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
      $this->connection->executeUpdate('DELETE FROM ' . $this->tableName . ' WHERE left_pos = ?',
        [$left]
      );

      // Move children up a level.
      $this->connection->executeUpdate('UPDATE ' . $this->tableName . ' SET right_pos = right_pos - 1, left_pos = left_pos - 1, depth = depth -1 WHERE left_pos BETWEEN ? AND ?',
        [$left, $right]
      );

      // Move everything back two places.
      $this->connection->executeUpdate('UPDATE ' . $this->tableName . ' SET right_pos = right_pos - 2 WHERE right_pos > ?',
        [$right]
      );
      $this->connection->executeUpdate('UPDATE ' . $this->tableName . ' SET left_pos = left_pos - 2 WHERE left_pos > ?',
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
      $this->connection->executeUpdate('DELETE FROM ' . $this->tableName . ' WHERE left_pos BETWEEN ? AND ?',
        [$left, $right]
      );

      // Move everything back two places.
      $this->connection->executeUpdate('UPDATE ' . $this->tableName . ' SET right_pos = right_pos - ? WHERE right_pos > ?',
        [$width, $right]
      );
      $this->connection->executeUpdate('UPDATE ' . $this->tableName . ' SET left_pos = left_pos - ?  WHERE left_pos > ?',
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
      $this->connection->executeUpdate('UPDATE ' . $this->tableName . ' SET left_pos = left_pos + ? WHERE left_pos >= ?',
        [$width, $newLeftPosition]
      );

      $this->connection->executeUpdate('UPDATE ' . $this->tableName . ' SET right_pos = right_pos + ? WHERE right_pos >= ?',
        [$width, $newLeftPosition]
      );

      // Move subtree into new space.
      $this->connection->executeUpdate('UPDATE ' . $this->tableName . ' SET left_pos = left_pos + ?, right_pos = right_pos + ?, depth = depth + ? WHERE left_pos >= ? AND right_pos < ?',
        [$distance, $distance, $depthDiff, $tempPos, $tempPos + $width]
      );

      // Remove old space vacated by subtree.
      $this->connection->executeUpdate('UPDATE ' . $this->tableName . ' SET  left_pos = left_pos - ? WHERE left_pos > ?',
        [$width, $node->getRight()]
      );

      $this->connection->executeUpdate('UPDATE ' . $this->tableName . ' SET right_pos = right_pos - ? WHERE right_pos > ?',
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
    $result = $this->connection->fetchAssoc("SELECT id, revision_id, left_pos, right_pos, depth FROM " . $this->tableName . " WHERE left_pos = ?",
      [$left]
    );
    if ($result) {
      return new Node($result['id'], $result['revision_id'], $result['left_pos'], $result['right_pos'], $result['depth']);
    }
  }

}
