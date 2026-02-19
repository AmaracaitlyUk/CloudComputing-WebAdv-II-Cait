<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vote extends Model
{
    use HasFactory;

    protected $table = 'votes';

    protected $fillable = [
        'candidate_id',
        'organization_id',
        'election_id',
        'timestamp',
    ];

    public $timestamps = false; // Since you're using a custom 'timestamp' field

    // Relationships
    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function election()
    {
        return $this->belongsTo(Election::class);
    }

    public function organization()
    {
        return $this->belongsTo(User::class, 'organization_id'); // Assuming User = organization
    }
}
