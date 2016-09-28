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

    protected $drop     = [];
    protected $create   = [];
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

    public function render()
    {
        $sql = '';

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

        return $sql;
    }

    public function execute()
    {
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
    }

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
