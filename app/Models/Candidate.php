<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Candidate extends Model
{
    use HasFactory;
 protected $table = 'candidates';
    protected $fillable = [
        'election_id',
        'name',
        'description',
        'image_path',
    
    ];

    /**
     * Relationship to Election
     */
    public function election()
    {
        return $this->belongsTo(Election::class);
    }

    public function votes()
    {
        return $this->hasMany(Vote::class);
    }

}
