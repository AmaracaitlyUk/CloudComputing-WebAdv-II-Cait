<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable
{
    use HasApiTokens;
    protected $table = 'admins';

    // /
    //  * The attributes that are mass assignable.
    //  *
    //  * @var array<int, string>
    //  */
    protected $fillable = [
        'username',
        'email',
        'password_hash',
    ];

    // /
    //  * The attributes that should be hidden for serialization.
    //  *
    //  * @var array<int, string>
    //  */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }


        public function checkPassword($inputPassword)
    {
        return \Illuminate\Support\Facades\Hash::check($inputPassword, $this->password_hash);
    }

    public function getSessionData()
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
        ];
    }

    public static function findByCredentials($usernameOrEmail)
    {
        return self::where('username', $usernameOrEmail)
                   ->orWhere('email', $usernameOrEmail)
                   ->first();
    }



}