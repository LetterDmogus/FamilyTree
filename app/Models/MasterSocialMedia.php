<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterSocialMedia extends Model
{
    use SoftDeletes;

    protected $table = 'master_social_medias';

    protected $fillable = ['name', 'prefix', 'icon_url'];
}
