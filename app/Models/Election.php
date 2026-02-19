<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Election extends Model
{
    use HasFactory;

    protected $table = 'elections';

    protected $fillable = [
        'organization_id',
        'title',
        'description',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'timezone',

        'send_email_invitations',
        'enable_public_link',
        'display_results_publicly',
        
    ];


    protected $appends = ['total_eligible_voters'];

    public function voters()
    {
        return $this->hasMany(Voter::class);
    }

    public function getTotalEligibleVotersAttribute()
    {
        return $this->voters()->count();
    }

    public function organization()
    {
        return $this->belongsTo(User::class, 'organization_id');
    }

    public function candidates()
    {
        return $this->hasMany(Candidate::class);
    }

    public function votes()
    {
        return $this->hasMany(\App\Models\Vote::class);
    }

    
}
