<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterSocialMedia extends Model
{
    protected $table = 'master_social_medias';

    protected $fillable = ['name', 'prefix', 'icon_url'];
}
