<?php
declare(strict_types=1);
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <nick@popphp.org>
 * @copyright  Copyright (c) 2009-2026 Nick Sagona, III
 * @license    https://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db\Record\Relationships;

/**
 * Relationship class for "has one" relationships
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <nick@popphp.org>
 * @copyright  Copyright (c) 2009-2026 Nick Sagona, III
 * @license    https://www.popphp.org/license     New BSD License
 * @version    7.0.0
 */
interface RelationshipInterface
{

    /**
     * Get foreign table
     *
     * @return string|null
     */
    public function getForeignTable(): string|null;

    /**
     * Get foreign key
     *
     * @return string|array|null
     */
    public function getForeignKey(): string|array|null;

    /**
     * Get options
     *
     * @return array|null
     */
    public function getOptions(): array|null;

    /**
     * Get eager relationships
     *
     * @param  array $ids
     * @throws Exception
     * @return array
     */
    public function getEagerRelationships(array $ids): array;

    /**
     * Get the value to use when no eager-loaded result exists for a given leaf record
     *
     * @return mixed
     */
    public function getEmptyRelationshipValue(): mixed;

}
