<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $table = 'm_companies';

    protected $primaryKey = 'company_id';

    public $timestamps = true;

    protected $fillable = [
        'company_name',
        'company_address',
        'ceo_title',
        'ceo_name_kana',
        'ceo_name',
        'tel',
        'fax',
        'office_number',
        'health_office_code',
        'pension_office_code',
        'corporate_number',
        'insurer_number',
        'insurer_name',
        'insurer_address',
        'employment_office_number',
        'labor_insurance_number',
        'labor_install_category',
        'labor_install_date',
        'labor_office_category',
        'labor_industry_category',
        'founded_date',
    ];

    public function stores(): HasMany
    {
        return $this->hasMany(Store::class, 'company_id', 'company_id');
    }
}
