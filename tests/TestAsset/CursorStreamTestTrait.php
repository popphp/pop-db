<?php

namespace Pop\Db\Test\TestAsset;

/**
 * Behaviour of stream() on a PostgreSQL server-side cursor (Pgsql on libpq < 17, and PDO pgsql),
 * which runs inside a transaction. The using class also uses StreamTestTrait.
 */
trait CursorStreamTestTrait
{

    public function testCursorStreamOwnTransactionEndsWithStream()
    {
        $db = $this->createStreamAdapter();
        $this->createStreamFixture($db);

        $this->assertFalse($db->isTransaction());
        $inside = [];
        foreach ($db->stream('SELECT * FROM stream_items ORDER BY id') as $row) {
            $inside[] = $db->isTransaction();
        }

        $this->assertCount(50, $inside);
        $this->assertNotContains(false, $inside);
        $this->assertFalse($db->isTransaction());
        $this->dropStreamFixture($db);
    }

    public function testCursorStreamInsideCallerTransactionLeavesItOpen()
    {
        $db = $this->createStreamAdapter();
        $this->createStreamFixture($db);

        $db->beginTransaction();
        $this->assertCount(50, iterator_to_array($db->stream('SELECT * FROM stream_items'), false));

        $this->assertTrue($db->isTransaction());
        $this->assertEquals(1, $db->getTransactionDepth());
        $db->rollback();
        $this->assertFalse($db->isTransaction());
        $this->dropStreamFixture($db);
    }

    public function testCursorStreamErrorRollsBackOwnTransaction()
    {
        $db = $this->createStreamAdapter();
        $this->createStreamFixture($db);

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

        $this->assertFalse($db->isTransaction());
        $this->assertEquals(50, $this->countStreamItems($db));
        $this->dropStreamFixture($db);
    }

    public function testCursorStreamFetchesInBatches()
    {
        $db  = $this->createStreamAdapter();
        $ids = [];
        foreach ($db->stream('SELECT g AS id FROM generate_series(1, 2500) g ORDER BY g') as $row) {
            $ids[] = (int)$row['id'];
        }

        $this->assertSame(range(1, 2500), $ids);
        $db->disconnect();
    }

    public function testCursorStreamRuntimeErrorDuringFetchThrows()
    {
        $db = $this->createStreamAdapter();
        $this->createStreamFixture($db);

        // DECLARE only plans the query; the division by zero happens during FETCH
        set_error_handler(fn() => true, E_WARNING);
        try {
            foreach ($db->stream('SELECT 1 / (g - 3) AS v FROM generate_series(1, 5) g') as $row) {
            }
            $this->fail('A runtime error during FETCH did not throw');
        } catch (\Pop\Db\Adapter\Exception $e) {
            $this->assertStringContainsString('division by zero', $e->getMessage());
        } finally {
            restore_error_handler();
        }

        $this->assertFalse($db->isTransaction());
        $this->assertEquals(50, $this->countStreamItems($db));
        $this->dropStreamFixture($db);
    }

    public function testCursorStreamProfilesCallersQuery()
    {
        $db = $this->createStreamAdapter();
        $this->createStreamFixture($db);

        $profiler = new \Pop\Db\Adapter\Profiler\Profiler();
        $db->setProfiler($profiler);
        $sql = 'SELECT * FROM stream_items ORDER BY id';
        iterator_to_array($db->stream($sql), false);

        $queries = array_map(fn($step) => $step->getQuery(), $profiler->getSteps());
        $this->assertContains($sql, $queries);
        foreach ($queries as $query) {
            $this->assertStringStartsNotWith('DECLARE', (string)$query);
        }
        $this->dropStreamFixture($db);
    }

}
