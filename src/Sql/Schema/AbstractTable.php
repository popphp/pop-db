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
namespace Pop\Db\Sql\Schema;

use Pop\Db\Adapter\AbstractAdapter;
use Pop\Db\Sql\AbstractSql;
use Pop\Db\Gateway;

/**
 * Abstract schema table class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.5.0
 */
abstract class AbstractTable extends AbstractSql
{

    /**
     * Table name
     * @var ?string
     */
    protected ?string $table = null;

    /**
     * Table info
     * @var array
     */
    protected array $info = [];

    /**
     * Constructor
     *
     * Instantiate the table object
     *
     * @param  string          $table
     * @param  AbstractAdapter $db
     */
    public function __construct(string $table, AbstractAdapter $db)
    {
        $this->table = $table;
        parent::__construct($db);

        $tableGateway = new Gateway\Table($table);
        $this->info   = $tableGateway->getTableInfo($db);
    }

    /**
     * Get the table name
     *
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Get the table info
     *
     * @return array
     */
    public function getInfo(): array
    {
        return $this->info;
    }

    /**
     * Render the table schema to an array of statements
     *
     * @return array
     */
    public function renderToStatements(): array
    {
        $statements    = explode(';' . PHP_EOL, $this->render());
        $sqlStatements = [];

        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                $sqlStatements[] = $statement;
            }
        }

        return $sqlStatements;
    }

    /**
     * Render the table schema
     *
     * @return string
     */
    abstract public function render(): string;

    /**
     * Render the table schema to string
     *
     * @return string
     */
    abstract public function __toString(): string;

}
