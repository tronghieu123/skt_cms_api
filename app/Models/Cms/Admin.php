<?php

declare(strict_types=1);

namespace App\Models\Cms;

use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use MongoDB\Laravel\Eloquent\Model as Eloquent;

class Admin extends Eloquent implements Authenticatable
{
    use AuthenticatableTrait, HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'group_id',
        'picture',
        'username',
        'password',
        'full_name',
        'email',
        'is_show',
        'pass_reset',
        'login_at'
    ];

    protected $connection = 'sky_cms';

    protected $table = 'admin';


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'password' => 'hashed',
    ];
}
