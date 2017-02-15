# Nested Set

A PHP Doctrine DBAL implementation for Nested Sets.

[![CircleCI](https://circleci.com/gh/previousnext/nested-set.svg?style=svg)](https://circleci.com/gh/previousnext/nested-set)

## Using

### Create table schema

Create the table schema, passing in a a [DBAL connection](http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html#getting-a-connection) and table name (defaults to 'tree').
```php
$schema = new DbalNestedSetSchema($connection, 'my_tree');
schema->create();
```

### Set up the nested set client

Create a new `DbalNestedSet` passing in the DBAL connection and the table name.

```php
$nestedSet = new DbalNestedSet($connection, 'my_tree');
```

### Add a root node

A NodeKey represents a unique ID for a node in the tree. It supports the idea of a node ID and a revision ID, mostly for compatibility with Drupal.

```php
$nodeKey = new NodeKey($id, $revisionId);
$rootNode = $nestedSet->addRootNode($nodeKey);
```

### Add a child node

To add a child node, you provide the parent node, and a child node key.

```php
$nodeKey = new NodeKey($id, $revisionId);
$nestedSet->addNodeBelow($rootNode, $nodeKey);
```

### Find Descendants

To find descendents, you provide the parent node key.

```php
$nodeKey = new NodeKey($id, $revisionId);
$descendants = $this->nestedSet->findDescendants($nodeKey);
```

### Find ancestors

To find ancestors, you provide the child node key.

```php
$nodeKey = new NodeKey($id, $revisionId);
$ancestors = $this->nestedSet->findAncestors($nodeKey);
```

See `\PNX\NestedSet\NestedSetInterface` for many more methods that can be used for interacting with the nested set.

## Developing

### Dependencies

To install all dependencies, run:

```
make init
```

### Linting

Uses the Drupal coding standard.

To validate code sniffs run: 

```
make lint-php
```

To automatically fix code sniff issues, run:

```
make fix-php
```


### Testing

To run all phpunit tests, run:

```
make test
```
