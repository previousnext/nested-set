<?php

namespace PNX\Tree\Tests\Functional;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use PNX\Tree\Leaf;
use PNX\Tree\Storage\DbalNestedSet;

/**
 * Tests the Dbal Tree implementation.
 *
 * @group tree
 */
class DbalNestedSetTest extends \PHPUnit_Framework_TestCase {

  /**
   * The database connection.
   *
   * @var \Doctrine\DBAL\Connection
   */
  protected $connection;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    if ($this->connection === NULL) {
      $this->connection = DriverManager::getConnection([
        'url' => 'sqlite:///:memory:',
      ], new Configuration());
      $this->createTable();
      $this->loadTestData();
    }
  }

  /**
   * Tests finding descendants.
   */
  public function testFindDescendants() {

    $nestedSet = new DbalNestedSet($this->connection);

    $leaf = $nestedSet->getLeaf(7, 1);

    $descendants = $nestedSet->findDescendants($leaf);

    $this->assertCount(2, $descendants);

    /** @var Leaf $child1 */
    $child1 = $descendants[0];

    $this->assertEquals(10, $child1->getId());
    $this->assertEquals(1, $child1->getRevisionId());
    $this->assertEquals(12, $child1->getLeft());
    $this->assertEquals(13, $child1->getRight());

    /** @var Leaf $child2 */
    $child2 = $descendants[1];

    $this->assertEquals(11, $child2->getId());
    $this->assertEquals(1, $child2->getRevisionId());
    $this->assertEquals(14, $child2->getLeft());
    $this->assertEquals(15, $child2->getRight());

  }

  /**
   * Tests finding descendants with depth.
   */
  public function testFindDescendantsWithDepth() {

    $nestedSet = new DbalNestedSet($this->connection);

    $leaf = $nestedSet->getLeaf(3, 1);

    // Limit to 1 level deep to exclude grandchildren.
    $descendants = $nestedSet->findDescendants($leaf, 1);

    $this->assertCount(3, $descendants);

    /** @var Leaf $child1 */
    $child1 = $descendants[0];

    $this->assertEquals(7, $child1->getId());
    $this->assertEquals(1, $child1->getRevisionId());
    $this->assertEquals(11, $child1->getLeft());
    $this->assertEquals(16, $child1->getRight());
    $this->assertEquals(2, $child1->getDepth());

    /** @var Leaf $child2 */
    $child2 = $descendants[1];

    $this->assertEquals(8, $child2->getId());
    $this->assertEquals(1, $child2->getRevisionId());
    $this->assertEquals(17, $child2->getLeft());
    $this->assertEquals(18, $child2->getRight());
    $this->assertEquals(2, $child2->getDepth());

    /** @var Leaf $child3 */
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
    $nestedSet = new DbalNestedSet($this->connection);

    $leaf = $nestedSet->getLeaf(7, 1);

    $ancestors = $nestedSet->findAncestors($leaf);

    $this->assertCount(3, $ancestors);

    /* @var Leaf $parent */
    $parent = $ancestors[1];

    $this->assertEquals(3, $parent->getId());
    $this->assertEquals(1, $parent->getRevisionId());
    $this->assertEquals(10, $parent->getLeft());
    $this->assertEquals(21, $parent->getRight());

    /* @var Leaf $grandparent */
    $grandparent = $ancestors[0];

    $this->assertEquals(1, $grandparent->getId());
    $this->assertEquals(1, $grandparent->getRevisionId());
    $this->assertEquals(1, $grandparent->getLeft());
    $this->assertEquals(22, $grandparent->getRight());

  }

  /**
   * Tests adding a leaf.
   */
  public function testAddLeafWithExistingChildren() {
    $nestedSet = new DbalNestedSet($this->connection);

    $parent = $nestedSet->getLeaf(3, 1);
    $child = new Leaf(12, 1);

    $newLeaf = $nestedSet->addLeaf($parent, $child);

    // Should be inserted in right-most spot.
    $this->assertEquals(21, $newLeaf->getLeft());
    $this->assertEquals(22, $newLeaf->getRight());

    $tree = $nestedSet->getTree();

    // Parent leaf right should have incremented.
    $newParent = $nestedSet->getLeaf(3, 1);
    $this->assertEquals(10, $newParent->getLeft());
    $this->assertEquals(23, $newParent->getRight());
  }

  /**
   * Tests adding a leaf.
   */
  public function testAddLeafWithNoChildren() {
    $nestedSet = new DbalNestedSet($this->connection);

    $parent = $nestedSet->getLeaf(6, 1);
    $child = new Leaf(13, 1);

    $newLeaf = $nestedSet->addLeaf($parent, $child);

    // Should be inserted below 6 with depth 4.
    $this->assertEquals(7, $newLeaf->getLeft());
    $this->assertEquals(8, $newLeaf->getRight());
    $this->assertEquals(4, $newLeaf->getDepth());

    $tree = $nestedSet->getTree();

    // Parent leaf right should have incremented.
    $newParent = $nestedSet->getLeaf(6, 1);
    $this->assertEquals(6, $newParent->getLeft());
    $this->assertEquals(9, $newParent->getRight());
  }

  /**
   * Creates the table.
   */
  protected function createTable() {
    $schema = new Schema();
    $tree = $schema->createTable("tree");
    $tree->addColumn("id", "integer", ["unsigned" => TRUE]);
    $tree->addColumn("revision_id", "integer", ["unsigned" => TRUE]);
    $tree->addColumn("nested_left", "integer", ["unsigned" => TRUE]);
    $tree->addColumn("nested_right", "integer", ["unsigned" => TRUE]);
    $tree->addColumn("depth", "integer", ["unsigned" => TRUE]);

    foreach ($schema->toSql($this->connection->getDatabasePlatform()) as $sql) {
      $this->connection->exec($sql);
    }
  }

  /**
   * Loads the test data.
   */
  protected function loadTestData() {
    $this->connection->insert('tree',
      [
        'id' => 1,
        'revision_id' => 1,
        'nested_left' => 1,
        'nested_right' => 22,
        'depth' => 0,
      ]);
    $this->connection->insert('tree',
      [
        'id' => 2,
        'revision_id' => 1,
        'nested_left' => 2,
        'nested_right' => 9,
        'depth' => 1,
      ]);
    $this->connection->insert('tree',
      [
        'id' => 3,
        'revision_id' => 1,
        'nested_left' => 10,
        'nested_right' => 21,
        'depth' => 1,
      ]);
    $this->connection->insert('tree',
      [
        'id' => 4,
        'revision_id' => 1,
        'nested_left' => 3,
        'nested_right' => 8,
        'depth' => 2,
      ]);
    $this->connection->insert('tree',
      [
        'id' => 5,
        'revision_id' => 1,
        'nested_left' => 4,
        'nested_right' => 5,
        'depth' => 3,
      ]);
    $this->connection->insert('tree',
      [
        'id' => 6,
        'revision_id' => 1,
        'nested_left' => 6,
        'nested_right' => 7,
        'depth' => 3,
      ]);
    $this->connection->insert('tree',
      [
        'id' => 7,
        'revision_id' => 1,
        'nested_left' => 11,
        'nested_right' => 16,
        'depth' => 2,
      ]);
    $this->connection->insert('tree',
      [
        'id' => 8,
        'revision_id' => 1,
        'nested_left' => 17,
        'nested_right' => 18,
        'depth' => 2,
      ]);
    $this->connection->insert('tree',
      [
        'id' => 9,
        'revision_id' => 1,
        'nested_left' => 19,
        'nested_right' => 20,
        'depth' => 2,
      ]);
    $this->connection->insert('tree',
      [
        'id' => 10,
        'revision_id' => 1,
        'nested_left' => 12,
        'nested_right' => 13,
        'depth' => 3,
      ]);
    $this->connection->insert('tree',
      [
        'id' => 11,
        'revision_id' => 1,
        'nested_left' => 14,
        'nested_right' => 15,
        'depth' => 3,
      ]
    );
  }

}
