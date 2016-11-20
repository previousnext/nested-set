<?php

namespace PNX\Tree;

/**
 * Provides a tree implementation.
 */
interface TreeInterface {

  /**
   * Adds a child to the parent.
   *
   * @param \PNX\Tree\Leaf $parent
   *   The parent.
   * @param \PNX\Tree\Leaf $child
   *   The child.
   */
  public function addLeaf(Leaf $parent, Leaf $child);

  /**
   * Finds all descendants of a leaf.
   *
   * @param \PNX\Tree\Leaf $leaf
   *   The leaf.
   *
   * @return array
   *   The nested array of descendants.
   */
  public function findDescendants(Leaf $leaf);

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
   * Gets the parent of this leaf.
   *
   * @param \PNX\Tree\Leaf $leaf
   *   The current leaf.
   *
   * @return \PNX\Tree\Leaf
   *   The parent leaf.
   */
  public function getParent(Leaf $leaf);

  /**
   * Gets a leaf with for the ID and Revision ID.
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

}
