<?php

namespace Pop\Db\Test\TestAsset;

use Pop\Db\Record;

class HookedUsers extends Record
{

    protected ?string $table = 'users';

    public array $hookLog = [];

    public bool $throwInBeforeSave   = false;
    public bool $throwInBeforeInsert = false;
    public bool $throwInBeforeUpdate = false;
    public bool $throwInBeforeDelete = false;
    public bool $throwInAfterDelete  = false;

    protected function beforeSave(): void
    {
        $this->hookLog[] = 'beforeSave';
        if ($this->throwInBeforeSave) {
            throw new \Pop\Db\Record\Exception('Aborted in beforeSave');
        }
    }

    protected function afterSave(): void
    {
        $this->hookLog[] = 'afterSave';
    }

    protected function beforeInsert(): void
    {
        $this->hookLog[] = 'beforeInsert';
        if ($this->throwInBeforeInsert) {
            throw new \Pop\Db\Record\Exception('Aborted in beforeInsert');
        }
    }

    protected function afterInsert(): void
    {
        $this->hookLog[] = 'afterInsert';
    }

    protected function beforeUpdate(): void
    {
        $this->hookLog[] = 'beforeUpdate';
        if ($this->throwInBeforeUpdate) {
            throw new \Pop\Db\Record\Exception('Aborted in beforeUpdate');
        }
    }

    protected function afterUpdate(): void
    {
        $this->hookLog[] = 'afterUpdate';
    }

    protected function beforeDelete(): void
    {
        $this->hookLog[] = 'beforeDelete';
        if ($this->throwInBeforeDelete) {
            throw new \Pop\Db\Record\Exception('Aborted in beforeDelete');
        }
    }

    protected function afterDelete(): void
    {
        $this->hookLog[] = 'afterDelete:id=' . $this->id;
        if ($this->throwInAfterDelete) {
            throw new \Pop\Db\Record\Exception('Aborted in afterDelete');
        }
    }

}
