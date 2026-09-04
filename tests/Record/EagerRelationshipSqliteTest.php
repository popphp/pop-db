<?php

namespace Pop\Db\Test\Record;

use Pop\Db\Adapter\AbstractAdapter;
use Pop\Db\Db;

class EagerRelationshipSqliteTest extends EagerRelationshipTestCase
{

    protected function createDb(): AbstractAdapter
    {
        $file = __DIR__ . '/../tmp/eager.sqlite';

        if (file_exists($file)) {
            unlink($file);
        }
        chmod(__DIR__ . '/../tmp', 0777);
        touch($file);
        chmod($file, 0777);

        $this->sqliteFile = $file;

        return Db::sqliteConnect(['database' => $file]);
    }

}
