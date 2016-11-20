<?php

namespace PNX\Tree\Tests\Functional;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use PNX\Tree\Storage\DbalTree;

/**
 * Tests the Dbal Tree implementation.
 *
 * @group tree
 */
class DbalTreeTest extends \PHPUnit_Framework_TestCase {

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
  public function testFindDescendents() {

    $tree = new DbalTree($this->connection);

    $leaf = $tree->getLeaf(7, 1);

    $descendants = $tree->findDescendants($leaf);

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
   * Tests finding ancestors.
   */
  public function testFindAncestors() {
    $tree = new DbalTree($this->connection);

    $leaf = $tree->getLeaf(7, 1);

    $ancestors = $tree->findAncestors($leaf);

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
   * Creates the table.
   */
  protected function createTable() {
    $schema = new Schema();
    $tree = $schema->createTable("tree");
    $tree->addColumn("id", "integer", ["unsigned" => TRUE]);
    $tree->addColumn("revision_id", "integer", ["unsigned" => TRUE]);
    $tree->addColumn("nested_left", "integer", ["unsigned" => TRUE]);
    $tree->addColumn("nested_right", "integer", ["unsigned" => TRUE]);

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
      ]);
    $this->connection->insert('tree',
      [
        'id' => 2,
        'revision_id' => 1,
        'nested_left' => 1,
        'nested_right' => 9,
      ]);
    $this->connection->insert('tree',
      [
        'id' => 3,
        'revision_id' => 1,
        'nested_left' => 10,
        'nested_right' => 21,
      ]);
    $this->connection->insert('tree',
      [
        'id' => 4,
        'revision_id' => 1,
        'nested_left' => 3,
        'nested_right' => 8,
      ]);
    $this->connection->insert('tree',
      [
        'id' => 5,
        'revision_id' => 1,
        'nested_left' => 4,
        'nested_right' => 5,
      ]);
    $this->connection->insert('tree',
      [
        'id' => 6,
        'revision_id' => 1,
        'nested_left' => 6,
        'nested_right' => 7,
      ]);
    $this->connection->insert('tree',
      [
        'id' => 7,
        'revision_id' => 1,
        'nested_left' => 11,
        'nested_right' => 16,
      ]);
    $this->connection->insert('tree',
      [
        'id' => 8,
        'revision_id' => 1,
        'nested_left' => 17,
        'nested_right' => 18,
      ]);
    $this->connection->insert('tree',
      [
        'id' => 9,
        'revision_id' => 1,
        'nested_left' => 19,
        'nested_right' => 20,
      ]);
    $this->connection->insert('tree',
      [
        'id' => 10,
        'revision_id' => 1,
        'nested_left' => 12,
        'nested_right' => 13,
      ]);
    $this->connection->insert('tree',
      [
        'id' => 11,
        'revision_id' => 1,
        'nested_left' => 14,
        'nested_right' => 15,
      ]
    );
  }

}
