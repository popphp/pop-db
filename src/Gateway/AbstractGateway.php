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
namespace Pop\Db\Gateway;

use Pop\Db\Db;
use Pop\Db\Sql;
use Pop\Db\Adapter\AbstractAdapter;

/**
 * Db abstract gateway class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <nick@popphp.org>
 * @copyright  Copyright (c) 2009-2026 Nick Sagona, III
 * @license    https://www.popphp.org/license     New BSD License
 * @version    7.0.0
 */
abstract class AbstractGateway implements GatewayInterface
{

    /**
     * Recognized $options keys. This is the union of the keys consumed by the table gateway,
     * the row gateway and the relationship classes - a key outside of it is a typo.
     * @var array
     */
    public const OPTIONS = ['select', 'limit', 'offset', 'order', 'group', 'join', 'columns'];

    /**
     * Table
     * @var ?string
     */
    protected ?string $table = null;

    /**
     * Constructor
     *
     * Instantiate the AbstractGateway object.
     *
     * @param  string $table
     */
    public function __construct(string $table)
    {
        $this->table = $table;
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
     * Check the keys of an $options array, firing a notice naming any that isn't recognized
     *
     * An unrecognized key is silently ignored by the query builders, so a misspelled or
     * wrong-cased one ('limitt', 'Limit', 'orderBy') would otherwise return unfiltered results
     * with no signal at all. This is deliberately a notice and not an exception, so that
     * applications passing extra keys today keep working.
     *
     * @param  ?array $options
     * @return void
     */
    protected function checkOptions(?array $options = null): void
    {
        if (empty($options)) {
            return;
        }

        $unrecognized = array_diff(array_map('strval', array_keys($options)), self::OPTIONS);

        if (!empty($unrecognized)) {
            trigger_error(
                "Notice: The option key(s) [" . implode(', ', $unrecognized) . "] are not recognized and were " .
                "ignored. Option keys are case-sensitive; the recognized keys are: " .
                implode(', ', self::OPTIONS) . ".",
                E_USER_NOTICE
            );
        }
    }

    /**
     * Get table info
     *
     * @param  ?AbstractAdapter $db
     * @return array
     */
    public function getTableInfo(?AbstractAdapter $db = null): array
    {
        if ($db === null) {
            $db = Db::getDb($this->table);
        }

        $tables = $db->getTables();
        $sql    = $db->createSql();
        $info   = [
            'tableName' => $this->table,
            'columns'   => []
        ];

        if (in_array($this->table, $tables)) {
            $sqlString = null;
            $field     = 'column_name';
            $type      = 'data_type';
            $nullField = 'is_nullable';
            $keyField  = 'constraint_type';

            switch ($sql->getDbType()) {
                case Sql::PGSQL:
                case Sql::SQLSRV:
                    $sqlString = 'SELECT * FROM information_schema.columns ' .
                        'LEFT JOIN information_schema.table_constraints ' .
                        'ON information_schema.table_constraints.table_name = information_schema.columns.table_name ' .
                        'WHERE information_schema.columns.table_name = \'' . $this->table . '\'';
                    break;
                case Sql::SQLITE:
                    $sqlString = 'PRAGMA table_info(\'' . $this->table . '\')';
                    $field     = 'name';
                    $type      = 'type';
                    $nullField = 'notnull';
                    $keyField  = 'pk';
                    break;
                default:
                    $sqlString = 'SHOW COLUMNS FROM `' . $this->table . '`';
                    $field     = 'Field';
                    $type      = 'Type';
                    $nullField = 'Null';
                    $keyField  = 'Key';
            }

            $db->query($sqlString);

            while (($row = $db->fetch()) != false) {
                switch ($sql->getDbType()) {
                    case Sql::SQLITE:
                        $nullResult    = !($row[$nullField]);
                        $primaryResult = ($row[$keyField] == 1);
                        break;
                    case Sql::MYSQL:
                        $nullResult    = (!empty($row[$nullField]) && (strtoupper($row[$nullField]) != 'NO'));
                        $primaryResult = (!empty($row[$keyField]) && (strtoupper($row[$keyField]) == 'PRI'));
                        break;
                    default:
                        $nullResult    = $row[$nullField];
                        $primaryResult = (!empty($row[$keyField]) && (strtoupper($row[$keyField]) == 'PRIMARY KEY'));

                }

                $info['columns'][$row[$field]] = [
                    'type'    => $row[$type],
                    'primary' => $primaryResult,
                    'null'    => $nullResult
                ];
            }
        }

        return $info;
    }

}
