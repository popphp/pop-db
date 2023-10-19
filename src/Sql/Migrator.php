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

use Pop\Db\Adapter\AbstractAdapter;
use Pop\Db\Sql\Parser;

/**
 * Sql migrator class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    6.0.0
 */
class Migrator extends Migration\AbstractMigrator
{

    /**
     * Migration path
     * @var ?string
     */
    protected ?string $path = null;

    /**
     * Current migration position
     * @var ?string
     */
    protected ?string $current = null;

    /**
     * Migrations
     * @var array
     */
    protected array $migrations = [];

    /**
     * Constructor
     *
     * Instantiate the migrator object
     *
     * @param  AbstractAdapter $db
     * @param  string          $path
     * @throws Exception
     */
    public function __construct(AbstractAdapter $db, string $path)
    {
        parent::__construct($db);
        $this->setPath($path);
    }

    /**
     * Create new migration file
     *
     * @param  string  $class
     * @param  ?string $path
     * @throws Exception
     * @return string
     */
    public static function create(string $class, ?string $path = null): string
    {
        $file          = date('YmdHis') . '_' . Parser\Table::parse($class) . '.php';
        $classContents = str_replace(
            'MigrationTemplate', $class, file_get_contents(__DIR__ . '/Migration/Template/MigrationTemplate.php')
        );

        if ($path !== null) {
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
    public function run(mixed $steps = 1): Migrator
    {
        ksort($this->migrations, SORT_NUMERIC);

        $stepsToRun = [];
        $current    = null;

        foreach ($this->migrations as $timestamp => $migration) {
            if (strtotime($timestamp) > strtotime((int)$this->current)) {
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

        if ($current !== null) {
            $this->storeCurrent($current);
        }

        return $this;
    }

    /**
     * Run all the migrator (up/forward direction)
     *
     * @return Migrator
     */
    public function runAll(): Migrator
    {
        return $this->run('all');
    }

    /**
     * Roll back the migrator (down/backward direction)
     *
     * @param  mixed $steps
     * @return Migrator
     */
    public function rollback(mixed $steps = 1): Migrator
    {
        krsort($this->migrations, SORT_NUMERIC);

        $stepsToRun = [];
        $class      = null;

        foreach ($this->migrations as $timestamp => $migration) {
            if (strtotime($timestamp) <= strtotime((int)$this->current)) {
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
            $this->storeCurrent($stepsToRun[$i]);
        } else {
            $this->clearCurrent();
        }

        return $this;
    }

    /**
     * Roll back all the migrator (down/backward direction)
     *
     * @return Migrator
     */
    public function rollbackAll(): Migrator
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
    public function setPath(string $path): Migrator
    {
        if (!file_exists($path)) {
            throw new Exception('Error: That migration path does not exist');
        }

        $this->path = $path;

        $handle = opendir($this->path);
        while (false !== ($filename = readdir($handle))) {
            if (($filename != '.') && ($filename != '..') &&
                !is_dir($this->path . DIRECTORY_SEPARATOR . $filename) && (str_ends_with($filename, '.php'))) {
                $fileContents = trim(file_get_contents($this->path . DIRECTORY_SEPARATOR . $filename));
                if ((str_contains($fileContents, 'extends AbstractMigration'))) {
                    $namespace = null;
                    if (str_contains($fileContents, 'namespace ')) {
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
        closedir($handle);

        $this->loadCurrent();

        return $this;
    }

    /**
     * Get the migration path
     *
     * @return ?string
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * Get the migration path
     *
     * @return ?string
     */
    public function getCurrent(): ?string
    {
        return $this->current;
    }

    /**
      * Load the current migration timestamp
      *
      * @return void
    */
    protected function loadCurrent(): void
    {
        if (file_exists($this->path . DIRECTORY_SEPARATOR . '.current')) {
            $cur = file_get_contents($this->path . DIRECTORY_SEPARATOR . '.current');
            if (false !== $cur) {
                $this->current = (int)$cur;
            }
        }
    }

    /**
     * Store the current migration timestamp
     *
     * @param  int $current
     * @return void
     */
    protected function storeCurrent(int $current): void
    {
        file_put_contents($this->path . DIRECTORY_SEPARATOR . '.current', $current);
    }

    /**
     * Clear the current migration timestamp
     *
     * @return void
     */
    protected function clearCurrent(): void
    {
        if (file_exists($this->path . DIRECTORY_SEPARATOR . '.current')) {
            unlink($this->path . DIRECTORY_SEPARATOR . '.current');
        }
    }

}