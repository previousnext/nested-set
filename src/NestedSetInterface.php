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
   * @param \PNX\NestedSet\NodeKey $nodeKey
   *   The node to insert.
   *
   * @return \PNX\NestedSet\Node
   *   Returns a new node with position values set.
   */
  public function addNodeBelow(Node $target, NodeKey $nodeKey): Node;

  /**
   * Inserts a node before the target node.
   *
   * @param \PNX\NestedSet\Node $target
   *   The target node to insert before.
   * @param \PNX\NestedSet\NodeKey $nodeKey
   *   The node key to insert.
   *
   * @return \PNX\NestedSet\Node
   *   Returns a node with position values set.
   */
  public function addNodeBefore(Node $target, NodeKey $nodeKey): Node;

  /**
   * Inserts a node after the target node.
   *
   * @param \PNX\NestedSet\Node $target
   *   The target node to insert after.
   * @param \PNX\NestedSet\NodeKey $nodeKey
   *   The node key to insert.
   *
   * @return \PNX\NestedSet\Node
   *   Returns a node with position values set.
   */
  public function addNodeAfter(Node $target, NodeKey $nodeKey): Node;

  /**
   * Inserts a root node.
   *
   * @param \PNX\NestedSet\NodeKey $nodeKey
   *   The node key to insert.
   *
   * @return \PNX\NestedSet\Node
   *   A new node with position values set.
   */
  public function addRootNode(NodeKey $nodeKey): Node;

  /**
   * Deletes a node and moves descendants up a level.
   *
   * @param \PNX\NestedSet\Node $node
   *   The node to delete.
   */
  public function deleteNode(Node $node): void;

  /**
   * Deletes a node and all it's descendants.
   *
   * @param \PNX\NestedSet\Node $node
   *   The node to delete.
   */
  public function deleteSubTree(Node $node): void;

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
   * @return \PNX\NestedSet\Node[]
   *   The nested array of descendants.
   */
  public function findDescendants(NodeKey $nodeKey, int $depth = 0, int $start = 1): array;

  /**
   * Finds all immediate children of a node.
   *
   * @param \PNX\NestedSet\NodeKey $nodeKey
   *   The node key to find children for.
   *
   * @return \PNX\NestedSet\Node[]
   *   The children.
   */
  public function findChildren(NodeKey $nodeKey): array;

  /**
   * Finds all ancestors of a node.
   *
   * @param \PNX\NestedSet\NodeKey $nodeKey
   *   The node to find ancestors for.
   *
   * @return \PNX\NestedSet\Node[]
   *   The ancestors.
   */
  public function findAncestors(NodeKey $nodeKey): array;

  /**
   * Finds the parent node.
   *
   * @param \PNX\NestedSet\NodeKey $nodeKey
   *   The node key.
   *
   * @return \PNX\NestedSet\Node|null
   *   The parent node.
   */
  public function findParent(NodeKey $nodeKey): ?Node;

  /**
   * Gets a node for the ID and Revision ID.
   *
   * @param NodeKey $nodeKey
   *   The node key.
   *
   * @return \PNX\NestedSet\Node|null
   *   The node.
   */
  public function getNode(NodeKey $nodeKey): ?Node;

  /**
   * Moves a subtree to be a new root of the tree.
   *
   * @param \PNX\NestedSet\Node $node
   *   The node to become the new root node.
   */
  public function moveSubTreeToRoot(Node $node): void;

  /**
   * Moves a node and its sub-tree below the target node.
   *
   * @param Node $target
   *   The node to move below.
   * @param \PNX\NestedSet\Node $node
   *   The node to move.
   */
  public function moveSubTreeBelow(Node $target, Node $node): void;

  /**
   * Moves a node and its sub-tree before the target node.
   *
   * @param Node $target
   *   The node to move before.
   * @param \PNX\NestedSet\Node $node
   *   The node to move.
   */
  public function moveSubTreeBefore(Node $target, Node $node): void;

  /**
   * Moves a node and its sub-tree after the target node.
   *
   * @param Node $target
   *   The node to move after.
   * @param \PNX\NestedSet\Node $node
   *   The node to move.
   */
  public function moveSubTreeAfter(Node $target, Node $node): void;

  /**
   * Swaps the parent of a sub-tree to a new parent.
   *
   * @param \PNX\NestedSet\Node $oldParent
   *   The old parent.
   * @param \PNX\NestedSet\Node $newParent
   *   The new parent.
   */
  public function adoptChildren(Node $oldParent, Node $newParent): void;

  /**
   * Gets a node at a specified left position.
   *
   * @param int $left
   *   The left position.
   *
   * @return Node
   *   The node.
   */
  public function getNodeAtPosition(int $left): ?Node;

  /**
   * Fetches the entire tree.
   *
   * @return \PNX\NestedSet\Node[]
   *   The tree.
   */
  public function getTree(): array;

  /**
   * Finds the root node for this node.
   *
   * @param \PNX\NestedSet\NodeKey $nodeKey
   *   The node key.
   *
   * @return \PNX\NestedSet\Node|null
   *   The root node.
   */
  public function findRoot(NodeKey $nodeKey): ?Node;

}
