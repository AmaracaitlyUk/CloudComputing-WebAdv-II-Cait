<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voter extends Model
{
    use HasFactory;

    protected $table = 'voters'; 
    protected $fillable = [
        'voter_id',
        'organization_id',
        'name',
        'email',
        'voter_key',
        'used',
        'election_id'
    ];

    // Optional: cast "used" to boolean if it's stored as tinyint
    protected $casts = [
        'used' => 'boolean',
    ];

    // Optional: relationships
    public function organization()
    {
        return $this->belongsTo(User::class, 'organization_id');
    }

    // Add more relationships if needed (like elections or votes)
}
