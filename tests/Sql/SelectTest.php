<?php

namespace Pop\Db\Test\Sql;

use Pop\Db\Db;
use PHPUnit\Framework\TestCase;

class SelectTest extends TestCase
{

    protected $db = null;

    public function setUp(): void
    {
        $this->db = Db::mysqlConnect([
            'database' => $_ENV['MYSQL_DB'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASS'],
            'host'     => $_ENV['MYSQL_HOST']
        ]);
    }

    public function testDistinct()
    {
        $sql = $this->db->createSql();
        $sql->select(['username'])->distinct()->from('users');
        $this->assertEquals('SELECT DISTINCT `username` FROM `users`', (string)$sql);
        $this->db->disconnect();
    }

    public function testAlias()
    {
        $sql = $this->db->createSql();
        $sql->select(['username'])->from('users');
        $sql->select()->asAlias('test_table');
        $this->assertEquals('(SELECT `username` FROM `users`) AS `test_table`', (string)$sql);
        $this->db->disconnect();
    }

    public function testTableAlias()
    {
        $sql = $this->db->createSql();
        $sql->select(['u.username'])->from(['u' => 'users']);
        $this->assertEquals('SELECT `u`.`username` FROM `users` AS `u`', $sql->render());
    }

    public function testFromWithMultipleAliasedTablesThrowsException()
    {
        $this->expectException(\Pop\Db\Sql\Exception::class);
        $sql = $this->db->createSql();
        $sql->select()->from(['a' => 'table1', 'b' => 'table2'])->render();
    }

    public function testSelectSubqueryColumnWithAlias()
    {
        $sql      = $this->db->createSql();
        $subquery = $this->db->createSql()->select('MAX(total)')->from('orders');

        $sql->select(['max_total' => $subquery])->from('orders');
        $this->assertEquals('SELECT (SELECT MAX(total) FROM `orders`) AS `max_total` FROM `orders`', $sql->render());
        $this->db->disconnect();
    }

    public function testJsonExtractMysql()
    {
        $sql = $this->db->createSql();
        $extract = $sql->jsonExtract('data', '$.name');
        $this->assertEquals("JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.name'))", (string)$extract);
        $this->db->disconnect();
    }

    public function testJsonExtractMysqlNestedPath()
    {
        $sql = $this->db->createSql();
        $extract = $sql->jsonExtract('data', '$.address.city');
        $this->assertEquals("JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.address.city'))", (string)$extract);
        $this->db->disconnect();
    }

    public function testJsonExtractPgsqlTopLevelKey()
    {
        $db  = Db::pgsqlConnect([
            'database' => $_ENV['PGSQL_DB'],
            'username' => $_ENV['PGSQL_USER'],
            'password' => $_ENV['PGSQL_PASS'],
            'host'     => $_ENV['PGSQL_HOST']
        ]);
        $sql = $db->createSql();
        $extract = $sql->jsonExtract('data', '$.name');
        $this->assertEquals('"data"->>\'name\'', (string)$extract);
        $db->disconnect();
    }

    public function testJsonExtractPgsqlNestedPath()
    {
        $db  = Db::pgsqlConnect([
            'database' => $_ENV['PGSQL_DB'],
            'username' => $_ENV['PGSQL_USER'],
            'password' => $_ENV['PGSQL_PASS'],
            'host'     => $_ENV['PGSQL_HOST']
        ]);
        $sql = $db->createSql();
        $extract = $sql->jsonExtract('data', '$.address.city');
        $this->assertEquals('"data"#>>\'{address,city}\'', (string)$extract);
        $db->disconnect();
    }

    public function testJsonExtractPgsqlArrayIndexPath()
    {
        $db  = Db::pgsqlConnect([
            'database' => $_ENV['PGSQL_DB'],
            'username' => $_ENV['PGSQL_USER'],
            'password' => $_ENV['PGSQL_PASS'],
            'host'     => $_ENV['PGSQL_HOST']
        ]);
        $sql = $db->createSql();
        $extract = $sql->jsonExtract('data', '$.tags[0]');
        $this->assertEquals('"data"#>>\'{tags,0}\'', (string)$extract);
        $db->disconnect();
    }

    public function testJsonExtractSqlite()
    {
        touch(__DIR__ . '/../tmp/json_extract.sqlite');
        $db  = Db::sqliteConnect(['database' => __DIR__ . '/../tmp/json_extract.sqlite']);
        $sql = $db->createSql();
        $extract = $sql->jsonExtract('data', '$.name');
        $this->assertEquals("json_extract(\"data\", '$.name')", (string)$extract);
        $db->disconnect();
        @unlink(__DIR__ . '/../tmp/json_extract.sqlite');
    }

    public function testJsonExtractSqlsrv()
    {
        // No options are passed, so the adapter never attempts a real connection
        $sql = new \Pop\Db\Sql(new \Pop\Db\Adapter\Sqlsrv());
        $extract = $sql->jsonExtract('data', '$.name');
        $this->assertEquals("JSON_VALUE([data], '$.name')", (string)$extract);
    }

    /**
     * The legacy 'offset,limit' comma-string $limit shape can no longer be set through the
     * public limit(int $limit) setter, so it's forced via reflection to exercise this branch.
     */
    public function testPgsqlLegacyCommaLimitStringIsSplitIntoLimitAndOffset()
    {
        $db     = Db::pgsqlConnect([
            'database' => $_ENV['PGSQL_DB'],
            'username' => $_ENV['PGSQL_USER'],
            'password' => $_ENV['PGSQL_PASS'],
            'host'     => $_ENV['PGSQL_HOST']
        ]);
        $sql    = $db->createSql();
        $select = $sql->select()->from('users');
        (new \ReflectionProperty($select, 'limit'))->setValue($select, '5,10');

        $this->assertEquals('SELECT * FROM "users" LIMIT 10 OFFSET 5', $select->render());
        $db->disconnect();
    }

    public function testSqlsrvLegacyCommaLimitStringIsSplitIntoLimitAndOffset()
    {
        $sql    = new \Pop\Db\Sql(new \Pop\Db\Adapter\Sqlsrv());
        $select = $sql->select()->from('users')->orderBy('id');
        (new \ReflectionProperty($select, 'limit'))->setValue($select, '5,10');

        $result = $select->render();
        $this->assertStringContainsString('BETWEEN 6 AND 15', $result);
    }

    public function testSqlsrvLimitWithoutOrderByThrowsException()
    {
        $this->expectException(\Pop\Db\Sql\Exception::class);
        $sql = new \Pop\Db\Sql(new \Pop\Db\Adapter\Sqlsrv());
        $sql->select()->from('users')->limit(5)->render();
    }

    public function testSqlsrvOffsetWithLimitUsesRowNumberBetween()
    {
        $sql = new \Pop\Db\Sql(new \Pop\Db\Adapter\Sqlsrv());
        $result = $sql->select()->from('users')->orderBy('id')->limit(10)->offset(5)->render();

        $this->assertStringContainsString('ROW_NUMBER() OVER (ORDER BY [id] ASC) AS RowNumber', $result);
        $this->assertStringContainsString('BETWEEN', $result);
    }

    public function testSqlsrvOffsetWithoutLimitUsesRowNumberGreaterThanOrEqualTo()
    {
        $sql = new \Pop\Db\Sql(new \Pop\Db\Adapter\Sqlsrv());
        $result = $sql->select()->from('users')->orderBy('id')->offset(5)->render();

        $this->assertStringContainsString('ROW_NUMBER() OVER (ORDER BY [id] ASC) AS RowNumber', $result);
        $this->assertStringContainsString('>=', $result);
    }

    /**
     * KNOWN BUG (not fixed here - needs a design decision, flagged separately): the
     * no-offset branch of Select::buildSqlSrvLimitAndOffset() does
     * str_replace('SELECT', 'SELECT TOP N', $sql) against $sql while it is still ''
     * (nothing has been built into it yet), so the replacement never matches and the
     * LIMIT is silently dropped - this renders a plain unbounded FROM clause instead
     * of a TOP-limited one. This test documents the CURRENT (broken) output rather
     * than asserting the intended behavior.
     */
    public function testSqlsrvLimitWithoutOffsetSilentlyDropsTheLimit()
    {
        $sql = new \Pop\Db\Sql(new \Pop\Db\Adapter\Sqlsrv());
        $result = $sql->select()->from('users')->orderBy('id')->limit(10)->render();

        $this->assertEquals('SELECT * FROM [users] ORDER BY [id] ASC', $result);
    }

    public function testJsonExtractUnsupportedDbTypeThrowsException()
    {
        $this->expectException(\Pop\Db\Sql\Exception::class);
        $sql = $this->db->createSql();
        (new \ReflectionProperty($sql, 'dbType'))->setValue($sql, 'UNSUPPORTED');
        $sql->jsonExtract('data', '$.name');
        $this->db->disconnect();
    }

    public function testJsonExtractAsSelectColumn()
    {
        $sql = $this->db->createSql();
        $sql->select(['id', 'extracted_name' => $sql->jsonExtract('data', '$.name')])->from('users');
        $this->assertEquals(
            "SELECT `id`, JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.name')) AS `extracted_name` FROM `users`",
            (string)$sql
        );
        $this->db->disconnect();
    }

    public function testJsonExtractInOrderBy()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users');
        $sql->select()->orderBy($sql->jsonExtract('data', '$.name'));
        $this->assertEquals(
            "SELECT * FROM `users` ORDER BY JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.name')) ASC",
            (string)$sql
        );
        $this->db->disconnect();
    }

