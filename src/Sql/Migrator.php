<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db\Sql;

use Pop\Db\Adapter\AbstractAdapter;
use Pop\Db\Sql\Parser;

/**
 * Sql migrator class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
 */
class Migrator extends Migration\AbstractMigrator
{

    /**
     * Migration path
     * @var string
     */
    protected $path = null;

    /**
     * Current migration position
     * @var string
     */
    protected $current = null;

    /**
     * Migrations
     * @var array
     */
    protected $migrations = [];

    /**
     * Constructor
     *
     * Instantiate the migrator object
     *
     * @param  AbstractAdapter $db
     * @param  string          $path
     */
    public function __construct(AbstractAdapter $db, $path)
    {
        parent::__construct($db);
        $this->setPath($path);
    }

    /**
     * Create new migration file
     *
     * @param  string $class
     * @param  string $path
     * @throws Exception
     * @return string
     */
    public static function create($class, $path = null)
    {
        $file          = date('YmdHis') . '_' . Parser\Table::parse($class) . '.php';
        $classContents = str_replace(
            'MigrationTemplate', $class, file_get_contents(__DIR__ . '/Migration/Template/MigrationTemplate.php')
        );

        if (null !== $path) {
            if (!is_dir($path)) {
                throw new Exception('Error: That path does not exist');
            }
            $file = $path . DIRECTORY_SEPARATOR . $file;
        }

        file_put_contents($file, $classContents);

        return $file;
    }

    /**
     * Run the migrator (up/forward direction)
     *
     * @param  mixed $steps
     * @return Migrator
     */
    public function run($steps = 1)
    {
        ksort($this->migrations, SORT_NUMERIC);

        $stepsToRun = [];
        $current    = null;

        foreach ($this->migrations as $timestamp => $migration) {
            if (strtotime($timestamp) > strtotime($this->current)) {
                $stepsToRun[] = $timestamp;
            }
        }

        if (count($stepsToRun) > 0) {
            $stop = ($steps == 'all') ? count($stepsToRun) : (int)$steps;
            for ($i = 0; $i < $stop; $i++) {
                $class = $this->migrations[$stepsToRun[$i]]['class'];
                if (!class_exists($class)) {
                    include $this->path . DIRECTORY_SEPARATOR . $this->migrations[$stepsToRun[$i]]['filename'];
                }
                $migration = new $class($this->db);
                $migration->up();
                $current = $stepsToRun[$i];
            }
        }

        if (null !== $current) {
            file_put_contents($this->path . DIRECTORY_SEPARATOR . '.current', $current);
        }

        return $this;
    }

    /**
     * Run all the migrator (up/forward direction)
     *
     * @return Migrator
     */
    public function runAll()
    {
        return $this->run('all');
    }

    /**
     * Roll back the migrator (down/backward direction)
     *
     * @param  mixed $steps
     * @return Migrator
     */
    public function rollback($steps = 1)
    {
        krsort($this->migrations, SORT_NUMERIC);

        $stepsToRun = [];
        $class      = null;

        foreach ($this->migrations as $timestamp => $migration) {
            if (strtotime($timestamp) <= strtotime($this->current)) {
                $stepsToRun[] = $timestamp;
            }
        }

        if (count($stepsToRun) > 0) {
            $stop = ($steps == 'all') ? count($stepsToRun) : (int)$steps;
            for ($i = 0; $i < $stop; $i++) {
                $class = $this->migrations[$stepsToRun[$i]]['class'];
                if (!class_exists($class)) {
                    include $this->path . DIRECTORY_SEPARATOR . $this->migrations[$stepsToRun[$i]]['filename'];
                }
                $migration = new $class($this->db);
                $migration->down();
            }
        }

        if (isset($i) && isset($stepsToRun[$i])) {
            file_put_contents($this->path . DIRECTORY_SEPARATOR . '.current', $stepsToRun[$i]);
        } else if (file_exists($this->path . DIRECTORY_SEPARATOR . '.current')) {
            unlink($this->path . DIRECTORY_SEPARATOR . '.current');
        }

        return $this;
    }

    /**
     * Roll back all the migrator (down/backward direction)
     *
     * @return Migrator
     */
    public function rollbackAll()
    {
        return $this->rollback('all');
    }

    /**
     * Set the migration path and get migration files
     *
     * @param  string $path
     * @throws Exception
     * @return Migrator
     */
    public function setPath($path)
    {
        if (!file_exists($path)) {
            throw new Exception('Error: That migration path does not exist');
        }

        $this->path = $path;

        $handle = opendir($this->path);
        while (false !== ($filename = readdir($handle))) {
            if (($filename != '.') && ($filename != '..') && !is_dir($this->path . DIRECTORY_SEPARATOR . $filename)) {
                $fileContents = trim(file_get_contents($this->path . DIRECTORY_SEPARATOR . $filename));
                if ($filename == '.current') {
                    $this->current = $fileContents;
                } else if ((substr($filename, -4) == '.php') && (strpos($fileContents, 'extends AbstractMigration') !== false)) {
                    $namespace = null;
                    if (strpos($fileContents, 'namespace ') !== false) {
                        $namespace = substr($fileContents, (strpos($fileContents, 'namespace ') + 10));
                        $namespace = trim(substr($namespace, 0, strpos($namespace, ';'))) . '\\';
                    }
                    $class = substr($fileContents, (strpos($fileContents, 'class ') + 6));
                    $class = $namespace . substr($class, 0, strpos($class, ' extends'));
                    $this->migrations[substr($filename, 0, 14)] = [
                        'class'    => $class,
                        'filename' => $filename
                    ];
                }
            }
        }

        return $this;
    }

    /**
     * Get the migration path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get the migration path
     *
     * @return string
     */
    public function getCurrent()
    {
        return $this->current;
    }

}