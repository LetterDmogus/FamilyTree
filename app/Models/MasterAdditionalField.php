<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterAdditionalField extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'icon_key', 'input_type', 'options'];

    protected function casts(): array
    {
        return [
            'options' => 'array',
        ];
    }
}
