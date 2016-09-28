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

    /**
     * DROP table schema objects
     * @var array
     */
    protected $drop     = [];

    /**
     * CREATE table schema objects
     * @var array
     */
    protected $create   = [];

    /**
     * ALTER table schema objects
     * @var array
     */
    protected $alter    = [];

    /**
     * RENAME table schema objects
     * @var array
     */
    protected $rename   = [];

    /**
     * TRUNCATE table schema objects
     * @var array
     */
    protected $truncate = [];

    /**
     * Foreign key check flag
     * @var boolean
     */
    protected $foreignKeyCheck = true;

    /**
     * Access the CREATE table object
     *
     * @param  string $table
     * @return Schema\Create
     */
    public function create($table)
    {
        return $this->getCreateTable($table);
    }

    /**
     * Access the CREATE table object, setting IF NOT EXISTS
     *
     * @param  string $table
     * @return Schema\Create
     */
    public function createIfNotExists($table)
    {
        $this->getCreateTable($table)->ifNotExists();
        return $this->getCreateTable($table);
    }

    /**
     * Access the DROP table object
     *
     * @param  string $table
     * @return Schema\Drop
     */
    public function drop($table)
    {
        return $this->getDropTable($table);
    }

    /**
     * Access the DROP table object, setting IF EXISTS
     *
     * @param  string $table
     * @return Schema\Drop
     */
    public function dropIfExists($table)
    {
        $this->getDropTable($table)->ifExists();
        return $this->getDropTable($table);
    }

    /**
     * Access the ALTER table object
     *
     * @param  string $table
     * @return Schema\Alter
     */
    public function alter($table)
    {
        return $this->getAlterTable($table);
    }

    /**
     * Access the RENAME table object
     *
     * @param  string $table
     * @return Schema\Rename
     */
    public function rename($table)
    {
        return $this->getRenameTable($table);
    }

    /**
     * Access the TRUNCATE table object
     *
     * @param  string $table
     * @return Schema\Truncate
     */
    public function truncate($table)
    {
        return $this->getTruncateTable($table);
    }

    /**
     * Enable the foreign key check
     *
     * @return Schema
     */
    public function enableForeignKeyCheck()
    {
        $this->foreignKeyCheck = true;
        return $this;
    }

    /**
     * Disable the foreign key check
     *
     * @return Schema
     */
    public function disableForeignKeyCheck()
    {
        $this->foreignKeyCheck = false;
        return $this;
    }

    /**
     * Render the schema
     *
     * @return string
     */
    public function render()
    {
        $sql = '';

        if (($this->dbType == self::MYSQL) && (!$this->foreignKeyCheck)) {
            $sql .= 'SET foreign_key_checks = 0;' . PHP_EOL . PHP_EOL;
        }

        // Render DROP tables
        foreach ($this->drop as $drop) {
            $sql .= $drop->render();
        }

        // Render CREATE tables
        foreach ($this->create as $create) {
            $sql .= $create->render();
        }

        // Render ALTER tables
        foreach ($this->alter as $alter) {
            $sql .= $alter->render();
        }

        // Render RENAME tables
        foreach ($this->rename as $rename) {
            $sql .= $rename->render();
        }

        // Render TRUNCATE tables
        foreach ($this->truncate as $truncate) {
            $sql .= $truncate->render();
        }

        if (($this->dbType == self::MYSQL) && (!$this->foreignKeyCheck)) {
            $sql .= 'SET foreign_key_checks = 1;' . PHP_EOL . PHP_EOL;
        }

        return $sql;
    }

    /**
     * Execute the schema directly
     *
     * @return void
     */
    public function execute()
    {
        if (($this->dbType == self::MYSQL) && (!$this->foreignKeyCheck)) {
            $this->db->query('SET foreign_key_checks = 0');
        }

        // Execute DROP tables
        foreach ($this->drop as $drop) {
            $this->db->query($drop->render());
        }

        // Execute CREATE tables
        foreach ($this->create as $create) {
            $this->db->query($create->render());
        }

        // Execute ALTER tables
        foreach ($this->alter as $alter) {
            $this->db->query($alter->render());
        }

        // Execute RENAME tables
        foreach ($this->rename as $rename) {
            $this->db->query($rename->render());
        }

        // Execute TRUNCATE tables
        foreach ($this->truncate as $truncate) {
            $this->db->query($truncate->render());
        }

        if (($this->dbType == self::MYSQL) && (!$this->foreignKeyCheck)) {
            $this->db->query('SET foreign_key_checks = 1');
        }
    }

    /**
     * Render the schema to string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }

    /**
     * @param  string $table
     * @return Schema\Create
     */
    protected function getCreateTable($table)
    {
        if (!isset($this->create[$table])) {
            $this->create[$table] = new Schema\Create($table, $this->db);
        }
        return $this->create[$table];
    }

    /**
     * @param  string $table
     * @return Schema\Drop
     */
    protected function getDropTable($table)
    {
        if (!isset($this->drop[$table])) {
            $this->drop[$table] = new Schema\Drop($table, $this->db);
        }
        return $this->drop[$table];
    }

    /**
     * @param  string $table
     * @return Schema\Alter
     */
    protected function getAlterTable($table)
    {
        if (!isset($this->alter[$table])) {
            $this->alter[$table] = new Schema\Alter($table, $this->db);
        }
        return $this->alter[$table];
    }

    /**
     * @param  string $table
     * @return Schema\Rename
     */
    protected function getRenameTable($table)
    {
        if (!isset($this->rename[$table])) {
            $this->rename[$table] = new Schema\Rename($table, $this->db);
        }
        return $this->rename[$table];
    }

    /**
     * @param  string $table
     * @return Schema\Truncate
     */
    protected function getTruncateTable($table)
    {
        if (!isset($this->truncate[$table])) {
            $this->truncate[$table] = new Schema\Truncate($table, $this->db);
        }
        return $this->truncate[$table];
    }

}