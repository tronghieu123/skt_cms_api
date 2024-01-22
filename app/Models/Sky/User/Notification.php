<?php

namespace App\Models\Sky\User;
use MongoDB\Laravel\Eloquent\Model;

class Notification extends Model
{
    public $timestamps = false;
    protected $connection = 'sky_user';
    protected $table = 'notification';
}
