<?php

namespace PNX\Tree;

/**
 * Provides a tree implementation.
 */
interface NestedSetInterface {

  /**
   * Adds a child to the parent.
   *
   * @param \PNX\Tree\Leaf $parent
   *   The parent.
   * @param \PNX\Tree\Leaf $child
   *   The child.
   *
   * @return \PNX\Tree\Leaf
   *   Returns a new child leaf with left and right.
   */
  public function addLeaf(Leaf $parent, Leaf $child);

  /**
   * Deletes a leaf and moves descendants up a level.
   *
   * @param \PNX\Tree\Leaf $leaf
   *   The leaf to delete.
   */
  public function deleteLeaf(Leaf $leaf);

  /**
   * Deletes a leaf and all it's descendants.
   *
   * @param \PNX\Tree\Leaf $leaf
   *   The leaf to delete.
   */
  public function deleteSubTree(Leaf $leaf);

  /**
   * Finds all descendants of a leaf.
   *
   * @param \PNX\Tree\Leaf $leaf
   *   The leaf.
   * @param int $depth
   *   (optional) A depth limit. Defaults to 0, no limit.
   *
   * @return array
   *   The nested array of descendants.
   */
  public function findDescendants(Leaf $leaf, $depth = 0);

  /**
   * Finds all ancestors of a leaf.
   *
   * @param \PNX\Tree\Leaf $leaf
   *   The leaf.
   *
   * @return array
   *   The ancestors.
   */
  public function findAncestors(Leaf $leaf);

  /**
   * Gets a leaf for the ID and Revision ID.
   *
   * @param int|string $id
   *   The ID.
   * @param int|string $revision_id
   *   The revision ID.
   *
   * @return \PNX\Tree\Leaf
   *   The leaf.
   */
  public function getLeaf($id, $revision_id);

  /**
   * Moves a Leaf and its sub-tree below the target leaf.
   *
   * @param Leaf $target
   *   The leaf to move below.
   * @param \PNX\Tree\Leaf $leaf
   *   The leaf to move.
   */
  public function moveSubTreeBelow(Leaf $target, Leaf $leaf);

  /**
   * Moves a Leaf and its sub-tree before the target leaf.
   *
   * @param Leaf $target
   *   The leaf to move before.
   * @param \PNX\Tree\Leaf $leaf
   *   The leaf to move.
   */
  public function moveSubTreeBefore(Leaf $target, Leaf $leaf);

  /**
   * Moves a Leaf and its sub-tree after the target leaf.
   *
   * @param Leaf $target
   *   The leaf to move after.
   * @param \PNX\Tree\Leaf $leaf
   *   The leaf to move.
   */
  public function moveSubTreeAfter(Leaf $target, Leaf $leaf);

  /**
   * Gets a leaf at a specified left position.
   *
   * @param int $left
   *   The left position.
   *
   * @return Leaf
   *   The leaf.
   */
  public function getLeafAtPosition($left);

}
