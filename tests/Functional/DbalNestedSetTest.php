<?php

namespace PNX\NestedSet\Tests\Functional;

use Console_Table;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use PNX\NestedSet\Node;
use PNX\NestedSet\Storage\DbalNestedSet;
use PNX\NestedSet\Storage\DbalNestedSetSchema;

/**
 * Tests the Dbal Tree implementation.
 *
 * @group tree
 */
class DbalNestedSetTest extends \PHPUnit_Framework_TestCase {

  /**
   * The nested set under test.
   *
   * @var DbalNestedSet
   */
  protected $nestedSet;

  /**
   * The database connection.
   *
   * @var \Doctrine\DBAL\Connection
   */
  protected $connection;

  /**
   * The table name.
   *
   * @var string
   */
  protected $tableName = 'nested_set';

  /**
   * The nested set schema.
   *
   * @var \PNX\NestedSet\Storage\DbalNestedSetSchema
   */
  protected $schema;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->connection = DriverManager::getConnection([
      'url' => 'sqlite:///:memory:',
    ], new Configuration());

    $this->schema = new DbalNestedSetSchema($this->connection, $this->tableName);
    $this->schema->create();
    $this->loadTestData();
    $this->nestedSet = new DbalNestedSet($this->connection, $this->tableName);
  }

  /**
   * Tests finding descendants.
   */
  public function testFindDescendants() {

    $node = $this->nestedSet->getNode(7, 1);

    $descendants = $this->nestedSet->findDescendants($node);

    $this->assertCount(2, $descendants);

    /** @var Node $child1 */
    $child1 = $descendants[0];

    $this->assertEquals(10, $child1->getId());
    $this->assertEquals(1, $child1->getRevisionId());
    $this->assertEquals(12, $child1->getLeft());
    $this->assertEquals(13, $child1->getRight());

    /** @var Node $child2 */
    $child2 = $descendants[1];

    $this->assertEquals(11, $child2->getId());
    $this->assertEquals(1, $child2->getRevisionId());
    $this->assertEquals(14, $child2->getLeft());
    $this->assertEquals(15, $child2->getRight());

  }

  /**
   * Tests finding children.
   */
  public function testFindChildren() {

    $node = $this->nestedSet->getNode(3, 1);

    // Limit to 1 level deep to exclude grandchildren.
    $descendants = $this->nestedSet->findChildren($node);

    $this->assertCount(3, $descendants);

    /** @var Node $child1 */
    $child1 = $descendants[0];

    $this->assertEquals(7, $child1->getId());
    $this->assertEquals(1, $child1->getRevisionId());
    $this->assertEquals(11, $child1->getLeft());
    $this->assertEquals(16, $child1->getRight());
    $this->assertEquals(2, $child1->getDepth());

    /** @var Node $child2 */
    $child2 = $descendants[1];

    $this->assertEquals(8, $child2->getId());
    $this->assertEquals(1, $child2->getRevisionId());
    $this->assertEquals(17, $child2->getLeft());
    $this->assertEquals(18, $child2->getRight());
    $this->assertEquals(2, $child2->getDepth());

    /** @var Node $child3 */
    $child3 = $descendants[2];

    $this->assertEquals(9, $child3->getId());
    $this->assertEquals(1, $child3->getRevisionId());
    $this->assertEquals(19, $child3->getLeft());
    $this->assertEquals(20, $child3->getRight());
    $this->assertEquals(2, $child3->getDepth());

  }

  /**
   * Tests finding ancestors.
   */
  public function testFindAncestors() {

    $node = $this->nestedSet->getNode(7, 1);

    $ancestors = $this->nestedSet->findAncestors($node);

    $this->assertCount(3, $ancestors);

    /* @var Node $parent */
    $parent = $ancestors[1];

    $this->assertEquals(3, $parent->getId());
    $this->assertEquals(1, $parent->getRevisionId());
    $this->assertEquals(10, $parent->getLeft());
    $this->assertEquals(21, $parent->getRight());

    /* @var Node $grandparent */
    $grandparent = $ancestors[0];

    $this->assertEquals(1, $grandparent->getId());
    $this->assertEquals(1, $grandparent->getRevisionId());
    $this->assertEquals(1, $grandparent->getLeft());
    $this->assertEquals(22, $grandparent->getRight());

  }

  /**
   * Tests finding the parent node.
   */
  public function testFindParent() {

    $node = $this->nestedSet->getNode(7, 1);

    $parent = $this->nestedSet->findParent($node);

    $this->assertEquals(3, $parent->getId());
    $this->assertEquals(1, $parent->getRevisionId());
    $this->assertEquals(10, $parent->getLeft());
    $this->assertEquals(21, $parent->getRight());

  }

  /**
   * Tests inserting a node below a parent with existing children.
   */
  public function testInsertNodeBelowWithExistingChildren() {

    $parent = $this->nestedSet->getNode(3, 1);
    $child = new Node(12, 1);

    $newNode = $this->nestedSet->addNodeBelow($parent, $child);

    // Should be inserted in right-most spot.
    $this->assertEquals(21, $newNode->getLeft());
    $this->assertEquals(22, $newNode->getRight());

    $tree = $this->nestedSet->getTree();

    // Parent node right should have incremented.
    $newParent = $this->nestedSet->getNode(3, 1);
    $this->assertEquals(10, $newParent->getLeft());
    $this->assertEquals(23, $newParent->getRight());
  }

  /**
   * Tests inserting a node below a parent with no children.
   */
  public function testInsertNodeBelowWithNoChildren() {
    $target = $this->nestedSet->getNode(6, 1);
    $node = new Node(13, 1);

    $newNode = $this->nestedSet->addNodeBelow($target, $node);

    // Should be inserted below 6 with depth 4.
    $this->assertEquals(7, $newNode->getLeft());
    $this->assertEquals(8, $newNode->getRight());
    $this->assertEquals(4, $newNode->getDepth());

    // Parent node right should have incremented.
    $newParent = $this->nestedSet->getNode(6, 1);
    $this->assertEquals(6, $newParent->getLeft());
    $this->assertEquals(9, $newParent->getRight());
  }

  /**
   * Tests inserting a node before another sibling.
   */
  public function testInsertNodeBefore() {
    $parent = $this->nestedSet->getNode(6, 1);
    $child = new Node(14, 1);

    $newNode = $this->nestedSet->addNodeBefore($parent, $child);

    // Should be inserted below 6 with depth 4.
    $this->assertEquals(6, $newNode->getLeft());
    $this->assertEquals(7, $newNode->getRight());
    $this->assertEquals(3, $newNode->getDepth());

    // Parent node right should have incremented.
    $newParent = $this->nestedSet->getNode(4, 1);
    $this->assertEquals(3, $newParent->getLeft());
    $this->assertEquals(10, $newParent->getRight());
  }

  /**
   * Tests inserting a node after another sibling.
   */
  public function testInsertNodeAfter() {
    $parent = $this->nestedSet->getNode(5, 1);
    $child = new Node(15, 1);

    $newNode = $this->nestedSet->addNodeAfter($parent, $child);

    // Should be inserted below 6 with depth 4.
    $this->assertEquals(6, $newNode->getLeft());
    $this->assertEquals(7, $newNode->getRight());
    $this->assertEquals(3, $newNode->getDepth());

    // Parent node right should have incremented.
    $newParent = $this->nestedSet->getNode(4, 1);
    $this->assertEquals(3, $newParent->getLeft());
    $this->assertEquals(10, $newParent->getRight());
  }

  /**
   * Tests deleting a node.
   */
  public function testDeleteNode() {
    $node = $this->nestedSet->getNode(4, 1);

    $this->nestedSet->deleteNode($node);

    // Node should be deleted.
    $node = $this->nestedSet->getNode(4, 1);
    $this->assertNull($node);

    // Children should be moved up.
    $node = $this->nestedSet->getNode(5, 1);
    $this->assertEquals(3, $node->getLeft());
    $this->assertEquals(4, $node->getRight());
    $this->assertEquals(2, $node->getDepth());

    $node = $this->nestedSet->getNode(6, 1);
    $this->assertEquals(5, $node->getLeft());
    $this->assertEquals(6, $node->getRight());
    $this->assertEquals(2, $node->getDepth());

    $tree = $this->nestedSet->getTree();
  }

  /**
   * Tests deleting a node with missing values.
   */
  public function testDeleteNodeInvalid() {
    $node = new Node(1, 1);
    $this->setExpectedException(\InvalidArgumentException::class);
    $this->nestedSet->deleteNode($node);
  }

  /**
   * Tests deleting a node and its sub-tree.
   */
  public function testDeleteSubTree() {

    $node = $this->nestedSet->getNode(4, 1);

    $this->nestedSet->deleteSubTree($node);

    // Node should be deleted.
    $node = $this->nestedSet->getNode(4, 1);
    $this->assertNull($node);

    // Children should be deleted.
    $node = $this->nestedSet->getNode(5, 1);
    $this->assertNull($node);

    $node = $this->nestedSet->getNode(6, 1);
    $this->assertNull($node);
  }

  /**
   * Tests moving a sub-tree under a parent node.
   */
  public function testMoveSubTreeBelow() {

    $parent = $this->nestedSet->getNode(1, 1);
    $node = $this->nestedSet->getNode(7, 1);

    $this->nestedSet->moveSubTreeBelow($parent, $node);

    // Check node is in new position.
    $node = $this->nestedSet->getNode(7, 1);
    $this->assertEquals(2, $node->getLeft());
    $this->assertEquals(7, $node->getRight());
    $this->assertEquals(1, $node->getDepth());

    // Check children are in new position.
    $node = $this->nestedSet->getNode(10, 1);
    $this->assertEquals(3, $node->getLeft());
    $this->assertEquals(4, $node->getRight());
    $this->assertEquals(2, $node->getDepth());

    $node = $this->nestedSet->getNode(11, 1);
    $this->assertEquals(5, $node->getLeft());
    $this->assertEquals(6, $node->getRight());
    $this->assertEquals(2, $node->getDepth());

    // Check old parent is updated.
    $node = $this->nestedSet->getNode(3, 1);
    $this->assertEquals(16, $node->getLeft());
    $this->assertEquals(21, $node->getRight());
    $this->assertEquals(1, $node->getDepth());

  }

  /**
   * Tests moving a sub-tree to the root.
   */
  public function testMoveSubTreeToRoot() {

    $node = $this->nestedSet->getNode(7, 1);

    $this->nestedSet->moveSubTreeToRoot($node);

    // Assert we are at the root now.
    $newRoot = $this->nestedSet->getNode(7, 1);
    $this->assertEquals(1, $newRoot->getLeft());
    $this->assertEquals(6, $newRoot->getRight());
    $this->assertEquals(0, $newRoot->getDepth());

    // Assert old root has had left and right updated.
    $oldRoot = $this->nestedSet->getNode(1, 1);
    $this->assertEquals(7, $oldRoot->getLeft());
    $this->assertEquals(22, $oldRoot->getRight());
    $this->assertEquals(0, $oldRoot->getDepth());

  }

  /**
   * Tests moving a sub-tree before a target node.
   */
  public function testMoveSubTreeBefore() {

    $target = $this->nestedSet->getNode(4, 1);
    $node = $this->nestedSet->getNode(7, 1);

    $this->nestedSet->moveSubTreeBefore($target, $node);

    // Check node is in new position.
    $node = $this->nestedSet->getNode(7, 1);
    $this->assertEquals(3, $node->getLeft());
    $this->assertEquals(8, $node->getRight());
    $this->assertEquals(2, $node->getDepth());

    // Check children are in new position.
    $node = $this->nestedSet->getNode(10, 1);
    $this->assertEquals(4, $node->getLeft());
    $this->assertEquals(5, $node->getRight());
    $this->assertEquals(3, $node->getDepth());

    $node = $this->nestedSet->getNode(11, 1);
    $this->assertEquals(6, $node->getLeft());
    $this->assertEquals(7, $node->getRight());
    $this->assertEquals(3, $node->getDepth());

    // Check old parent is updated.
    $node = $this->nestedSet->getNode(3, 1);
    $this->assertEquals(16, $node->getLeft());
    $this->assertEquals(21, $node->getRight());
    $this->assertEquals(1, $node->getDepth());

  }

  /**
   * Tests inserting a root node to an empty tree.
   */
  public function testAddRootNodeWhenEmpty() {

    $rootNode = $this->nestedSet->getNode(1, 1);

    $this->nestedSet->deleteSubTree($rootNode);

    $node = new Node(12, 1);

    $newNode = $this->nestedSet->addRootNode($node);

    $this->assertEquals(1, $newNode->getLeft());
    $this->assertEquals(2, $newNode->getRight());
    $this->assertEquals(0, $newNode->getDepth());
  }

  /**
   * Tests inserting a root node to an existing tree.
   */
  public function testAddRootNode() {
    $node = new Node(12, 1);

    $newNode = $this->nestedSet->addRootNode($node);

    $this->assertEquals(23, $newNode->getLeft());
    $this->assertEquals(24, $newNode->getRight());
    $this->assertEquals(0, $newNode->getDepth());
  }

  /**
   * Test table name validation, max length.
   */
  public function testValidateTableNameTooLong() {
    $this->setExpectedException(\InvalidArgumentException::class);
    $this->nestedSet = new DbalNestedSet($this->connection, "");
  }

  /**
   * Test table name validation, invalid chars.
   */
  public function testValidateTableInvalidChars() {
    $this->setExpectedException(\InvalidArgumentException::class);
    $this->nestedSet = new DbalNestedSet($this->connection, "Robert;)DROP TABLE students;--");
  }

  /**
   * Test table name validation, first char.
   */
  public function testValidateTableInvalidFirstChars() {
    $this->setExpectedException(\InvalidArgumentException::class);
    $this->nestedSet = new DbalNestedSet($this->connection, "1abc");
  }

  /**
   * Loads the test data.
   */
  protected function loadTestData() {
    $this->connection->insert($this->tableName,
      [
        'id' => 1,
        'revision_id' => 1,
        'left_pos' => 1,
        'right_pos' => 22,
        'depth' => 0,
      ]);
    $this->connection->insert($this->tableName,
      [
        'id' => 2,
        'revision_id' => 1,
        'left_pos' => 2,
        'right_pos' => 9,
        'depth' => 1,
      ]);
    $this->connection->insert($this->tableName,
      [
        'id' => 3,
        'revision_id' => 1,
        'left_pos' => 10,
        'right_pos' => 21,
        'depth' => 1,
      ]);
    $this->connection->insert($this->tableName,
      [
        'id' => 4,
        'revision_id' => 1,
        'left_pos' => 3,
        'right_pos' => 8,
        'depth' => 2,
      ]);
    $this->connection->insert($this->tableName,
      [
        'id' => 5,
        'revision_id' => 1,
        'left_pos' => 4,
        'right_pos' => 5,
        'depth' => 3,
      ]);
    $this->connection->insert($this->tableName,
      [
        'id' => 6,
        'revision_id' => 1,
        'left_pos' => 6,
        'right_pos' => 7,
        'depth' => 3,
      ]);
    $this->connection->insert($this->tableName,
      [
        'id' => 8,
        'revision_id' => 1,
        'left_pos' => 17,
        'right_pos' => 18,
        'depth' => 2,
      ]);
    $this->connection->insert($this->tableName,
      [
        'id' => 9,
        'revision_id' => 1,
        'left_pos' => 19,
        'right_pos' => 20,
        'depth' => 2,
      ]);
    $this->connection->insert($this->tableName,
      [
        'id' => 7,
        'revision_id' => 1,
        'left_pos' => 11,
        'right_pos' => 16,
        'depth' => 2,
      ]);
    $this->connection->insert($this->tableName,
      [
        'id' => 10,
        'revision_id' => 1,
        'left_pos' => 12,
        'right_pos' => 13,
        'depth' => 3,
      ]);
    $this->connection->insert($this->tableName,
      [
        'id' => 11,
        'revision_id' => 1,
        'left_pos' => 14,
        'right_pos' => 15,
        'depth' => 3,
      ]
    );
  }

  /**
   * Prints out a tree to the console.
   *
   * @param array $tree
   *   The tree to print.
   */
  public function printTree($tree) {
    $table = new Console_Table(CONSOLE_TABLE_ALIGN_RIGHT);
    $table->setHeaders(['ID', 'Rev', 'Left', 'Right', 'Depth']);
    $table->setAlign(0, CONSOLE_TABLE_ALIGN_LEFT);
    /** @var Node $node */
    foreach ($tree as $node) {
      $indent = str_repeat('-', $node->getDepth());
      $table->addRow([
        $indent . $node->getId(),
        $node->getRevisionId(),
        $node->getLeft(),
        $node->getRight(),
        $node->getDepth(),
      ]);
    }
    echo $table->getTable();
  }

}
