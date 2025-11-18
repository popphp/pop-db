<?php
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2026 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db\Record\Relationships;

use Pop\Db\Record\Collection;

/**
 * Relationship class for "has one" relationships
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2026 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.8.0
 */
abstract class AbstractRelationship implements RelationshipInterface
{

    /**
     * Foreign table class
     * @var ?string
     */
    protected ?string $foreignTable = null;

    /**
     * Foreign key
     * @var ?string
     */
    protected ?string $foreignKey = null;

    /**
     * Relationship options
     * @var ?array
     */
    protected ?array $options = null;

    /**
     * Relationship children
     * @var ?string
     */
    protected ?string $children = null;

    /**
     * Constructor
     *
     * Instantiate the relationship object
     *
     * @param string $foreignTable
     * @param string $foreignKey
     * @param ?array $options
     */
    public function __construct(string $foreignTable, string $foreignKey, ?array $options = null)
    {
        $this->foreignTable = $foreignTable;
        $this->foreignKey   = $foreignKey;
        $this->options      = $options;
    }

    /**
     * Get foreign table class
     *
     * @return string|null
     */
    public function getForeignTable(): string|null
    {
        return $this->foreignTable;
    }

    /**
     * Get foreign key
     *
     * @return string|null
     */
    public function getForeignKey(): string|null
    {
        return $this->foreignKey;
    }

    /**
     * Get options
     *
     * @return array|null
     */
    public function getOptions(): array|null
    {
        return $this->options;
    }

    /**
     * Get child relationships
     *
     * @return string|null
     */
    public function getChildRelationships(): string|null
    {
        return $this->children;
    }

    /**
     * Set children child relationships
     *
     * @param  string $children
     * @return static
     */
    public function setChildRelationships(string $children): static
    {
        $this->children = $children;
        return $this;
    }

    /**
     * Get eager relationships
     *
     * @param  array $ids
     * @throws Exception
     * @return array
     */
    abstract public function getEagerRelationships(array $ids): array;

}
