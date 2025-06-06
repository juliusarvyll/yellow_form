<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = [
        'department_name'
    ];

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function yellowForms(): HasMany
    {
        return $this->hasMany(YellowForm::class);
    }
}
