<?php

namespace Sinapsteknologi\NextcloudManager\Models;

use Illuminate\Database\Eloquent\Model;

class NextcloudFile extends Model
{
    protected $table = 'nextcloud_files';

    protected $fillable = [
        'name',
        'path',
        'url',
        'share_id',
    ];
}
