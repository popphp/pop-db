<?php

namespace Pop\Db\Test\TestAsset;

use Pop\Db\Record;

/**
 * A composite-primary-key table that is also the *target* of a composite-key hasMany/hasOne,
 * so its leaf records are looked up by their own primary key columns ('ticket_id'/'ticket_rev')
 * — deliberately named differently from the foreign key columns that point at it, so mixing
 * the two up cannot pass by coincidence.
 */
class CkTicket extends Record
{

    protected ?string $table = 'ck_tickets';
    protected array $primaryKeys = ['ticket_id', 'ticket_rev'];

    // Self-referential: children are looked up by this record's own composite primary key.
    public function children(?array $options = null, bool $eager = false)
    {
        return $this->hasMany('Pop\Db\Test\TestAsset\CkTicket', ['parent_ticket_id', 'parent_ticket_rev'], $options, $eager);
    }

}
