<?php

namespace App\Models\Sky\User;
use MongoDB\Laravel\Eloquent\Model;

class User_Notification_Autosend extends Model
{
    public $timestamps = false;
    protected $connection = 'sky_user';
    protected $table = 'user_notification_autosend';
}
