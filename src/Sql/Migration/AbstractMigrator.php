<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db\Sql\Migration;

use Pop\Db\Adapter\AbstractAdapter;

/**
 * Db SQL migrator abstract class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    6.0.0
 */
abstract class AbstractMigrator implements MigratorInterface
{

    /**
     * Database adapter
     * @var ?AbstractAdapter
     */
    protected ?AbstractAdapter $db = null;

    /**
     * Constructor
     *
     * Instantiate the migration object
     *
     * @param  AbstractAdapter $db
     */
    public function __construct(AbstractAdapter $db)
    {
        $this->db = $db;
    }

    /**
     * Get the DB adapter
     *
     * @return AbstractAdapter
     */
    public function getDb(): AbstractAdapter
    {
        return $this->db;
    }

    /**
     * Get the DB adapter (alias method)
     *
     * @return AbstractAdapter
     */
    public function db(): AbstractAdapter
    {
        return $this->db;
    }

}