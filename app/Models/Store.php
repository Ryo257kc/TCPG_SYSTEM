<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Store extends Model
{
    protected $table = 'm_stores';

    protected $primaryKey = 'store_id';

    public $timestamps = true;

    protected $fillable = [
        'company_id',
        'store_code',
        'store_name',
        'business_type',
        'visit_area',
        'store_short_name',
        'postal_code',
        'address_kana',
        'store_address',
        'tel',
        'fax',
        'is_closed',
        'legacy_no',
    ];

    protected $casts = [
        'is_closed' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'company_id');
    }
}
