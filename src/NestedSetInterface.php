<?php

namespace PNX\NestedSet;

/**
 * Provides a tree implementation.
 */
interface NestedSetInterface {

  /**
   * Inserts a node below the target node.
   *
   * @param \PNX\NestedSet\Node $target
   *   The target node to insert below.
   * @param \PNX\NestedSet\Node $node
   *   The node to insert. Only id and revision ID are required.
   *
   * @return \PNX\NestedSet\Node
   *   Returns a new node with position values set.
   */
  public function insertNodeBelow(Node $target, Node $node);

  /**
   * Inserts a node before the target node.
   *
   * @param \PNX\NestedSet\Node $target
   *   The target node to insert before.
   * @param \PNX\NestedSet\Node $node
   *   The node to insert. Only id and revision ID are required.
   *
   * @return \PNX\NestedSet\Node
   *   Returns a node with position values set.
   */
  public function insertNodeBefore(Node $target, Node $node);

  /**
   * Inserts a node after the target node.
   *
   * @param \PNX\NestedSet\Node $target
   *   The target node to insert after.
   * @param \PNX\NestedSet\Node $node
   *   The node to insert. Only id and revision ID are required.
   *
   * @return \PNX\NestedSet\Node
   *   Returns a node with position values set.
   */
  public function insertNodeAfter(Node $target, Node $node);

  /**
   * Deletes a node and moves descendants up a level.
   *
   * @param \PNX\NestedSet\Node $node
   *   The node to delete.
   */
  public function deleteNode(Node $node);

  /**
   * Deletes a node and all it's descendants.
   *
   * @param \PNX\NestedSet\Node $node
   *   The node to delete.
   */
  public function deleteSubTree(Node $node);

  /**
   * Finds all descendants of a node.
   *
   * @param \PNX\NestedSet\Node $node
   *   The node.
   * @param int $depth
   *   (optional) A depth limit. Defaults to 0, no limit.
   *
   * @return array
   *   The nested array of descendants.
   */
  public function findDescendants(Node $node, $depth = 0);

  /**
   * Finds all immediate children of a node.
   *
   * @param \PNX\NestedSet\Node $node
   *   The node.
   *
   * @return array
   *   The children.
   */
  public function findChildren(Node $node);

  /**
   * Finds all ancestors of a node.
   *
   * @param \PNX\NestedSet\Node $node
   *   The node.
   *
   * @return array
   *   The ancestors.
   */
  public function findAncestors(Node $node);

  /**
   * Finds the parent node.
   *
   * @param \PNX\NestedSet\Node $node
   *   The node.
   *
   * @return Node
   *   The parent node.
   */
  public function findParent(Node $node);

  /**
   * Gets a node for the ID and Revision ID.
   *
   * @param int|string $id
   *   The ID.
   * @param int|string $revision_id
   *   The revision ID.
   *
   * @return \PNX\NestedSet\Node
   *   The node.
   */
  public function getNode($id, $revision_id);

  /**
   * Moves a node and its sub-tree below the target node.
   *
   * @param Node $target
   *   The node to move below.
   * @param \PNX\NestedSet\Node $node
   *   The node to move.
   */
  public function moveSubTreeBelow(Node $target, Node $node);

  /**
   * Moves a node and its sub-tree before the target node.
   *
   * @param Node $target
   *   The node to move before.
   * @param \PNX\NestedSet\Node $node
   *   The node to move.
   */
  public function moveSubTreeBefore(Node $target, Node $node);

  /**
   * Moves a node and its sub-tree after the target node.
   *
   * @param Node $target
   *   The node to move after.
   * @param \PNX\NestedSet\Node $node
   *   The node to move.
   */
  public function moveSubTreeAfter(Node $target, Node $node);

  /**
   * Gets a node at a specified left position.
   *
   * @param int $left
   *   The left position.
   *
   * @return Node
   *   The node.
   */
  public function getNodeAtPosition($left);

  /**
   * Fetches the entire tree.
   *
   * @return array
   *   The tree.
   */
  public function getTree();

}
