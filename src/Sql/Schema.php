<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db\Sql;

/**
 * Sql schema table class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
 */
class Schema extends AbstractSql
{

    protected $create   = [];
    protected $drop     = [];
    protected $alter    = [];
    protected $rename   = [];
    protected $truncate = [];

    public function create($table)
    {
        return $this->getCreateTable($table);
    }

    public function createIfNotExists($table)
    {
        $this->getCreateTable($table)->ifNotExists();
        return $this->getCreateTable($table);
    }

    public function drop($table)
    {
        return $this->getDropTable($table);
    }

    public function dropIfExists($table)
    {
        $this->getDropTable($table)->ifExists();
        return $this->getDropTable($table);
    }

    public function alter($table)
    {
        return $this->getAlterTable($table);
    }

    public function rename($table)
    {
        return $this->getRenameTable($table);
    }

    public function truncate($table)
    {
        return $this->getTruncateTable($table);
    }

    /**
     * @param  string $table
     * @return Table\Create
     */
    protected function getCreateTable($table)
    {
        if (!isset($this->createTables[$table])) {
            $this->createTables[$table] = new Table\Create($table);
        }
        return $this->createTables[$table];
    }

    /**
     * @param  string $table
     * @return Table\Drop
     */
    protected function getDropTable($table)
    {
        if (!isset($this->dropTables[$table])) {
            $this->dropTables[$table] = new Table\Drop($table);
        }
        return $this->dropTables[$table];
    }

    /**
     * @param  string $table
     * @return Table\Alter
     */
    protected function getAlterTable($table)
    {
        if (!isset($this->alterTables[$table])) {
            $this->alterTables[$table] = new Table\Alter($table);
        }
        return $this->alterTables[$table];
    }

    /**
     * @param  string $table
     * @return Table\Rename
     */
    protected function getRenameTable($table)
    {
        if (!isset($this->renameTables[$table])) {
            $this->renameTables[$table] = new Table\Rename($table);
        }
        return $this->renameTables[$table];
    }

    /**
     * @param  string $table
     * @return Table\Truncate
     */
    protected function getTruncateTable($table)
    {
        if (!isset($this->truncateTables[$table])) {
            $this->truncateTables[$table] = new Table\Truncate($table);
        }
        return $this->truncateTables[$table];
    }

}
