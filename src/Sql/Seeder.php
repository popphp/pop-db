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
namespace Pop\Db\Sql;

use Pop\Db\Adapter\AbstractAdapter;
use Pop\Db\Db;

/**
 * Sql seeder class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.6.0
 */
class Seeder
{

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
            'SeederTemplate', $class, file_get_contents(__DIR__ . '/Seeder/Template/SeederTemplate.php')
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
     * Run the seeder
     *
     * @param  AbstractAdapter $db
     * @param  string          $path
     * @param  bool            $clear
     * @return array
     *@throws Exception|\Pop\Db\Exception
     */
    public static function run(AbstractAdapter $db, string $path, bool $clear = true): array
    {
        if (!file_exists($path)) {
            throw new Exception("Error: That path doesn't not exist.");
        }

        // Clear the database out
        if ($clear) {
            $schema = $db->createSchema();
            $tables = $db->getTables();

            if (($db instanceof \Pop\Db\Adapter\Mysql) ||
                (($db instanceof \Pop\Db\Adapter\Pdo) && ($db->getType() == 'mysql'))) {
                $db->query('SET foreign_key_checks = 0');
                foreach ($tables as $table) {
                    $schema->drop($table);
                    $db->query($schema);
                }
                $db->query('SET foreign_key_checks = 1');
            } else if (($db instanceof \Pop\Db\Adapter\Pgsql) ||
                (($db instanceof \Pop\Db\Adapter\Pdo) && ($db->getType() == 'pgsql'))) {
                foreach ($tables as $table) {
                    $schema->drop($table)->cascade();
                    $db->query($schema);
                }
            } else {
                foreach ($tables as $table) {
                    $schema->drop($table);
                    $db->query($schema);
                }
            }
        }

        $seedFilesRun = [];
        $seedFiles    = array_filter(scandir($path), function($value) {
            return (($value != '.') && ($value != '..'));
        });

        // Run the SQL seeds
        foreach ($seedFiles as $seed) {
            if (stripos($seed, '.sql') !== false) {
                Db::executeSqlFile($path . DIRECTORY_SEPARATOR . $seed, $db);
            } else {
                $fileContents = trim(file_get_contents($path . DIRECTORY_SEPARATOR . $seed));
                if ((str_contains($fileContents, 'extends AbstractSeeder'))) {
                    $namespace = null;
                    if (str_contains($fileContents, 'namespace ')) {
                        $namespace = substr($fileContents, (strpos($fileContents, 'namespace ') + 10));
                        $namespace = trim(substr($namespace, 0, strpos($namespace, ';'))) . '\\';
                    }
                    $class = substr($fileContents, (strpos($fileContents, 'class ') + 6));
                    $class = $namespace . substr($class, 0, strpos($class, ' extends'));

                    include $path . DIRECTORY_SEPARATOR . $seed;
                    $dbSeed = new $class();
                    if ($dbSeed instanceof Seeder\SeederInterface) {
                        $dbSeed->run($db);
                    }
                }
            }
            $seedFilesRun[] = $seed;
        }

        return $seedFilesRun;
    }

}
