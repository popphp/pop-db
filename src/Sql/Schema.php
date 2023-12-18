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
namespace Pop\Db\Sql;

/**
 * Sql schema table class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    6.5.0
 */
class Schema extends AbstractSql
{

    /**
     * DROP table schema objects
     * @var array
     */
    protected array $drop = [];

    /**
     * CREATE table schema objects
     * @var array
     */
    protected array $create = [];

    /**
     * ALTER table schema objects
     * @var array
     */
    protected array $alter = [];

    /**
     * RENAME table schema objects
     * @var array
     */
    protected array $rename = [];

    /**
     * TRUNCATE table schema objects
     * @var array
     */
    protected array $truncate = [];

    /**
     * Foreign key check flag
     * @var bool
     */
    protected bool $foreignKeyCheck = true;

    /**
     * Access the CREATE table object
     *
     * @param  string $table
     * @return Schema\Create
     */
    public function create(string $table): Schema\Create
    {
        return $this->getCreateTable($table);
    }

    /**
     * Access the CREATE table object, setting IF NOT EXISTS
     *
     * @param  string $table
     * @return Schema\Create
     */
    public function createIfNotExists(string $table): Schema\Create
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
    public function drop(string $table): Schema\Drop
    {
        return $this->getDropTable($table);
    }

    /**
     * Access the DROP table object, setting IF EXISTS
     *
     * @param  string $table
     * @return Schema\Drop
     */
    public function dropIfExists(string $table): Schema\Drop
    {
        return $this->getDropTable($table)->ifExists();
    }

    /**
     * Access the ALTER table object
     *
     * @param  string $table
     * @return Schema\Alter
     */
    public function alter(string $table): Schema\Alter
    {
        return $this->getAlterTable($table);
    }

    /**
     * Access the RENAME table object
     *
     * @param  string $table
     * @return Schema\Rename
     */
    public function rename(string $table): Schema\Rename
    {
        return $this->getRenameTable($table);
    }

    /**
     * Access the TRUNCATE table object
     *
     * @param  string $table
     * @return Schema\Truncate
     */
    public function truncate(string $table): Schema\Truncate
    {
        return $this->getTruncateTable($table);
    }

    /**
     * Enable the foreign key check
     *
     * @return Schema
     */
    public function enableForeignKeyCheck(): Schema
    {
        $this->foreignKeyCheck = true;
        return $this;
    }

    /**
     * Disable the foreign key check
     *
     * @return Schema
     */
    public function disableForeignKeyCheck(): Schema
    {
        $this->foreignKeyCheck = false;
        return $this;
    }

    /**
     * Render the schema
     *
     * @return string
     */
    public function render(): string
    {
        $sql = '';

        if (!$this->foreignKeyCheck) {
            if ($this->isMysql()) {
                $sql .= 'SET foreign_key_checks = 0;' . PHP_EOL . PHP_EOL;
            } else if ($this->isSqlite()) {
                $sql .= 'PRAGMA foreign_keys=off;' . PHP_EOL . PHP_EOL;
            }
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

        if (!$this->foreignKeyCheck) {
            if ($this->isMysql()) {
                $sql .= 'SET foreign_key_checks = 1;' . PHP_EOL . PHP_EOL;
            } else if ($this->isSqlite()) {
                $sql .= 'PRAGMA foreign_keys=on;' . PHP_EOL . PHP_EOL;
            }
        }

        $this->reset();

        return $sql;
    }

    /**
     * Reset and clear the schema object
     *
     * @return Schema
     */
    public function reset(): Schema
    {
        $this->drop            = [];
        $this->create          = [];
        $this->alter           = [];
        $this->rename          = [];
        $this->truncate        = [];
        $this->foreignKeyCheck = true;

        return $this;
    }

    /**
     * Execute the schema directly
     *
     * @param  bool $reset
     * @return void
     */
    public function execute(bool $reset = true): void
    {
        if (!$this->foreignKeyCheck) {
            if ($this->isMysql()) {
                $this->db->query('SET foreign_key_checks = 0');
            } else if ($this->isSqlite()) {
                $this->db->query('PRAGMA foreign_keys=off');
            }
        }

        // Execute DROP tables
        foreach ($this->drop as $drop) {
            $dropStatements = $drop->renderToStatements();
            foreach ($dropStatements as $statement) {
                $this->db->query($statement);
            }
        }

        // Execute CREATE tables
        foreach ($this->create as $create) {
            $createStatements = $create->renderToStatements();
            foreach ($createStatements as $statement) {
                $this->db->query($statement);
            }
        }

        // Execute ALTER tables
        foreach ($this->alter as $alter) {
            $alterStatements = $alter->renderToStatements();
            foreach ($alterStatements as $statement) {
                $this->db->query($statement);
            }
        }

        // Execute RENAME tables
        foreach ($this->rename as $rename) {
            $renameStatements = $rename->renderToStatements();
            foreach ($renameStatements as $statement) {
                $this->db->query($statement);
            }
        }

        // Execute TRUNCATE tables
        foreach ($this->truncate as $truncate) {
            $truncateStatements = $truncate->renderToStatements();
            foreach ($truncateStatements as $statement) {
                $this->db->query($statement);
            }
        }

        if (!$this->foreignKeyCheck) {
            if ($this->isMysql()) {
                $this->db->query('SET foreign_key_checks = 1');
            } else if ($this->isSqlite()) {
                $this->db->query('PRAGMA foreign_keys=on');
            }
        }

        if ($reset) {
            $this->reset();
        }
    }

    /**
     * Render the schema to string
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->render();
    }

    /**
     * Get the CREATE table object
     *
     * @param  string $table
     * @return Schema\Create
     */
    protected function getCreateTable(string $table): Schema\Create
    {
        if (!isset($this->create[$table])) {
            $this->create[$table] = new Schema\Create($table, $this->db);
        }
        return $this->create[$table];
    }

    /**
     * Get the DROP table object
     *
     * @param  string $table
     * @return Schema\Drop
     */
    protected function getDropTable(string $table): Schema\Drop
    {
        if (!isset($this->drop[$table])) {
            $this->drop[$table] = new Schema\Drop($table, $this->db);
        }
        return $this->drop[$table];
    }

    /**
     * Get the ALTER table object
     *
     * @param  string $table
     * @return Schema\Alter
     */
    protected function getAlterTable(string $table): Schema\Alter
    {
        if (!isset($this->alter[$table])) {
            $this->alter[$table] = new Schema\Alter($table, $this->db);
        }
        return $this->alter[$table];
    }

    /**
     * Get the RENAME table object
     *
     * @param  string $table
     * @return Schema\Rename
     */
    protected function getRenameTable(string $table): Schema\Rename
    {
        if (!isset($this->rename[$table])) {
            $this->rename[$table] = new Schema\Rename($table, $this->db);
        }
        return $this->rename[$table];
    }

    /**
     * Get the TRUNCATE table object
     *
     * @param  string $table
     * @return Schema\Truncate
     */
    protected function getTruncateTable(string $table): Schema\Truncate
    {
        if (!isset($this->truncate[$table])) {
            $this->truncate[$table] = new Schema\Truncate($table, $this->db);
        }
        return $this->truncate[$table];
    }

}
