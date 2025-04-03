<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Violation extends Model
{
    protected $fillable = [
        'violation_name',
        'violation_legend',
        'violation_description'
    ];

    public function yellowForms()
    {
        return $this->hasMany(YellowForm::class);
    }
}
