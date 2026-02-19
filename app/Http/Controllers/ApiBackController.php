<?php

namespace App\Http\Controllers;
use App\Models\Admin;
use App\Models\User;
use App\Models\Election;
use App\Models\Vote;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ApiBackController extends Controller
{

    

// public function register(Request $request)
// {
//     $validator = Validator::make($request->all(), [
//         'username' => 'required|string|max:255',
//         'email'    => 'required|email|unique:admins,email',
//         'password' => 'required|string|min:8|confirmed',  // password_confirmation required in request
//     ]);

//     if ($validator->fails()) {
//         return response()->json(['errors' => $validator->errors()], 422);
//     }

//     $admin = Admin::create([
//         'username' => $request->username,
//         'email'    => $request->email,
//         'password_hash' => Hash::make($request->password),  // store hashed password in password_hash
//     ]);

//     return response()->json([
//         'message' => 'Admin registered successfully.',
//         'token'   => $admin->createToken('admin-token', ['admins'])->plainTextToken,
//         'admin'   => $admin->getSessionData(),
//     ], 201);
// }

public function login(Request $request)
{
    $validator = Validator::make($request->all(), [
        'username' => 'required|string',
        'password' => 'required|string',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $admin = Admin::findByCredentials($request->username);

    if (!$admin || !$admin->checkPassword($request->password)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    return response()->json([
        'token' => $admin->createToken('admin-token')->plainTextToken,
        'admin' => $admin->getSessionData(),
    ]);
}

public function logout(Request $request)
{
    $admin = $request->user('admin_api');  

    if ($admin && $admin->currentAccessToken()) {
        $admin->currentAccessToken()->delete();
        return response()->json(['message' => 'Admin logged out successfully.']);
    }

    return response()->json(['message' => 'Unauthenticated.'], 401);
}



    public function dashboard(Request $request)
{
    $keyword = $request->get('keyword');

    $query = User::query();

    if ($keyword) {
        $query->where(function($q) use ($keyword) {
            $q->where('name', 'like', '%' . $keyword . '%')
              ->orWhere('organization_email', 'like', '%' . $keyword . '%')
              ->orWhere('organization_name', 'like', '%' . $keyword . '%');
        });
    }

    // Get all matching users
    $users = $query->orderBy('created_at', 'desc')->get();

    // Group them by account_type
    $grouped = $users->groupBy('account_type');

    // Count types
    $schoolCount = $grouped->get('school')?->count() ?? 0;
    $organizationCount = $grouped->get('organization')?->count() ?? 0;

    return response()->json([
        'school_count' => $schoolCount,
        'organization_count' => $organizationCount,
        'school' => $grouped->get('school') ?? [],
        'organization' => $grouped->get('organization') ?? [],
        
    ]);
    }


    public function profile($id)
{
    $organization = User::with('elections')->find($id);

    if (!$organization) {
        return response()->json([
            'success' => false,
            'message' => 'Organization not found.',
        ], 404);
    }

    // Clean up elections output
    $elections = $organization->elections->map(function ($election) {
        return [
            'id' => $election->id,
            'title' => $election->title,
            'description' => $election->description,
            'start_date' => $election->start_date,
            'end_date' => $election->end_date,
            'status' => $election->status,
            'start_time' => $election->start_time,
            'end_time' => $election->end_time,
            'timezone' => $election->timezone,
            'created_at' => $election->created_at,
            'updated_at' => $election->updated_at,
            'total_eligible_voters' => $election->total_eligible_voters,
        ];
    });

    $formattedOrganization = [
        'id' => $organization->id,
        'name' => $organization->name,
        'organization_name' => $organization->organization_name,
        'organization_email' => $organization->organization_email,

    ];

    return response()->json([
        'success' => true,
        'data' => [
            'organization' => $formattedOrganization,
            'elections_count' => $elections->count(),
            'elections' => $elections,
            
        ]
    ]);
}





    public function results(Election $election)
{
    // Force loading total_eligible_voters
    $totalEligible = $election->total_eligible_voters;

    $candidates = $election->candidates()->get();

    $results = $candidates->map(function ($candidate) use ($election) {
        $voteCount = DB::table('votes')
            ->where('election_id', $election->id)
            ->where('candidate_id', $candidate->id)
            ->count();

        return [
            'candidate_id' => $candidate->id,
            'candidate_name' => ($candidate->name === 'N/A' || $candidate->name === 'n/a')
                ? 'Candidate ' . $candidate->id
                : $candidate->name,
            'vote_count' => $voteCount,
        ];
    });

    $totalVotes = DB::table('votes')
        ->where('election_id', $election->id)
        ->count();

    $turnoutPercentage = $totalEligible > 0
        ? ($totalVotes / $totalEligible) * 100
        : 0;

    return response()->json([
        'success' => true,
        'data' => [
            'election' => [
                'id' => $election->id,
                'name' => $election->title,
                'total_eligible_voters' => $totalEligible,
                // Add other election fields as needed
            ],
            'results' => $results->sortByDesc('vote_count')->values(),
            'total_votes' => $totalVotes,
            'candidates_count' => $candidates->count(),
            'turnout_percentage' => round($turnoutPercentage, 2),
        ]
    ]);
}


    private function getVoteTimeline(Election $election)
{
    $startDate = $election->start_date ? Carbon::parse($election->start_date) : now()->subDays(7);
$endDate = $election->end_date ? Carbon::parse($election->end_date) : now();

    if ($election->votes()->exists()) {
        $timelineData = DB::table('votes')
            ->selectRaw('DATE(voted_at) as date, HOUR(voted_at) as hour, COUNT(*) as vote_count')
            ->where('election_id', $election->id)
            ->whereBetween('voted_at', [$startDate, $endDate])
            ->groupBy('date', 'hour')
            ->orderBy('date')
            ->orderBy('hour')
            ->get();
    } else {
        // For upcoming elections: generate mock data
        $timelineData = collect();
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            for ($hour = 0; $hour < 24; $hour++) {
                $timelineData->push((object)[
                    'date' => $currentDate->format('Y-m-d'),
                    'hour' => $hour,
                    'vote_count' => rand(0, 5)
                ]);
            }
            $currentDate->addDay();
        }
    }

    // Format data
    $formattedData = [];
    foreach ($timelineData as $data) {
        $timestamp = $data->date . ' ' . str_pad($data->hour, 2, '0', STR_PAD_LEFT) . ':00:00';
        $formattedData[] = [
            'timestamp' => $timestamp,
            'votes' => $data->vote_count,
            'label' => date('M d, H:i', strtotime($timestamp))
        ];
    }

    return $formattedData;
}


    public function timeline(Election $election)
    {
        $timeline = $this->getVoteTimeline($election);
        
        return response()->json([
            'success' => true,
            'data' => $timeline
        ]);
    }



}

