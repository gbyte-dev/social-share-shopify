<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingShopRedaction extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pending_shop_redactions';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'shop_domain',
        'store_id',
        'attempts',
        'last_attempt_at',
        'last_error',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'store_id' => 'integer',
            'attempts' => 'integer',
            'last_attempt_at' => 'datetime',
        ];
    }
}
