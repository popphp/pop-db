<?php
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db\Record;

use Pop\Db\Db;
use Pop\Db\Gateway;
use Pop\Db\Sql\Parser;
use ArrayIterator;

/**
 * Abstract record class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.6.0
 */
abstract class AbstractRecord implements \ArrayAccess, \Countable, \IteratorAggregate
{

    /**
     * Table name
     * @var ?string
     */
    protected ?string $table = null;

    /**
     * Table prefix
     * @var ?string
     */
    protected ?string $prefix = null;

    /**
     * Primary keys
     * @var array
     */
    protected array $primaryKeys = ['id'];

    /**
     * Row gateway
     * @var ?Gateway\Row
     */
    protected ?Gateway\Row $rowGateway = null;

    /**
     * Table gateway
     * @var ?Gateway\Table
     */
    protected ?Gateway\Table $tableGateway = null;

    /**
     * Is new record flag
     * @var bool
     */
    protected bool $isNew = false;

    /**
     * Is transaction flag
     * @var bool
     */
    protected bool $isTransaction = false;

    /**
     * With relationships
     * @var array
     */
    protected array $with = [];

    /**
     * With relationship options
     * @var array
     */
    protected array $withOptions = [];

    /**
     * With relationship children
     * @var ?string
     */
    protected ?string $withChildren = null;

    /**
     * Relationships
     * @var array
     */
    protected array $relationships = [];

    /**
     * Relationship sort-by field
     * @var string
     */
    protected string $relationshipSortBy = 'id';

    /**
     * Relationship latest flag
     * @var bool
     */
    protected bool $latest = false;

    /**
     * Relationship oldest flag
     * @var bool
     */
    protected bool $oldest = false;

    /**
     * Set the table
     *
     * @param  string $table
     * @return AbstractRecord
     */
    public function setTable(string $table): AbstractRecord
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Set the table from a class name
     *
     * @param  ?string $class
     * @return AbstractRecord
     */
    public function setTableFromClassName(?string $class = null): AbstractRecord
    {
        if ($class === null) {
            $class = get_class($this);
        }

        if (str_contains($class, '_')) {
            $cls = substr($class, (strrpos($class, '_') + 1));
        } else if (str_contains($class, '\\')) {
            $cls = substr($class, (strrpos($class, '\\') + 1));
        } else {
            $cls = $class;
        }

        return $this->setTable(Parser\Table::parse($cls));
    }

    /**
     * Set the table prefix
     *
     * @param  string $prefix
     * @return AbstractRecord
     */
    public function setPrefix(string $prefix): AbstractRecord
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * Set the primary keys
     *
     * @param  array $keys
     * @return AbstractRecord
     */
    public function setPrimaryKeys(array $keys): AbstractRecord
    {
        $this->primaryKeys = $keys;
        return $this;
    }

    /**
     * Start transaction
     *
     * @return AbstractRecord
     */
    public function startTransaction(): AbstractRecord
    {
        $class = get_called_class();
        if (Db::hasDb($class)) {
            Db::db($class)->beginTransaction();
        }
        $this->isTransaction = true;
        return $this;
    }

    /**
     * Is transaction
     *
     * @return bool
     */
    public function isTransaction(): bool
    {
        return $this->isTransaction;
    }

    /**
     * Commit transaction
     *
     * @throws \Pop\Db\Exception
     * @return AbstractRecord
     */
    public function commitTransaction(): AbstractRecord
    {
        $class = get_called_class();
        if (($this->isTransaction) && (Db::hasDb($class))) {
            Db::db($class)->commit();
        }
        $this->isTransaction = false;
        return $this;
    }

    /**
     * Rollback transaction
     *
     * @throws \Pop\Db\Exception
     * @return AbstractRecord
     */
    public function rollbackTransaction(): AbstractRecord
    {
        $class = get_called_class();
        if (($this->isTransaction) && (Db::hasDb($class))) {
            Db::db($class)->rollback();
        }
        $this->isTransaction = false;
        return $this;
    }

    /**
     * Get the table
     *
     * @return ?string
     */
    public function getTable(): ?string
    {
        return $this->table;
    }

    /**
     * Get the full table name (prefix + table)
     *
     * @return string
     */
    public function getFullTable(): string
    {
        return $this->prefix . $this->table;
    }

    /**
     * Get the table prefix
     *
     * @return ?string
     */
    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    /**
     * Get the primary keys
     *
     * @return array
     */
    public function getPrimaryKeys(): array
    {
        return $this->primaryKeys;
    }

    /**
     * Get the primary values
     *
     * @return array
     */
    public function getPrimaryValues(): array
    {
        return ($this->rowGateway !== null) ?
            array_intersect_key($this->rowGateway->getColumns(), array_flip($this->primaryKeys)) : [];
    }

    /**
     * Get the row gateway
     *
     * @return ?Gateway\Row
     */
    public function getRowGateway(): ?Gateway\Row
    {
        return $this->rowGateway;
    }

