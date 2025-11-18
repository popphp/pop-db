<?php
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2026 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
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
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2026 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.8.0
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

        // If migration is stored in a DB table, check for table and create if does not exist
        if (($this->isTable()) && (!$this->hasTable())) {
            $this->createTable();
        }
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
        $batch      = $this->getNextBatch();

        foreach ($this->migrations as $timestamp => $migration) {
            if (strtotime($timestamp) > strtotime((int)$this->current)) {
                $stepsToRun[] = $timestamp;
            }
        }

        $numOfSteps = count($stepsToRun);

        if ($numOfSteps > 0) {
            $stop = (($steps == 'all') || ($steps > $numOfSteps)) ? $numOfSteps : (int)$steps;
            for ($i = 0; $i < $stop; $i++) {
                $class = $this->migrations[$stepsToRun[$i]]['class'];
                if (!class_exists($class)) {
                    include $this->path . DIRECTORY_SEPARATOR . $this->migrations[$stepsToRun[$i]]['filename'];
                }
                $migration = new $class($this->db);
                $migration->up();

                $current = $stepsToRun[$i];
                if ($current !== null) {
                    $this->storeCurrent($current, $this->migrations[$stepsToRun[$i]]['filename'], $batch);
                }
            }
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

        if (is_string($steps) && str_starts_with($steps, 'batch-')) {
            $stepsToRun = $this->getByBatch($steps);
        } else {
            foreach ($this->migrations as $timestamp => $migration) {
                if (strtotime($timestamp) <= strtotime((int)$this->current)) {
                    $stepsToRun[] = $timestamp;
                }
            }
        }

        $numOfSteps = count($stepsToRun);

        if ($numOfSteps > 0) {
            $stop = (($steps == 'all') || ($steps > $numOfSteps)) ? $numOfSteps : (int)$steps;
            for ($i = 0; $i < $stop; $i++) {
                $class = $this->migrations[$stepsToRun[$i]]['class'];
                if (!class_exists($class)) {
                    include $this->path . DIRECTORY_SEPARATOR . $this->migrations[$stepsToRun[$i]]['filename'];
                }
                $migration = new $class($this->db);
                $migration->down();

                $this->deleteCurrent($stepsToRun[$i], ($stepsToRun[$i + 1] ?? null));
            }
        }

        if (!isset($i) || !isset($stepsToRun[$i])) {
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

        while (($filename = readdir($handle)) !== false) {
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
     * Determine if the migration source is stored in a file
     *
     * @return bool
     */
    public function isFile(): bool
    {
        return (file_exists($this->path . DIRECTORY_SEPARATOR . '.current'));
    }

    /**
     * Determine if the migration source is stored in a DB
     *
     * @return bool
     */
    public function isTable(): bool
    {
        if (file_exists($this->path . DIRECTORY_SEPARATOR . '.table')) {
            $table = trim(file_get_contents($this->path . DIRECTORY_SEPARATOR . '.table'));
            return (class_exists($table) && is_subclass_of($table, 'Pop\Db\Record'));
        } else {
            return false;
        }
    }

    /**
     * Determine if the migration source has a table in the DB
     *
     * @return bool
     */
    public function hasTable(): bool
    {
        if ($this->isTable()) {
            $migrationTable = $this->getTable();
            return (in_array($migrationTable::table(), $this->db->getTables()));
        } else {
            return false;
        }
    }

    /**
     * Get table class string
     *
     * @return string
     */
    public function getTable(): string
    {
        return (file_exists($this->path . DIRECTORY_SEPARATOR . '.table')) ?
            trim(file_get_contents($this->path . DIRECTORY_SEPARATOR . '.table')) : '';
    }

    /**
     * Create table
     *
     * @return Migrator
     */
    public function createTable(): Migrator
    {
        if (($this->isTable()) && (!$this->hasTable())) {
            $migrationTable = $this->getTable();

            $schema = $this->db->createSchema();
            $schema->create($migrationTable::table())
                ->int('id', 16)->notNullable()->increment()
                ->varchar('migration_id', 255)
                ->varchar('class_file', 255)
                ->int('batch', 16)
                ->datetime('timestamp')->notNullable()
                ->primary('id')
                ->index('migration_id', 'migration_id')
                ->index('class_file', 'class_file')
                ->index('batch', 'batch')
                ->index('timestamp', 'timestamp');

            $schema->execute();
        }

        return $this;
    }

    /**
     * Get next batch
     *
     * @return int
     */
    public function getNextBatch(): int
    {
        $batch = 1;

        if (($this->isTable()) && ($this->hasTable())) {
            $class = $this->getTable();
            if (!empty($class)) {
                $current = $class::findOne(null, ['order' => 'batch DESC']);
                if (!empty($current->batch)) {
                    $batch = (int)$current->batch + 1;
                }
            }
        }

        return $batch;
    }

    /**
     * Get current batch
     *
     * @return int
     */
    public function getCurrentBatch(): int
    {
        $batch = 0;

        if (($this->isTable()) && ($this->hasTable())) {
            $class = $this->getTable();
            if (!empty($class)) {
                $current = $class::findOne(null, ['order' => 'batch DESC']);
                if (!empty($current->batch)) {
                    $batch = (int)$current->batch;
                }
            }
        }

        return $batch;
    }

    /**
     * Get migrations by batch
     *
     * @param  string|int $batch
     * @return array
     */
    public function getByBatch(string|int $batch): array
    {
        if (is_string($batch) && str_starts_with($batch, 'batch-')) {
            $batch = substr($batch, 6);
        }

        $batchMigrations = [];

        if (($this->isTable()) && ($this->hasTable()) && ($batch == $this->getCurrentBatch())) {
            $class = $this->getTable();
            if (!empty($class)) {
                $batchMigrations = array_values(
                    $class::findBy(['batch' => $batch], ['order' => 'migration_id DESC'])->toArray(['column' => 'migration_id'])
                );
            }
        }

        return $batchMigrations;
    }

    /**
      * Load the current migration timestamp
      *
      * @return void
    */
    protected function loadCurrent(): void
    {
        if (($this->isTable()) && ($this->hasTable())) {
            $class = $this->getTable();
            if (!empty($class)) {
                $current = $class::findOne(null, ['order' => 'id DESC']);
                if (isset($current->id)) {
                    $this->current = (int)$current->migration_id;
                }
            }
        } else if ($this->isFile()) {
            $current = file_get_contents($this->path . DIRECTORY_SEPARATOR . '.current');
            if ($current !== false) {
                $this->current = (int)$current;
            }
        }
    }

    /**
     * Store the current migration timestamp
     *
     * @param int    $current
     * @param string $classFile
     * @param ?int   $batch
     * @return void
     */
    protected function storeCurrent(int $current, string $classFile, ?int $batch = null): void
    {
        if (($this->isTable()) && ($this->hasTable())) {
            $class = $this->getTable();
            if (!empty($class)) {
                $migration = new $class([
                    'migration_id' => $current,
                    'class_file'   => $classFile,
                    'batch'        => $batch,
                    'timestamp'    => date('Y-m-d H:i:s')
                ]);
                $migration->save();
            }
        } else {
            file_put_contents($this->path . DIRECTORY_SEPARATOR . '.current', $current);
        }

        $this->current = $current;
    }

    /**
     * Delete migration
     *
     * @param  int  $current
     * @param  ?int $previous
     * @return void
     */
    protected function deleteCurrent(int $current, ?int $previous = null): void
    {
        if (($this->isTable()) && ($this->hasTable())) {
            if (($this->isTable()) && ($this->hasTable())) {
                $class     = $this->getTable();
                $migration = $class::findOne(['migration_id' => $current]);
                if (isset($migration->id)) {
                    $migration->delete();
                }
            }
        } else if ($this->isFile()) {
            if ($previous !== null) {
                file_put_contents($this->path . DIRECTORY_SEPARATOR . '.current', $previous);
            } else {
                unlink($this->path . DIRECTORY_SEPARATOR . '.current');
            }
        }

        $this->loadCurrent();
    }

    /**
     * Clear migrations
     *
     * @return void
     */
    protected function clearCurrent(): void
    {
        if (($this->isTable()) && ($this->hasTable())) {
            if (($this->isTable()) && ($this->hasTable())) {
                $class = $this->getTable();
                $count = $class::total();
                if ($count > 0) {
                    $migrations = new $class();
                    $migrations->delete();
                }
            }
        } else if ($this->isFile()) {
            if (file_exists($this->path . DIRECTORY_SEPARATOR . '.current')) {
                unlink($this->path . DIRECTORY_SEPARATOR . '.current');
            }
        }

        $this->current = null;
    }

}
