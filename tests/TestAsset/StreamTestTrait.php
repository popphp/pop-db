<?php

namespace Pop\Db\Test\TestAsset;

use Pop\Db\Adapter\AbstractAdapter;

/**
 * Behaviour every adapter's stream() must share. The using test class supplies a connected
 * adapter and a parameterized query in that adapter's own placeholder style.
 */
trait StreamTestTrait
{

    /**
     * Return a connected adapter to stream from
     *
     * @return AbstractAdapter
     */
    abstract protected function createStreamAdapter(): AbstractAdapter;

    /**
     * Return [sql, params] selecting the stream_items rows with id > 40, ordered by id
     *
     * @return array
     */
    abstract protected function streamParamQuery(): array;

    protected function createStreamFixture(AbstractAdapter $db): void
    {
        $values = [];
        for ($i = 1; $i <= 50; $i++) {
            $values[] = '(' . $i . ", 'item" . $i . "')";
        }

        $db->query('DROP TABLE IF EXISTS stream_items');
        $db->query('CREATE TABLE stream_items (id INT PRIMARY KEY, name VARCHAR(50))');
        $db->query('INSERT INTO stream_items (id, name) VALUES ' . implode(', ', $values));
    }

    protected function dropStreamFixture(AbstractAdapter $db): void
    {
        $db->query('DROP TABLE IF EXISTS stream_items');
        $db->disconnect();
    }

    protected function countStreamItems(AbstractAdapter $db): int
    {
        // Read to the end, so no half-read statement is left open on the connection
        $db->query('SELECT COUNT(*) AS c FROM stream_items');
        $rows = $this->fetchAllRows($db);
        return (int)$rows[0]['c'];
    }

    /**
     * Read every row of the last query() through fetch(), which every adapter supports after query()
     */
    protected function fetchAllRows(AbstractAdapter $db): array
    {
        $rows = [];
        while (($row = $db->fetch())) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function testStreamYieldsSameRowsAsFetchAll()
    {
        $db = $this->createStreamAdapter();
        $this->createStreamFixture($db);

        $db->query('SELECT * FROM stream_items ORDER BY id');
        $expected = $this->fetchAllRows($db);

        $streamed = [];
        foreach ($db->stream('SELECT * FROM stream_items ORDER BY id') as $row) {
            $streamed[] = $row;
        }

        $this->assertCount(50, $streamed);
        $this->assertEquals($expected, $streamed);
        $this->dropStreamFixture($db);
    }

    public function testStreamBindsParams()
    {
        $db = $this->createStreamAdapter();
        $this->createStreamFixture($db);

        [$sql, $params] = $this->streamParamQuery();
        $ids = [];
        foreach ($db->stream($sql, $params) as $row) {
            $ids[] = (int)$row['id'];
        }

        $this->assertSame(range(41, 50), $ids);
        $this->dropStreamFixture($db);
    }

    public function testStreamAcceptsSqlBuilder()
    {
        $db = $this->createStreamAdapter();
        $this->createStreamFixture($db);

        $sql = $db->createSql();
        $sql->select()->from('stream_items');

        $this->assertCount(50, iterator_to_array($db->stream($sql), false));
        $this->dropStreamFixture($db);
    }

    public function testStreamEmptyResult()
    {
        $db = $this->createStreamAdapter();
        $this->createStreamFixture($db);

        $this->assertSame([], iterator_to_array($db->stream('SELECT * FROM stream_items WHERE id > 1000'), false));
        $this->assertEquals(50, $this->countStreamItems($db));
        $this->dropStreamFixture($db);
    }

    public function testStreamBreakEarlyLeavesConnectionUsable()
    {
        $db = $this->createStreamAdapter();
        $this->createStreamFixture($db);

        $stream = $db->stream('SELECT * FROM stream_items ORDER BY id');
        $seen   = 0;
        foreach ($stream as $row) {
            if (++$seen == 3) {
                break;
            }
        }
        unset($stream);

        $this->assertEquals(3, $seen);
        $this->assertEquals(50, $this->countStreamItems($db));
        $this->dropStreamFixture($db);
    }

    public function testStreamCallerExceptionLeavesConnectionUsable()
    {
        $db = $this->createStreamAdapter();
        $this->createStreamFixture($db);

        $stream = $db->stream('SELECT * FROM stream_items ORDER BY id');
        try {
            foreach ($stream as $i => $row) {
                if ($i == 2) {
                    throw new \RuntimeException('caller failure');
                }
            }
            $this->fail('The loop body exception was not raised');
        } catch (\RuntimeException $e) {
            $this->assertEquals('caller failure', $e->getMessage());
        }
        unset($stream);

        $this->assertEquals(50, $this->countStreamItems($db));
        $this->dropStreamFixture($db);
    }

    public function testStreamBadSqlThrowsAdapterException()
    {
        $db = $this->createStreamAdapter();
        $this->createStreamFixture($db);

        // Some drivers' prepare() also emits a PHP warning alongside the adapter exception;
        // that existing adapter behaviour is not what this test checks
        set_error_handler(fn() => true, E_WARNING);
        try {
            foreach ($db->stream('SELECT * FROM no_such_table') as $row) {
            }
            $this->fail('Streaming invalid SQL did not throw');
        } catch (\Pop\Db\Adapter\Exception $e) {
            $this->assertNotEmpty($e->getMessage());
        } finally {
            restore_error_handler();
        }

        $this->assertEquals(50, $this->countStreamItems($db));
        $this->dropStreamFixture($db);
    }

    public function testStreamLeavesNoStaleResult()
    {
        $db = $this->createStreamAdapter();
        $this->createStreamFixture($db);

        // A result from an earlier query() must not be readable through fetch() after a stream
        $db->query('SELECT * FROM stream_items');
        iterator_to_array($db->stream('SELECT * FROM stream_items WHERE id > 1000'), false);

        try {
            $db->fetch();
            $this->fail('fetch() after a stream read a stale result');
        } catch (\Pop\Db\Adapter\Exception $e) {
            $this->assertStringContainsString('not currently set', $e->getMessage());
        }

        $this->dropStreamFixture($db);
    }

    public function testStreamDestroyedAfterDisconnectIsSilent()
    {
        $db = $this->createStreamAdapter();
        $this->createStreamFixture($db);

        $stream = $db->stream('SELECT * FROM stream_items ORDER BY id');
        $this->assertEquals(1, (int)$stream->current()['id']);
        $db->disconnect();
        unset($stream);

        $this->dropStreamFixture($this->createStreamAdapter());
    }

}