    /**
     * Get the table gateway
     *
     * @return ?Gateway\Table
     */
    public function getTableGateway(): ?Gateway\Table
    {
        return $this->tableGateway;
    }

    /**
     * Get column values as array
     *
     * @return array
     */
    public function toArray(): array
    {
        $columns = $this->rowGateway->getColumns();

        if ($this->hasRelationships()) {
            $relationships = $this->getRelationships();
            foreach ($relationships as $name => $relationship) {
                $columns[$name] = (is_object($relationship) && method_exists($relationship, 'toArray')) ?
                    $relationship->toArray() : $relationship;
            }
        }

        return $columns;
    }

    /**
     * Method to get count of fields in row gateway
     *
     * @return int
     */
    public function count(): int
    {
        return $this->rowGateway->count();
    }

    /**
     * Method to iterate over the columns
     *
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return $this->rowGateway->getIterator();
    }

    /**
     * Get the rows
     *
     * @return Collection
     */
    public function getRows(): Collection
    {
        return new Collection($this->tableGateway->getRows());
    }

    /**
     * Get the rows (alias method)
     *
     * @return Collection
     */
    public function rows(): Collection
    {
        return $this->getRows();
    }

    /**
     * Get the count of rows returned in the result
     *
     * @return int
     */
    public function countRows(): int
    {
        return $this->tableGateway->getNumberOfRows();
    }

    /**
     * Determine if the result has rows
     *
     * @return bool
     */
    public function hasRows(): bool
    {
        return ($this->tableGateway->getNumberOfRows() > 0);
    }

    /**
     * Set all the table column values at once
     *
     * @param  mixed  $columns
     * @throws Exception
     * @return AbstractRecord
     */
    public function setColumns(mixed $columns = null): AbstractRecord
    {
        if ($columns !== null) {
            if (is_array($columns) || ($columns instanceof \ArrayObject)) {
                $this->rowGateway->setColumns((array)$columns);
            } else if ($columns instanceof AbstractRecord) {
                $this->rowGateway->setColumns($columns->toArray());
            } else if (($columns instanceof \ArrayAccess) && method_exists($columns, 'toArray')) {
                $this->rowGateway->setColumns($columns->toArray());
            } else {
                throw new Exception('The parameter passed must be an arrayable object.');
            }
        }

        return $this;
    }

    /**
     * Set all the table rows at once
     *
     * @param  ?array $rows
     * @param  bool   $toArray
     * @return AbstractRecord
     */
    public function setRows(array $rows = null, bool|array $toArray = false): AbstractRecord
    {
        $this->rowGateway->setColumns();
        $this->tableGateway->setRows();

        if ($rows !== null) {
            $this->rowGateway->setColumns(((isset($rows[0])) ? (array)$rows[0] : []));
            foreach ($rows as $i => $row) {
                $rows[$i] = $this->processRow($row, $toArray);
            }
            $this->tableGateway->setRows($rows);
        }

        return $this;
    }

    /**
     * Process table rows
     *
     * @param  array $rows
     * @param  bool  $toArray
     * @return array
     */
    public function processRows(array $rows, bool|array $toArray = false): array
    {
        foreach ($rows as $i => $row) {
            $rows[$i] = $this->processRow($row, $toArray);
        }
        return $rows;
    }

    /**
     * Process a table row
     *
     * @param  array $row
     * @param  bool  $toArray
     * @return mixed
     */
    public function processRow(array $row, bool|array $toArray = false): mixed
    {
        if ($toArray !== false) {
            return (is_array($toArray)) ? (new Collection($row))->toArray($toArray): $row;
        } else {
            $record = new static();
            $record->setColumns($row);
            return $record;
        }
    }

    /**
     * Set with relationships
     *
     * @param  string $name
     * @param  ?array $options
     * @return AbstractRecord
     */
    public function addWith(string $name, array $options = null): AbstractRecord
    {
        $children = null;
        if (str_contains($name, '.')) {
            $names    = explode('.', $name);
            $name     = array_shift($names);
            $children = implode('.', $names);
        }
        $this->with[]        = $name;
        $this->withOptions[] = $options;
        $this->withChildren  = $children;

        return $this;
    }

    /**
     * Determine if there is specific with relationship
     *
     * @param  string $name
     * @return bool
     */
    public function hasWith(string $name): bool
    {
        return (isset($this->with[$name]));
    }

    /**
     * Determine if there are with relationships
     *
     * @return bool
     */
    public function hasWiths(): bool
    {
        return (count($this->with) > 0);
    }

    /**
     * Get with relationships
     *
     * @return array
     */
    public function getWiths(): array
    {
        return $this->with;
    }

    /**
     * Get with relationships
     *
     * @param  bool $eager
     * @return AbstractRecord
     */
    public function getWithRelationships(bool $eager = true): AbstractRecord
    {
        foreach ($this->with as $i => $name) {
            $options = (isset($this->withOptions[$i])) ? $this->withOptions[$i] : null;

            if (method_exists($this, $name)) {
                $this->relationships[$name] = $this->{$name}($options, $eager);
            }
        }

        return $this;
    }

