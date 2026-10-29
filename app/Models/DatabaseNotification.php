<?php
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\DatabaseNotification as BaseNotification;

class DatabaseNotification extends BaseNotification
{
    use SoftDeletes;
}
