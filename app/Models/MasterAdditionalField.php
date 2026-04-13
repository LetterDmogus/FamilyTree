<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterAdditionalField extends Model
{
    protected $fillable = ['name', 'icon_key', 'input_type', 'options'];

    protected function casts(): array
    {
        return [
            'options' => 'array',
        ];
    }
}
