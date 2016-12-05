<?php

/**
 * @file
 * Schema for Tree table.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;

$config = new Configuration();
$connectionParams = array(
  'url' => 'sqlite:///tree.sqlite',
);
$conn = DriverManager::getConnection($connectionParams, $config);

$sm = $conn->getSchemaManager();
$schema = $sm->createSchema();

$tree = $schema->createTable("tree");
$tree->addColumn("id", "integer", ["unsigned" => TRUE]);
$tree->addColumn("revision_id", "integer", ["unsigned" => TRUE]);
$tree->addColumn("left_pos", "integer", ["unsigned" => TRUE]);
$tree->addColumn("right_pos", "integer", ["unsigned" => TRUE]);

foreach ($schema->toSql($this->connection->getDatabasePlatform()) as $sql) {
  $this->connection->exec($sql);
}
