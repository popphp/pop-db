<?php

namespace Pop\Db\Test\Adapter\Transaction;

use Pop\Db\Adapter\Transaction\Manager;
use PHPUnit\Framework\TestCase;

class ManagerTest extends TestCase
{

    public function testFailedBeginDoesNotOpenTransaction()
    {
        $manager = new Manager();

        try {
            $manager->enter(beginFunc: function () { throw new \RuntimeException('begin failed'); });
            $this->fail('The begin failure was not propagated');
        } catch (\RuntimeException $e) {
            $this->assertEquals('begin failed', $e->getMessage());
        }

        $this->assertFalse($manager->isTransaction());
        $this->assertEquals(0, $manager->getTransactionDepth());
    }

    public function testBeginAfterFailedBeginStartsNewTransaction()
    {
        $manager = new Manager();
        $begins  = 0;

        try {
            $manager->enter(beginFunc: function () { throw new \RuntimeException('begin failed'); });
        } catch (\RuntimeException) {
        }

        $manager->enter(
            beginFunc: function () use (&$begins) { $begins++; },
            savepointFunc: function (string $sp) { $this->fail('A savepoint was created instead of a BEGIN'); }
        );

        $this->assertEquals(1, $begins);
        $this->assertTrue($manager->isTransaction());
        $this->assertEquals(1, $manager->getTransactionDepth());
    }

    public function testFailedCommitLeavesTransactionOpenForRollback()
    {
        $manager   = new Manager();
        $rollbacks = 0;

        $manager->enter(beginFunc: function () {});

        try {
            $manager->leave(true, commitFunc: function () { throw new \RuntimeException('commit failed'); });
            $this->fail('The commit failure was not propagated');
        } catch (\RuntimeException $e) {
            $this->assertEquals('commit failed', $e->getMessage());
        }

        $this->assertTrue($manager->isTransaction());
        $this->assertEquals(1, $manager->getTransactionDepth());

        $this->assertTrue($manager->leave(false, rollbackFunc: function () use (&$rollbacks) { $rollbacks++; }));
        $this->assertEquals(1, $rollbacks);
        $this->assertFalse($manager->isTransaction());
        $this->assertEquals(0, $manager->getTransactionDepth());
    }

    public function testFailedRollbackClosesTransaction()
    {
        $manager = new Manager();

        $manager->enter(beginFunc: function () {});

        try {
            $manager->leave(false, rollbackFunc: function () { throw new \RuntimeException('rollback failed'); });
            $this->fail('The rollback failure was not propagated');
        } catch (\RuntimeException $e) {
            $this->assertEquals('rollback failed', $e->getMessage());
        }

        $this->assertFalse($manager->isTransaction());
        $this->assertEquals(0, $manager->getTransactionDepth());
    }

    public function testNestedTransactionWithSavepoints()
    {
        $manager = new Manager();
        $calls   = [];

        $manager->enter(beginFunc: function () use (&$calls) { $calls[] = 'begin'; });
        $manager->enter(savepointFunc: function (string $sp) use (&$calls) { $calls[] = 'savepoint ' . $sp; });
        $this->assertEquals(2, $manager->getTransactionDepth());

        $manager->leave(true, savepointReleaseFunc: function (string $sp) use (&$calls) { $calls[] = 'release ' . $sp; });
        $manager->leave(true, commitFunc: function () use (&$calls) { $calls[] = 'commit'; });

        $this->assertEquals(['begin', 'savepoint PopDbTxn_0', 'release PopDbTxn_0', 'commit'], $calls);
        $this->assertFalse($manager->isTransaction());
    }

}
