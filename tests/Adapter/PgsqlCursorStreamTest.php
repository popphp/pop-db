<?php

namespace Pop\Db\Test\Adapter;

use Pop\Db\Adapter\AbstractAdapter;
use Pop\Db\Test\TestAsset\PgsqlWithoutChunkedRows;
use Pop\Db\Test\TestAsset\StreamTestTrait;
use Pop\Db\Test\TestAsset\CursorStreamTestTrait;
use PHPUnit\Framework\TestCase;

/**
 * stream() on the Pgsql adapter's cursor fallback (libpq < 17)
 */
class PgsqlCursorStreamTest extends TestCase
{

    use StreamTestTrait, CursorStreamTestTrait;

    protected function createStreamAdapter(): AbstractAdapter
    {
        return new PgsqlWithoutChunkedRows([
            'database' => $_ENV['PGSQL_DB'],
            'username' => $_ENV['PGSQL_USER'],
            'password' => $_ENV['PGSQL_PASS'],
            'host'     => $_ENV['PGSQL_HOST']
        ]);
    }

    protected function streamParamQuery(): array
    {
        return ['SELECT * FROM stream_items WHERE id > $1 ORDER BY id', [40]];
    }

}
