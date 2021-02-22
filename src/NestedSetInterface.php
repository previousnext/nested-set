<?php

namespace PNX\NestedSet;

/**
 * Provides a tree implementation.
 */
interface NestedSetInterface {

  /**
   * Inserts a node below the target node.
   *
   * @param \PNX\NestedSet\NodeKey $parent
   *   The target node to insert below.
   * @param \PNX\NestedSet\NodeKey $child
   *   The node to insert.
   *
   * @return \PNX\NestedSet\Node
   *   Returns a new node with position values set.
   */
  public function addNodeBelow(NodeKey $parent, NodeKey $child);

  /**
   * Inserts a node before the target node.
   *
   * @param \PNX\NestedSet\NodeKey $targetKey
   *   The target node to insert before.
   * @param \PNX\NestedSet\NodeKey $nodeKey
   *   The node key to insert.
   *
   * @return \PNX\NestedSet\Node
   *   Returns a node with position values set.
   */
  public function addNodeBefore(NodeKey $targetKey, NodeKey $nodeKey);

  /**
   * Inserts a node after the target node.
   *
   * @param \PNX\NestedSet\NodeKey $targetKey
   *   The target node to insert after.
   * @param \PNX\NestedSet\NodeKey $nodeKey
   *   The node key to insert.
   *
   * @return \PNX\NestedSet\Node
   *   Returns a node with position values set.
   */
  public function addNodeAfter(NodeKey $targetKey, NodeKey $nodeKey);

  /**
   * Inserts a root node.
   *
   * @param \PNX\NestedSet\NodeKey $nodeKey
   *   The node key to insert.
   *
   * @return \PNX\NestedSet\Node
   *   A new node with position values set.
   */
  public function addRootNode(NodeKey $nodeKey);

  /**
   * Deletes a node and moves descendants up a level.
   *
   * @param \PNX\NestedSet\NodeKey $nodeKey
   *   The node to delete.
   */
  public function deleteNode(NodeKey $nodeKey);

  /**
   * Deletes a node and all it's descendants.
   *
   * @param \PNX\NestedSet\NodeKey $nodeKey
   *   The node to delete.
   */
  public function deleteSubTree(NodeKey $nodeKey);

  /**
   * Finds all descendants of a node.
   *
   * @param \PNX\NestedSet\NodeKey $nodeKey
   *   The node key to find descendants for.
   * @param int $depth
   *   (optional) A depth limit. Defaults to 0, no limit.
   * @param int $start
   *   (optional) A starting depth. Default of 1 excludes the node itself.
   *
   * @return array
   *   The nested array of descendants.
   */
  public function findDescendants(NodeKey $nodeKey, $depth = 0, $start = 1);

  /**
   * Finds all immediate children of a node.
   *
   * @param \PNX\NestedSet\NodeKey $nodeKey
   *   The node key to find children for.
   *
   * @return array
   *   The children.
   */
  public function findChildren(NodeKey $nodeKey);

  /**
   * Finds all ancestors of a node.
   *
   * @param \PNX\NestedSet\NodeKey $nodeKey
   *   The node to find ancestors for.
   *
   * @return array
   *   The ancestors.
   */
  public function findAncestors(NodeKey $nodeKey);

  /**
   * Finds the parent node.
   *
   * @param \PNX\NestedSet\NodeKey $nodeKey
   *   The node key.
   *
   * @return \PNX\NestedSet\Node
   *   The parent node.
   */
  public function findParent(NodeKey $nodeKey);

  /**
   * Gets a node for the ID and Revision ID.
   *
   * @param \PNX\NestedSet\NodeKey $nodeKey
   *   The node key.
   *
   * @return \PNX\NestedSet\Node
   *   The node.
   */
  public function getNode(NodeKey $nodeKey);

  /**
   * Moves a subtree to be a new root of the tree.
   *
   * @param \PNX\NestedSet\NodeKey $nodeKey
   *   The node to become the new root node.
   */
  public function moveSubTreeToRoot(NodeKey $nodeKey);

  /**
   * Moves a node and its sub-tree below the target node.
   *
   * @param \PNX\NestedSet\NodeKey $targetKey
   *   The node to move below.
   * @param \PNX\NestedSet\NodeKey $nodeKey
   *   The node to move.
   */
  public function moveSubTreeBelow(NodeKey $targetKey, NodeKey $nodeKey);

  /**
   * Moves a node and its sub-tree before the target node.
   *
   * @param \PNX\NestedSet\NodeKey $targetKey
   *   The node to move before.
   * @param \PNX\NestedSet\NodeKey $nodeKey
   *   The node to move.
   */
  public function moveSubTreeBefore(NodeKey $targetKey, NodeKey $nodeKey);

  /**
   * Moves a node and its sub-tree after the target node.
   *
   * @param \PNX\NestedSet\NodeKey $targetKey
   *   The node to move after.
   * @param \PNX\NestedSet\NodeKey $nodeKey
   *   The node to move.
   */
  public function moveSubTreeAfter(NodeKey $targetKey, NodeKey $nodeKey);

  /**
   * Swaps the parent of a sub-tree to a new parent.
   *
   * @param \PNX\NestedSet\NodeKey $oldParentKey
   *   The old parent.
   * @param \PNX\NestedSet\NodeKey $newParentKey
   *   The new parent.
   */
  public function adoptChildren(NodeKey $oldParentKey, NodeKey $newParentKey);

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

  /**
   * Finds the root node for this node.
   *
   * @param \PNX\NestedSet\NodeKey $nodeKey
   *   The node key.
   *
   * @return \PNX\NestedSet\Node
   *   The root node.
   */
  public function findRoot(NodeKey $nodeKey);

}
