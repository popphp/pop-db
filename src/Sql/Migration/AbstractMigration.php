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
namespace Pop\Db\Sql\Migration;

/**
 * Db SQL migration abstract class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.6.5
 */
abstract class AbstractMigration extends AbstractMigrator implements MigrationInterface
{

    /**
     * Execute an UP migration (new forward changes)
     *
     * @return void
     */
    abstract public function up(): void;

    /**
     * Execute a DOWN migration (rollback previous changes)
     *
     * @return void
     */
    abstract public function down(): void;

}
