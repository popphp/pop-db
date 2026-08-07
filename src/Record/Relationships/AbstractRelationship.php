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
     * Relationship children (list of dotted child paths to eager-load under this relationship)
     * @var array
     */
    protected array $children = [];

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
     * @return array
     */
    public function getChildRelationships(): array
    {
        return $this->children;
    }

    /**
     * Set children child relationships
     *
     * @param  array $children
     * @return static
     */
    public function setChildRelationships(array $children): static
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

    /**
     * Get the value to use for a leaf record's named child relationship when no eager-loaded
     * results were found for it (e.g. a "has many" child with zero matching rows should still
     * see an empty Collection rather than a plain array). Concrete relationship classes whose
     * populated results aren't plain arrays should override this.
     *
     * @return mixed
     */
    public function getEmptyRelationshipValue(): mixed
    {
        return [];
    }

    /**
     * Hydrate nested child relationships onto a flat list of leaf records, resolving each
     * named child relationship once (accumulated by name) and distributing every one of them
     * onto every leaf record — so multiple differently-named children under this relationship
     * don't overwrite each other.
     *
     * @param  array  $leafRecords
     * @param  string $primaryKeyColumn
     * @return void
     */
    protected function hydrateChildRelationships(array $leafRecords, string $primaryKeyColumn): void
    {
        if (empty($this->children) || empty($leafRecords)) {
            return;
        }

        $parentIds = array_values(array_unique(array_map(
            fn($record) => $record[$primaryKeyColumn], $leafRecords
        )));

        $accumulated         = [];
        $relationshipsByName = [];

        foreach ($leafRecords as $record) {
            foreach ($this->children as $childPath) {
                $record->addWith($childPath);
            }
            $record->getWithRelationships();
            foreach ($record->getRelationships() as $name => $relationship) {
                if (!isset($accumulated[$name])) {
                    $accumulated[$name]         = $relationship->getEagerRelationships($parentIds);
                    $relationshipsByName[$name] = $relationship;
                }
            }
        }

        foreach ($leafRecords as $record) {
            $primaryValue = $record[$primaryKeyColumn] ?? null;
            foreach ($accumulated as $name => $resultsByParentId) {
                $record->setRelationship(
                    $name,
                    $resultsByParentId[$primaryValue] ?? $relationshipsByName[$name]->getEmptyRelationshipValue()
                );
            }
        }
    }

}