    /**
     * Process with relationships
     *
     * @param  array $rows
     * @return AbstractRecord
     */
    public function processWithRelationships(array $rows): AbstractRecord
    {
        foreach ($this->relationships as $name => $relationship) {
            $withIds = [];
            if ($relationship instanceof \Pop\Db\Record\Relationships\HasOneOf) {
                $primaryKey = $relationship->getForeignKey();
                foreach ($rows as $i => $row) {
                    if (isset($row[$primaryKey]) && !in_array($row[$primaryKey], $withIds)) {
                        $withIds[] = $row[$primaryKey];
                    }
                }
                $results = $relationship->getEagerRelationships($withIds);
            } else {
                $primaryKey = $this->getPrimaryKeys();
                if (count($primaryKey) == 1) {
                    $primaryKey = reset($primaryKey);
                }
                foreach ($rows as $i => $row) {
                    $primaryValues = $rows[$i]->getPrimaryValues();
                    if (count($primaryValues) == 1) {
                        $withId = reset($primaryValues);
                        if (!in_array($withId, $withIds)) {
                            $withIds[] = $withId;
                        }
                    }
                }
                $results = $relationship->getEagerRelationships($withIds);
            }
            foreach ($rows as $i => $row) {
                if (isset($results[$row[$primaryKey]])) {
                    $row->setRelationship($name, $results[$row[$primaryKey]]);
                } else {
                    $row->setRelationship($name, []);
                }
            }
        }

        return $this;
    }

    /**
     * Set relationship
     *
     * @param  string $name
     * @param  mixed  $relationship
     * @return AbstractRecord
     */
    public function setRelationship(string $name, mixed $relationship): AbstractRecord
    {
        $this->relationships[$name] = $relationship;
        return $this;
    }

    /**
     * Get relationship
     *
     * @param  string $name
     * @return mixed
     */
    public function getRelationship(string $name): mixed
    {
        return $this->relationships[$name] ?? null;
    }

    /**
     * Has relationship
     *
     * @param  string $name
     * @return bool
     */
    public function hasRelationship(string $name): bool
    {
        return (isset($this->relationships[$name]));
    }

    /**
     * Get relationships
     *
     * @return array
     */
    public function getRelationships(): array
    {
        return $this->relationships;
    }

    /**
     * Get relationships
     *
     * @return bool
     */
    public function hasRelationships(): bool
    {
        return (count($this->relationships) > 0);
    }

    /**
     * Method to set latest flag for relationships
     *
     * @param  string $sortBy
     * @return static
     */
    public function latest(string $sortBy = 'id'): static
    {
        $this->relationshipSortBy = $sortBy;
        $this->latest             = true;
        $this->oldest             = false;

        return $this;
    }

    /**
     * Method to set oldest flag for relationships
     *
     * @param  string $sortBy
     * @return static
     */
    public function oldest(string $sortBy = 'id'): static
    {
        $this->relationshipSortBy = $sortBy;
        $this->latest             = false;
        $this->oldest             = true;

        return $this;
    }

    /**
     * Magic method to set the property to the value of $this->rowGateway[$name]
     *
     * @param  string $name
     * @param  mixed $value
     * @return void
     */
    public function __set(string $name, mixed $value)
    {
        $this->rowGateway[$name] = $value;
    }

    /**
     * Magic method to return the value of $this->rowGateway[$name]
     *
     * @param  string $name
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        $result = null;

        if (isset($this->relationships[$name])) {
            $result = $this->relationships[$name];
        } else if (isset($this->rowGateway[$name])) {
            $result = $this->rowGateway[$name];
        } else if (method_exists($this, $name)) {
            $result = $this->{$name}();
        }

        return $result;
    }

    /**
     * Magic method to return the isset value of $this->rowGateway[$name]
     *
     * @param  string $name
     * @return bool
     */
    public function __isset(string $name): bool
    {
        if (isset($this->relationships[$name])) {
            return true;
        } else if (isset($this->rowGateway[$name])) {
            return true;
        } else if (method_exists($this, $name)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Magic method to unset $this->rowGateway[$name]
     *
     * @param  string $name
     * @return void
     */
    public function __unset(string $name): void
    {
        if (isset($this->rowGateway[$name])) {
            unset($this->rowGateway[$name]);
        }
    }

    /**
     * ArrayAccess offsetExists
     *
     * @param  mixed $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->__isset($offset);
    }

    /**
     * ArrayAccess offsetGet
     *
     * @param  mixed $offset
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->__get($offset);
    }

    /**
     * ArrayAccess offsetSet
     *
     * @param  mixed $offset
     * @param  mixed $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->__set($offset, $value);
    }

    /**
     * ArrayAccess offsetUnset
     *
     * @param  mixed $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->__unset($offset);
    }

}