    /**
     * The array form of orderBy() maps quoteId()/trim() over every element, which would wrap a
     * JsonExtract's whole rendered expression in identifier quotes (or fail outright). It has to
     * embed the expression verbatim, exactly as the scalar form already does, while still
     * quoting the plain column names alongside it.
     */
    public function testJsonExtractInOrderByArray()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users');
        $sql->select()->orderBy([$sql->jsonExtract('data', '$.name'), 'id']);
        $this->assertEquals(
            "SELECT * FROM `users` ORDER BY JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.name')), `id` ASC",
            (string)$sql
        );
        $this->db->disconnect();
    }

    public function testJsonExtractInGroupBy()
    {
        $sql = $this->db->createSql();
        $sql->select(['total' => 'COUNT(1)'])->from('users');
        $sql->select()->groupBy($sql->jsonExtract('data', '$.name'));
        $this->assertEquals(
            "SELECT COUNT(1) AS `total` FROM `users` GROUP BY JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.name'))",
            (string)$sql
        );
        $this->db->disconnect();
    }

    public function testJsonExtractInGroupByArray()
    {
        $sql = $this->db->createSql();
        $sql->select(['total' => 'COUNT(1)'])->from('users');
        $sql->select()->groupBy([$sql->jsonExtract('data', '$.name'), 'id']);
        $this->assertEquals(
            "SELECT COUNT(1) AS `total` FROM `users` GROUP BY JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.name')), `id`",
            (string)$sql
        );
        $this->db->disconnect();
    }

    public function testJoin()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')
            ->join('user_info', ['user_info.user_id' => 'users.id']);
        $this->assertEquals('SELECT * FROM `users` JOIN `user_info` ON ((`user_info`.`user_id` = `users`.`id`))', (string)$sql);
        $this->db->disconnect();
    }

    public function testLeftJoin()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')
            ->leftJoin('user_info', ['user_info.user_id' => 'users.id']);
        $this->assertEquals('SELECT * FROM `users` LEFT JOIN `user_info` ON ((`user_info`.`user_id` = `users`.`id`))', (string)$sql);
        $this->db->disconnect();
    }

    public function testRightJoin()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')
            ->rightJoin('user_info', ['user_info.user_id' => 'users.id']);
        $this->assertEquals('SELECT * FROM `users` RIGHT JOIN `user_info` ON ((`user_info`.`user_id` = `users`.`id`))', (string)$sql);
        $this->db->disconnect();
    }

    public function testFullJoin()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')
            ->fullJoin('user_info', ['user_info.user_id' => 'users.id']);
        $this->assertEquals('SELECT * FROM `users` FULL JOIN `user_info` ON ((`user_info`.`user_id` = `users`.`id`))', (string)$sql);
        $this->db->disconnect();
    }

    public function testOuterJoin()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')
            ->outerJoin('user_info', ['user_info.user_id' => 'users.id']);
        $this->assertEquals('SELECT * FROM `users` OUTER JOIN `user_info` ON ((`user_info`.`user_id` = `users`.`id`))', (string)$sql);
        $this->db->disconnect();
    }

    public function testLeftOuterJoin()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')
            ->leftOuterJoin('user_info', ['user_info.user_id' => 'users.id']);
        $this->assertEquals('SELECT * FROM `users` LEFT OUTER JOIN `user_info` ON ((`user_info`.`user_id` = `users`.`id`))', (string)$sql);
        $this->db->disconnect();
    }

    public function testRightOuterJoin()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')
            ->rightOuterJoin('user_info', ['user_info.user_id' => 'users.id']);
        $this->assertEquals('SELECT * FROM `users` RIGHT OUTER JOIN `user_info` ON ((`user_info`.`user_id` = `users`.`id`))', (string)$sql);
        $this->db->disconnect();
    }

    public function testFullOuterJoin()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')
            ->fullOuterJoin('user_info', ['user_info.user_id' => 'users.id']);
        $this->assertEquals('SELECT * FROM `users` FULL OUTER JOIN `user_info` ON ((`user_info`.`user_id` = `users`.`id`))', (string)$sql);
        $this->db->disconnect();
    }

    public function testInnerJoin()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')
            ->innerJoin('user_info', ['user_info.user_id' => 'users.id']);
        $this->assertEquals('SELECT * FROM `users` INNER JOIN `user_info` ON ((`user_info`.`user_id` = `users`.`id`))', (string)$sql);
        $this->db->disconnect();
    }

    public function testLeftInnerJoin()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')
            ->leftInnerJoin('user_info', ['user_info.user_id' => 'users.id']);
        $this->assertEquals('SELECT * FROM `users` LEFT INNER JOIN `user_info` ON ((`user_info`.`user_id` = `users`.`id`))', (string)$sql);
        $this->db->disconnect();
    }

    public function testRightInnerJoin()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')
            ->rightInnerJoin('user_info', ['user_info.user_id' => 'users.id']);
        $this->assertEquals('SELECT * FROM `users` RIGHT INNER JOIN `user_info` ON ((`user_info`.`user_id` = `users`.`id`))', (string)$sql);
        $this->db->disconnect();
    }

    public function testFullInnerJoin()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')
            ->fullInnerJoin('user_info', ['user_info.user_id' => 'users.id']);
        $this->assertEquals('SELECT * FROM `users` FULL INNER JOIN `user_info` ON ((`user_info`.`user_id` = `users`.`id`))', (string)$sql);
        $this->db->disconnect();
    }

    public function testMagicException()
    {
        $this->expectException('Pop\Db\Sql\Exception');
        $sql = $this->db->createSql();
        $bad = $sql->select(['email', 'total' => 'COUNT(1)'])->from('users')->bad;
    }

    public function testWhereMagic()
    {
        $sql = $this->db->createSql();
        $this->assertInstanceOf('Pop\Db\Sql\Where', $sql->select(['email', 'total' => 'COUNT(1)'])->from('users')->where);
        $this->db->disconnect();
    }

    public function testHaving()
    {
        $sql = $this->db->createSql();
        $sql->select(['email', 'total' => 'COUNT(1)'])->from('users')->having('total > 1');
        $this->assertEquals('SELECT `email`, COUNT(1) AS `total` FROM `users` HAVING (`total` > 1)', (string)$sql);
        $this->db->disconnect();
    }

    public function testHavingMagic()
    {
        $sql = $this->db->createSql();
        $this->assertInstanceOf('Pop\Db\Sql\Having', $sql->select(['email', 'total' => 'COUNT(1)'])->from('users')->having);
        $this->db->disconnect();
    }

    public function testAndHaving()
    {
        $sql = $this->db->createSql();
        $sql->select(['email', 'total' => 'COUNT(1)'])->from('users')->having('total > 1')->andHaving('total < 10');
        $this->assertEquals('SELECT `email`, COUNT(1) AS `total` FROM `users` HAVING ((`total` > 1) AND (`total` < 10))', (string)$sql);
        $this->db->disconnect();
    }

    public function testOrHaving()
    {
        $sql = $this->db->createSql();
        $sql->select(['email', 'total' => 'COUNT(1)'])->from('users')->having('total > 1')->orHaving('total = 0');
        $this->assertEquals('SELECT `email`, COUNT(1) AS `total` FROM `users` HAVING ((`total` > 1) OR (`total` = 0))', (string)$sql);
        $this->db->disconnect();
    }

    public function testHavingAnd()
    {
        $sql = $this->db->createSql();
        $sql->select(['email', 'total' => 'COUNT(1)'])->from('users')->having('total > 1 AND total < 10');
        $this->assertEquals('SELECT `email`, COUNT(1) AS `total` FROM `users` HAVING ((`total` > 1) AND (`total` < 10))', (string)$sql);
        $this->db->disconnect();
    }

    public function testHavingAndHaving()
    {
        $sql = $this->db->createSql();
        $sql->select(['email', 'total' => 'COUNT(1)'])->from('users')->having('total > 1')->andHaving('total < 10');
        $this->assertEquals('SELECT `email`, COUNT(1) AS `total` FROM `users` HAVING ((`total` > 1) AND (`total` < 10))', (string)$sql);
        $this->db->disconnect();
    }

    public function testHavingOr()
    {
        $sql = $this->db->createSql();
        $sql->select(['email', 'total' => 'COUNT(1)'])->from('users')->having('total > 1 OR total < 10');
        $this->assertEquals('SELECT `email`, COUNT(1) AS `total` FROM `users` HAVING ((`total` > 1) OR (`total` < 10))', (string)$sql);
        $this->db->disconnect();
    }

    public function testHavingOrHaving()
    {
        $sql = $this->db->createSql();
        $sql->select(['email', 'total' => 'COUNT(1)'])->from('users')->having('total > 1')->orHaving('total < 10');
        $this->assertEquals('SELECT `email`, COUNT(1) AS `total` FROM `users` HAVING ((`total` > 1) OR (`total` < 10))', (string)$sql);
        $this->db->disconnect();
    }

    public function testHavingArray()
    {
        $sql = $this->db->createSql();
        $sql->select(['email', 'total' => 'COUNT(1)'])->from('users')->having(['total > 1', 'total < 10']);
        $this->assertEquals('SELECT `email`, COUNT(1) AS `total` FROM `users` HAVING ((`total` > 1) AND (`total` < 10))', (string)$sql);
        $this->db->disconnect();
    }

    public function testHavingIgnoresAndInsideQuotedValue()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')->having("name = 'JOHNSON AND JOHNSON'");
        $this->assertStringContainsString("HAVING (`name` = 'JOHNSON AND JOHNSON')", $sql->render());
        $this->db->disconnect();
    }

    public function testAndHavingArray()
    {
        $sql = $this->db->createSql();
        $sql->select(['email', 'total' => 'COUNT(1)'])->from('users')->andHaving(['total > 1', 'total < 10']);
        $this->assertEquals('SELECT `email`, COUNT(1) AS `total` FROM `users` HAVING ((`total` > 1) AND (`total` < 10))', (string)$sql);
        $this->db->disconnect();
    }

    public function testOrHavingArray()
    {
        $sql = $this->db->createSql();
        $sql->select(['email', 'total' => 'COUNT(1)'])->from('users')->orHaving(['total > 1', 'total < 10']);
        $this->assertEquals('SELECT `email`, COUNT(1) AS `total` FROM `users` HAVING ((`total` > 1) OR (`total` < 10))', (string)$sql);
        $this->db->disconnect();
    }

    public function testGroupBy()
    {
        $sql = $this->db->createSql();
        $sql->select(['username', 'total' => 'COUNT(1)'])->from('users')->groupBy('username');
        $this->assertEquals('SELECT `username`, COUNT(1) AS `total` FROM `users` GROUP BY `username`', (string)$sql);
        $this->db->disconnect();
    }

    public function testGroupByArray()
    {
        $sql = $this->db->createSql();
        $sql->select(['username', 'email', 'total' => 'COUNT(1)'])->from('users')->groupBy(['username', 'email']);
        $this->assertEquals('SELECT `username`, `email`, COUNT(1) AS `total` FROM `users` GROUP BY `username`, `email`', (string)$sql);
        $this->db->disconnect();
    }

    public function testGroupByString()
    {
        $sql = $this->db->createSql();
        $sql->select(['username', 'email', 'total' => 'COUNT(1)'])->from('users')->groupBy('username, email');
        $this->assertEquals('SELECT `username`, `email`, COUNT(1) AS `total` FROM `users` GROUP BY `username`, `email`', (string)$sql);
        $this->db->disconnect();
    }

    public function testGroupByRendersBeforeHaving()
    {
        $sql = $this->db->createSql();
        $sql->select(['username', 'total' => 'COUNT(1)'])->from('users')
            ->groupBy('username')->having('total > 1');
        $this->assertEquals(
            'SELECT `username`, COUNT(1) AS `total` FROM `users` GROUP BY `username` HAVING (`total` > 1)',
            (string)$sql
        );
        $this->db->disconnect();
    }

    public function testClauseOrderWithWhereGroupByHavingOrderByAndLimit()
    {
        $sql = $this->db->createSql();
        $sql->select(['username', 'total' => 'COUNT(1)'])->from('users')
            ->where('id > 0')->groupBy('username')->having('total > 1')->orderBy('username')->limit(10);
        $this->assertEquals(
            'SELECT `username`, COUNT(1) AS `total` FROM `users` WHERE (`id` > 0) GROUP BY `username` ' .
            'HAVING (`total` > 1) ORDER BY `username` ASC LIMIT 10',
            (string)$sql
        );
        $this->db->disconnect();
    }

    public function testGroupByWithHavingExecutes()
    {
        $this->db->query('DROP TABLE IF EXISTS pop_group_having');
        $this->db->query(
            'CREATE TABLE pop_group_having (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(255))'
        );
        $this->db->query("INSERT INTO pop_group_having (username) VALUES ('admin')");
        $this->db->query("INSERT INTO pop_group_having (username) VALUES ('admin')");
        $this->db->query("INSERT INTO pop_group_having (username) VALUES ('guest')");

        $sql = $this->db->createSql();
        $sql->select(['username', 'total' => 'COUNT(1)'])->from('pop_group_having')
            ->groupBy('username')->having('total > 1');

        $this->db->query((string)$sql);
        $rows = $this->db->fetchAll();

        $this->assertEquals(1, count($rows));
        $this->assertEquals('admin', $rows[0]['username']);
        $this->assertEquals(2, $rows[0]['total']);

        $this->db->query('DROP TABLE IF EXISTS pop_group_having');
        $this->db->disconnect();
    }

    public function testOrderBy()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')->orderBy('username', 'ASC');
        $this->assertEquals('SELECT * FROM `users` ORDER BY `username` ASC', (string)$sql);
        $this->db->disconnect();
    }

    public function testOrderByArray()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')->orderBy(['username', 'email'], 'ASC');
        $this->assertEquals('SELECT * FROM `users` ORDER BY `username`, `email` ASC', (string)$sql);
        $this->db->disconnect();
    }

    public function testOrderByString()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')->orderBy('username, email', 'ASC');
        $this->assertEquals('SELECT * FROM `users` ORDER BY `username`, `email` ASC', (string)$sql);
        $this->db->disconnect();
    }

    public function testOrderByRandom()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')->orderBy('username', 'RAND');
        $this->assertEquals('SELECT * FROM `users` ORDER BY `username` RAND()', (string)$sql);
        $this->db->disconnect();
    }

    public function testLimit()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')->limit(1);
        $this->assertEquals('SELECT * FROM `users` LIMIT 1', (string)$sql);
        $this->db->disconnect();
    }

    public function testOffset()
    {
        $sql = $this->db->createSql();
        $sql->select()->from('users')->offset(25);
        $this->assertEquals('SELECT * FROM `users` OFFSET 25', (string)$sql->select());
        $this->db->disconnect();
    }

    public function testNestedSql()
    {
        $sql1 = $this->db->createSql();
        $sql2 = $this->db->createSql();
        $sql2->select('username')->from('users');
        $sql2->select()->setAlias('usernames');
        $sql1->select()->from($sql2);
        $this->assertEquals('SELECT * FROM (SELECT `username` FROM `users`) AS `usernames`', $sql1->render());
        $this->db->disconnect();
    }

    public function testNestedSelect()
    {
        $sql1 = $this->db->createSql();
        $sql2 = $this->db->createSql();
        $sql2->select('username')->from('users');
        $sql2->select()->setAlias('usernames');
        $sql1->select()->from($sql2->select());
        $this->assertEquals('SELECT * FROM (SELECT `username` FROM `users`) AS `usernames`', $sql1->render());

        $this->db->disconnect();
    }

    public function testInSubqueryLiveExecution()
    {
        $this->db->query('DROP TABLE IF EXISTS `sub_orders`');
        $this->db->query('DROP TABLE IF EXISTS `sub_users`');

        $this->db->query(
            'CREATE TABLE `sub_users` (`id` INT NOT NULL, `name` VARCHAR(255), PRIMARY KEY (`id`))'
        );
        $this->db->query(
            'CREATE TABLE `sub_orders` (`id` INT NOT NULL, `user_id` INT NOT NULL, `total` INT NOT NULL, PRIMARY KEY (`id`))'
        );

        $this->db->query(
            "INSERT INTO `sub_users` (`id`, `name`) VALUES (1, 'Alice'), (2, 'Bob'), (3, 'Carol')"
        );
        $this->db->query(
            "INSERT INTO `sub_orders` (`id`, `user_id`, `total`) VALUES (1, 1, 50), (2, 2, 200)"
        );

        // Subquery: users who have an order totaling >= 100.
        // Alice's only order (total=50) fails the filter, so she must be excluded ONLY because
        // the inner WHERE is actually applied - if it were silently dropped, the subquery would
        // yield {1, 2} instead of {2} and Alice would incorrectly appear in the result.
        // Bob's only order (total=200) passes the filter, so he must be included.
        // Carol has no orders at all, so she must NOT be included in the result either way.
        $subquery = $this->db->createSql()->select('user_id')->from('sub_orders');
        $subquery->where->greaterThanOrEqualTo('total', 100);

        $sql = $this->db->createSql();
        $sql->select()->from('sub_users');
        $sql->select()->where->in('id', $subquery);

        $sqlString = (string)$sql;

        $this->assertEquals(
            'SELECT * FROM `sub_users` WHERE (`id` IN (SELECT `user_id` FROM `sub_orders` WHERE (`total` >= 100)))',
            $sqlString
        );

        $this->db->query($sqlString);
        $rows = $this->db->fetchAll();
        $ids  = array_column($rows, 'id');
        sort($ids);

        $this->assertEquals([2], $ids);
        $this->assertNotContains(1, $ids);
        $this->assertNotContains(3, $ids);

        $this->db->query('DROP TABLE `sub_orders`');
        $this->db->query('DROP TABLE `sub_users`');
        $this->db->disconnect();
    }

    public function testJsonExtractLiveExecution()
    {
        $this->db->query('DROP TABLE IF EXISTS `json_extract_live_test`');
        $this->db->query(
            'CREATE TABLE `json_extract_live_test` (`id` INT NOT NULL, `data` JSON, PRIMARY KEY (`id`))'
        );

        // Row 1 and row 2 carry DIFFERENT values at the same '$.name' path. Filtering to row 2
        // and asserting on its extracted value specifically means a bug that extracted the wrong
        // row, or a path that resolved to the wrong key, would produce 'Alice' (or null) here
        // instead of 'Bob' - a detectably wrong result, not a coincidentally-identical one.
        $this->db->query(
            'INSERT INTO `json_extract_live_test` (`id`, `data`) VALUES ' .
            '(1, \'{"name": "Alice"}\'), (2, \'{"name": "Bob"}\')'
        );

        $sql = $this->db->createSql();
        $sql->select(['id', 'extracted_name' => $sql->jsonExtract('data', '$.name')])
            ->from('json_extract_live_test');
        $sql->select()->where->equalTo('id', 2);

        $this->db->query((string)$sql);
        $row = $this->db->fetch();

        $this->assertEquals('Bob', $row['extracted_name']);
        $this->assertNotEquals('Alice', $row['extracted_name']);

        $this->db->query('DROP TABLE `json_extract_live_test`');
        $this->db->disconnect();
    }

    public function testJsonEqualToLiveExecution()
    {
        $this->db->query('DROP TABLE IF EXISTS `json_equal_live_test`');
        $this->db->query(
            'CREATE TABLE `json_equal_live_test` (`id` INT NOT NULL, `data` JSON, PRIMARY KEY (`id`))'
        );

        // Rows 1 and 3 have 'role' == 'admin' at the tested path, row 2 has 'role' == 'user'.
        // If the JSON path comparison were silently ignored (matching every row) or broken
        // (matching no rows), the id set below would provably differ from {1, 3}.
        $this->db->query(
            'INSERT INTO `json_equal_live_test` (`id`, `data`) VALUES ' .
            '(1, \'{"role": "admin"}\'), (2, \'{"role": "user"}\'), (3, \'{"role": "admin"}\')'
        );

        $sql = $this->db->createSql();
        $sql->select()->from('json_equal_live_test');
        $sql->select()->where->jsonEqualTo('data', '$.role', 'admin');

        $this->db->query((string)$sql);
        $rows = $this->db->fetchAll();
        $ids  = array_column($rows, 'id');
        sort($ids);

        $this->assertEquals([1, 3], $ids);
        $this->assertNotContains(2, $ids);

        $this->db->query('DROP TABLE `json_equal_live_test`');
        $this->db->disconnect();
    }

    public function testJsonContainsLiveExecutionMysql()
    {
        $this->db->query('DROP TABLE IF EXISTS `json_contains_live_test`');
        $this->db->query(
            'CREATE TABLE `json_contains_live_test` (`id` INT NOT NULL, `data` JSON, PRIMARY KEY (`id`))'
        );

        // Rows 1 and 3 have 'admin' among the values of their 'roles' array, row 2 does not.
        // A broken containment check (matching every row or none) would provably diverge from
        // the {1, 3} result asserted below.
        $this->db->query(
            'INSERT INTO `json_contains_live_test` (`id`, `data`) VALUES ' .
            '(1, \'{"roles": ["admin", "editor"]}\'), (2, \'{"roles": ["editor"]}\'), ' .
            '(3, \'{"roles": ["admin"]}\')'
        );

        $sql = $this->db->createSql();
        $sql->select()->from('json_contains_live_test');
        $sql->select()->where->jsonContains('data', '$.roles', 'admin');

        $this->db->query((string)$sql);
        $rows = $this->db->fetchAll();
        $ids  = array_column($rows, 'id');
        sort($ids);

        $this->assertEquals([1, 3], $ids);
        $this->assertNotContains(2, $ids);

        $this->db->query('DROP TABLE `json_contains_live_test`');
        $this->db->disconnect();
    }

    public function testJsonContainsLiveExecutionPgsql()
    {
        $db = Db::pgsqlConnect([
            'database' => $_ENV['PGSQL_DB'],
            'username' => $_ENV['PGSQL_USER'],
            'password' => $_ENV['PGSQL_PASS'],
            'host'     => $_ENV['PGSQL_HOST']
        ]);

        $db->query('DROP TABLE IF EXISTS "json_contains_live_test_pg"');
        $db->query(
            'CREATE TABLE "json_contains_live_test_pg" ("id" INT NOT NULL, "data" JSONB, PRIMARY KEY ("id"))'
        );

        // Same load-bearing shape as the MySQL containment test above, run against a real
        // PostgreSQL connection to prove the '#>'/'@>' rendering path (not just the MySQL
        // JSON_CONTAINS() rendering path) actually filters correctly.
        $db->query(
            'INSERT INTO "json_contains_live_test_pg" ("id", "data") VALUES ' .
            '(1, \'{"roles": ["admin", "editor"]}\'), (2, \'{"roles": ["editor"]}\'), ' .
            '(3, \'{"roles": ["admin"]}\')'
        );

        $sql = $db->createSql();
        $sql->select()->from('json_contains_live_test_pg');
        $sql->select()->where->jsonContains('data', '$.roles', 'admin');

        $db->query((string)$sql);
        $rows = $db->fetchAll();
        $ids  = array_column($rows, 'id');
        sort($ids);

        $this->assertEquals([1, 3], $ids);
        $this->assertNotContains(2, $ids);

        $db->query('DROP TABLE "json_contains_live_test_pg"');
        $db->disconnect();
    }

    /**
     * PostgreSQL's '->>' extraction yields text and PostgreSQL will not implicitly compare text
     * to a number, so a numeric comparison value used to abort the whole query at execution time
     * with "operator does not exist: text = integer". Only a live run proves it now executes -
     * the rendered string alone cannot show a runtime type error.
     */
    public function testJsonEqualToNumericValueLiveExecutionPgsql()
    {
        $db = Db::pgsqlConnect([
            'database' => $_ENV['PGSQL_DB'],
            'username' => $_ENV['PGSQL_USER'],
            'password' => $_ENV['PGSQL_PASS'],
            'host'     => $_ENV['PGSQL_HOST']
        ]);

        $db->query('DROP TABLE IF EXISTS "json_equal_num_live_test_pg"');
        $db->query(
            'CREATE TABLE "json_equal_num_live_test_pg" ("id" INT NOT NULL, "data" JSONB, PRIMARY KEY ("id"))'
        );

        // Rows 1 and 3 carry n == 5, row 2 carries n == 7, so a comparison that silently matched
        // everything (or nothing) would provably diverge from the {1, 3} result asserted below.
        $db->query(
            'INSERT INTO "json_equal_num_live_test_pg" ("id", "data") VALUES ' .
            '(1, \'{"n": 5}\'), (2, \'{"n": 7}\'), (3, \'{"n": 5}\')'
        );

        $sql = $db->createSql();
        $sql->select()->from('json_equal_num_live_test_pg');
        $sql->select()->where->jsonEqualTo('data', '$.n', 5);

        $db->query((string)$sql);
        $rows = $db->fetchAll();
        $ids  = array_column($rows, 'id');
        sort($ids);

        $this->assertEquals([1, 3], $ids);
        $this->assertNotContains(2, $ids);

        $db->query('DROP TABLE "json_equal_num_live_test_pg"');
        $db->disconnect();
    }

    /**
     * The containment candidate must survive PredicateSet::addPredicate()'s parameter
     * normalization untouched and actually match rows on a live server - a candidate rewritten
     * into a placeholder token renders as '"$1"'::jsonb, which matches nothing, ever.
     */
    public function testJsonContainsBooleanCandidateLiveExecutionPgsql()
    {
        $db = Db::pgsqlConnect([
            'database' => $_ENV['PGSQL_DB'],
            'username' => $_ENV['PGSQL_USER'],
            'password' => $_ENV['PGSQL_PASS'],
            'host'     => $_ENV['PGSQL_HOST']
        ]);

        $db->query('DROP TABLE IF EXISTS "json_contains_bool_live_test_pg"');
        $db->query(
            'CREATE TABLE "json_contains_bool_live_test_pg" ("id" INT NOT NULL, "data" JSONB, PRIMARY KEY ("id"))'
        );

        $db->query(
            'INSERT INTO "json_contains_bool_live_test_pg" ("id", "data") VALUES ' .
            '(1, \'{"flags": [true, false]}\'), (2, \'{"flags": [false]}\'), (3, \'{"flags": [true]}\')'
        );

        $sql = $db->createSql();
        $sql->select()->from('json_contains_bool_live_test_pg');
        $sql->select()->where->jsonContains('data', '$.flags', true);

        $db->query((string)$sql);
        $rows = $db->fetchAll();
        $ids  = array_column($rows, 'id');
        sort($ids);

        $this->assertEquals([1, 3], $ids);
        $this->assertNotContains(2, $ids);

        $db->query('DROP TABLE "json_contains_bool_live_test_pg"');
        $db->disconnect();
    }

}
