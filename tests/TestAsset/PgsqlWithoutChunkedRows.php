<?php

namespace Pop\Db\Test\TestAsset;

use Pop\Db\Adapter\Pgsql;

/**
 * A Pgsql adapter that reports no chunked-rows support, so stream() takes the cursor fallback
 * used with libpq < 17
 */
class PgsqlWithoutChunkedRows extends Pgsql
{

    protected function supportsChunkedRows(): bool
    {
        return false;
    }

}
