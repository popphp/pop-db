<?php

namespace Pop\Db\Test\Record;

use Pop\Db\Adapter\AbstractAdapter;
use Pop\Db\Db;

/**
 * Every PDO connection uses named (':name') placeholders regardless of its driver, which is a
 * third placeholder style on top of the native MySQL and PostgreSQL ones, so it gets its own run.
 */
class EagerRelationshipPdoSqliteTest extends EagerRelationshipTestCase
{

    protected function createDb(): AbstractAdapter
    {
        $file = __DIR__ . '/../tmp/eager-pdo.sqlite';

        if (file_exists($file)) {
            unlink($file);
        }
        chmod(__DIR__ . '/../tmp', 0777);
        touch($file);
        chmod($file, 0777);

        $this->sqliteFile = $file;

        return Db::pdoConnect(['type' => 'sqlite', 'database' => $file]);
    }

}
