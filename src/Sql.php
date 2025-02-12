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
namespace Pop\Db;

use Pop\Db\Sql\AbstractSql;

/**
 * Sql class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.6.5
 */
class Sql extends AbstractSql
{

    /**
     * Select object
     * @var ?Sql\Select
     */
    protected ?Sql\Select $select = null;

    /**
     * Insert object
     * @var ?Sql\Insert
     */
    protected ?Sql\Insert $insert = null;

    /**
     * Update object
     * @var ?Sql\Update
     */
    protected ?Sql\Update $update = null;

    /**
     * Delete object
     * @var ?Sql\Delete
     */
    protected ?Sql\Delete $delete = null;

    /**
     * Access the select object
     *
     * @param  mixed $columns
     * @return ?Sql\Select
     */
    public function select(mixed $columns = null): ?Sql\Select
    {
        $this->insert = null;
        $this->update = null;
        $this->delete = null;

        if ($this->select === null) {
            $this->select = new Sql\Select($this->db);
        }
        if ($columns !== null) {
            if (!is_array($columns)) {
                $columns = [$columns];
            }
            foreach ($columns as $name => $value) {
                if (!is_numeric($name)) {
                    $this->select->addValue($value, $name);
                } else {
                    $this->select->addValue($value);
                }
            }
        }

        return $this->select;
    }

    /**
     * Access the insert object
     *
     * @param  ?string $table
     * @return ?Sql\Insert
     */
    public function insert(?string $table = null): ?Sql\Insert
    {
        $this->select = null;
        $this->update = null;
        $this->delete = null;

        if ($this->insert === null) {
            $this->insert = new Sql\Insert($this->db);
        }
        if ($table !== null) {
            $this->insert->setTable($table);
        }

        return $this->insert;
    }

    /**
     * Access the update object
     *
     * @param  ?string $table
     * @return ?Sql\Update
     */
    public function update(?string $table = null): ?Sql\Update
    {
        $this->insert = null;
        $this->select = null;
        $this->delete = null;

        if ($this->update === null) {
            $this->update = new Sql\Update($this->db);
        }
        if ($table !== null) {
            $this->update->setTable($table);
        }

        return $this->update;
    }

    /**
     * Access the delete object
     *
     * @param  ?string $table
     * @return ?Sql\Delete
     */
    public function delete(?string $table = null): ?Sql\Delete
    {
        $this->insert = null;
        $this->update = null;
        $this->select = null;

        if ($this->delete === null) {
            $this->delete = new Sql\Delete($this->db);
        }
        if ($table !== null) {
            $this->delete->setTable($table);
        }

        return $this->delete;
    }

    /**
     * Determine if SQL object has a select object
     *
     * @return bool
     */
    public function hasSelect(): bool
    {
        return ($this->select !== null);
    }

    /**
     * Determine if SQL object has a insert object
     *
     * @return bool
     */
    public function hasInsert(): bool
    {
        return ($this->insert !== null);
    }

    /**
     * Determine if SQL object has a update object
     *
     * @return bool
     */
    public function hasUpdate(): bool
    {
        return ($this->update !== null);
    }

    /**
     * Determine if SQL object has a delete object
     *
     * @return bool
     */
    public function hasDelete(): bool
    {
        return ($this->delete !== null);
    }

    /**
     * Reset and clear the SQL object
     *
     * @return Sql
     */
    public function reset(): Sql
    {
        $this->select = null;
        $this->insert = null;
        $this->update = null;
        $this->delete = null;

        return $this;
    }

    /**
     * Render the SQL statement
     *
     * @return string
     */
    public function render(): string
    {
        $sql = '';

        if ($this->select !== null) {
            $sql = $this->select->render();
        } else if ($this->insert !== null) {
            $sql = $this->insert->render();
        } else if ($this->update !== null) {
            $sql = $this->update->render();
        } else if ($this->delete !== null) {
            $sql = $this->delete->render();
        }

        $this->reset();

        return $sql;
    }

    /**
     * Render the SQL statement
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->render();
    }

}
