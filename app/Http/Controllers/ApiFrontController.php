<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Election;
use App\Models\Candidate;
use App\Models\Voter;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class ApiFrontController extends Controller
{
    public function register(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'organization_name' => 'required|string|max:255',
        'organization_email' => 'required|email|unique:organizations,organization_email',
        'password' => 'required|string|min:8|confirmed',
        'account_type' => 'required|in:school,organization',
        'address' => 'nullable|string|max:255',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'errors' => $validator->errors(),
        ], 422);
    }

    $user = User::create([
        'name' => $request->name,
        'organization_name' => $request->organization_name,
        'organization_email' => $request->organization_email,
        'password' => Hash::make($request->password), 
        'account_type' => $request->account_type,
        'address' => $request->address,
        'remember_token' => Str::random(10),
    ]);

    return response()->json([
        'message' => 'User registered successfully.',
        'token'   => $user->createToken('user-token')->plainTextToken,
        'user' => $user,
    ], 201);
}


public function login(Request $request)
{
    $user = User::where('organization_email', $request->organization_email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) { 
        return response()->json([
            'message' => 'Invalid credentials',
        ], 401);
    }

    return response()->json([
        'token' => $user->createToken('user-token')->plainTextToken,
    ]);
}




    public function profile(Request $request)
{
    $user = $request->user();

    return response()->json([
        'status' => true,
        'message' => 'Profile retrieved successfully.',
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'organization_email' => $user->organization_email,
            'organization_name' => $user->organization_name,
            'account_type' => $user->account_type,
            'address' => $user->address,
            'email_verified_at' => $user->email_verified_at,
        ]
    ]);
}

    public function logout(Request $request)
{
    $user = $request->user();

    if ($user && $user->currentAccessToken()) {
        $user->currentAccessToken()->delete();

        return response()->json([
            'status' => true,
            'message' => 'User logged out successfully.'
        ]);
    }

    return response()->json([
        'status' => false,
        'message' => 'Unauthenticated.'
    ], 401);
}



    public function addElection(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'required',
            'end_time' => 'required',
            'timezone' => 'required|string',
            'description' => 'nullable|string',
        ]);

        $election = Election::create([
            'organization_id' => $user->id,
            'title' => $request->title,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'timezone' => $request->timezone,
        ]);

        return response()->json([
            'message' => 'Election created successfully.',
            'election' => $election,
        ], 201);
    }



    public function getElection(Request $request, $id = null)
    {
        $user = $request->user();

        if ($id) {
            $election = Election::where('id', $id)
                ->where('organization_id', $user->id)
                ->first();

            if (!$election) {
                return response()->json(['message' => 'Election not found.'], 404);
            }

            // Access the computed attribute to include it in the response
            $election->total_eligible_voters;

            return response()->json([
                'message' => 'Election retrieved successfully.',
                'election' => $election,
            ]);
        }

        $elections = Election::where('organization_id', $user->id)->get();

        $elections->each(function ($election) {
            $election->total_eligible_voters;
        });

        return response()->json([
            'message' => 'Elections retrieved successfully.',
            'elections' => $elections,
        ]);
    }



public function updateElectionDetails(Request $request, $id)
{
    $user = $request->user();

    // Get only fields explicitly present in request and not empty
    $updatableFields = ['title', 'start_date', 'end_date', 'description'];
    $requestData = collect($request->only($updatableFields))
        ->filter(function ($value) {
            return !is_null($value) && $value !== '';
        })
        ->toArray();

    // Validate only the cleaned data
    $validator = Validator::make($requestData, [
        'title' => 'sometimes|string|max:255',
        'start_date' => 'sometimes|date',
        'end_date' => 'sometimes|date',
        'description' => 'sometimes|string|nullable',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors(),
        ], 422);
    }

    // Find election owned by user
    $election = Election::where('id', $id)
        ->where('organization_id', $user->id)
        ->first();

    if (!$election) {
        return response()->json([
            'success' => false,
            'message' => 'Election not found or unauthorized.',
        ], 404);
    }

    // Update only fields that are both present and non-empty
    foreach ($requestData as $key => $value) {
        $election->$key = $value;
    }

    $election->save();

    return response()->json([
        'success' => true,
        'message' => 'Election updated successfully.',
        'data' => $election,
    ]);
}






    public function deleteElection(Request $request, $id)
    {
        $user = $request->user();

        $election = Election::where('id', $id)
            ->where('organization_id', $user->id)
            ->first();

        if (!$election) {
            return response()->json(['message' => 'Election not found or unauthorized.'], 404);
        }

        $election->delete();

        return response()->json([
            'message' => 'Election deleted successfully.'
        ]);
    }





    public function dashboard(Request $request)
    {
        $user = $request->user();

        $elections = Election::where('organization_id', $user->id)->get();
        $totalElections = $elections->count();
        $active = $elections->where('status', 'active')->count();

        return response()->json([
            'message' => 'Dashboard data loaded.',
            'summary' => [
                'total_elections' => $totalElections,
                'active_elections' => $active,
            ],
            'elections' => $elections,
        ]);
    }



    public function addCandidate(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'election_id' => 'required|exists:elections,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image_path' => 'nullable|string', // or file upload if you handle files
            
        ]);

        // Verify the election belongs to the logged-in user
        $election = Election::where('id', $request->election_id)
                            ->where('organization_id', $user->id)
                            ->first();

        if (!$election) {
            return response()->json(['message' => 'Election not found or unauthorized'], 404);
        }

        $candidate = Candidate::create([
            'election_id' => $request->election_id,
            'name' => $request->name,
            'description' => $request->description,
            'image_path' => $request->image_path,
            
        ]);

        return response()->json([
            'message' => 'Candidate added successfully.',
            'election_name' => $election->title,
            'candidate' => $candidate,
        ], 201);
    }



    public function getCandidates(Request $request, $election_id = null)
{
    $user = $request->user();

    if ($election_id) {
        // Get candidates for a specific election
        $election = Election::where('id', $election_id)
                            ->where('organization_id', $user->id)
                            ->first();

        if (!$election) {
            return response()->json([
                'message' => 'Election not found or unauthorized.'
            ], 404);
        }

        $candidates = $election->candidates()->get();

        return response()->json([
            'message' => 'Candidates retrieved successfully.',
            'election' => [
                'election id' => $election->id,
                'election title' => $election->title,
                'candidates' => $candidates,
            ],
        ], 200);
    } else {
        // Get all elections with their candidates
        $elections = Election::with('candidates')
            ->where('organization_id', $user->id)
            ->get();

        $result = $elections->map(function ($election) {
            return [
                'election id' => $election->id,
                'election title' => $election->title,
                'candidates' => $election->candidates,
            ];
        });

        return response()->json([
            'message' => 'Candidates grouped by election retrieved successfully.',
            'elections' => $result,
        ], 200);
    }
}


    public function addVoter(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|unique:voters,email',
            'election_id' => 'required|exists:elections,id',
            // voter_id and voter_key will be generated
        ]);

        $voter = Voter::create([
            'voter_id'       => \Illuminate\Support\Str::uuid(),
            'organization_id'=> $user->id,
            'election_id'    => $validated['election_id'],
            'name'           => $validated['name'],
            'email'          => $validated['email'],
            'voter_key'      => \Illuminate\Support\Str::random(32),
            'used'           => false,
        ]);

        return response()->json([
            'message' => 'Voter added successfully.',
            'voter' => $voter,
        ], 201);
    }




    public function importVoters(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'election_id' => 'required|exists:elections,id',
            'voters' => 'required|array',
            'voters.*.name' => 'required|string|max:255',
            'voters.*.email' => 'required|email|distinct|unique:voters,email',
        ]);

        $insertData = [];

        foreach ($validated['voters'] as $voter) {
            $insertData[] = [
                'voter_id' => 'VOTER-' . strtoupper(Str::random(8)),
                'organization_id' => $user->id,
                'election_id' => $validated['election_id'],
                'name' => $voter['name'],
                'email' => $voter['email'],
                'voter_key' => Str::random(16),
                'used' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Voter::insert($insertData);

        return response()->json([
            'message' => 'Voters imported successfully.',
            'count' => count($insertData),
        ]);
    }



    public function getVotersByElection(Request $request, $electionId)
    {
        $user = $request->user();

        // Check election belongs to this organization (optional but recommended)
        $election = Election::where('id', $electionId)
                            ->where('organization_id', $user->id)
                            ->first();

        if (!$election) {
            return response()->json(['message' => 'Election not found or access denied.'], 404);
        }

        // Get voters for this election
        $voters = Voter::where('election_id', $electionId)
                    ->where('organization_id', $user->id)
                    ->get();

        return response()->json([
            'election_name' => $election->title,
            'voters' => $voters,
        ], 200);
    }



    public function editVoter(Request $request, $id)
{
    $user = $request->user();

    // Get only the updatable fields that are present and not empty
    $updatableFields = ['name', 'email', 'election_id'];
    $requestData = collect($request->only($updatableFields))
        ->filter(fn($value) => $value !== null && $value !== '')
        ->toArray();

    // Validate only fields provided
    $validator = Validator::make($requestData, [
        'name'        => 'sometimes|string|max:255',
        'email'       => 'sometimes|email|unique:voters,email,' . $id,
        'election_id' => 'sometimes|exists:elections,id',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors(),
        ], 422);
    }

    // Find voter with org ownership check
    $voter = Voter::where('id', $id)
        ->where('organization_id', $user->id)
        ->first();

    if (!$voter) {
        return response()->json([
            'success' => false,
            'message' => 'Voter not found.',
        ], 404);
    }

    // Update only fields provided
    foreach ($requestData as $key => $value) {
        $voter->$key = $value;
    }

    $voter->save();

    return response()->json([
        'success' => true,
        'message' => 'Voter updated successfully.',
        'voter'   => $voter,
    ]);
}



    public function deleteVoter(Request $request, $id)
    {
        $user = $request->user();

        $voter = Voter::where('id', $id)
                    ->where('organization_id', $user->id)
                    ->first();

        if (!$voter) {
            return response()->json(['message' => 'Voter not found.'], 404);
        }

        $voter->delete();

        return response()->json(['message' => 'Voter deleted successfully.'], 200);
    }



    public function getVoteCountsPerElection(Request $request)
{
    $user = $request->user();

    // Join votes and elections, filter by organization, group and count
    $results = DB::table('votes')
        ->join('elections', 'votes.election_id', '=', 'elections.id')
        ->select(
            'votes.election_id',
            'elections.title',
            DB::raw('COUNT(*) as total_votes')
        )
        ->where('elections.organization_id', $user->id)
        ->groupBy('votes.election_id', 'elections.title')
        ->get();

    if ($results->isEmpty()) {
        return response()->json(['message' => 'No votes found for your elections.'], 404);
    }

    return response()->json([
        'message' => 'Vote counts per election',
        'data' => $results
    ], 200);
}


    public function getElectionResults(Request $request, $electionId)
    {
        $user = $request->user();

        // Verify the election belongs to the user's organization
        $election = Election::where('id', $electionId)
                            ->where('organization_id', $user->id)
                            ->first();

        if (!$election) {
            return response()->json(['message' => 'Election not found.'], 404);
        }

        // Get candidates with vote counts (assuming relationship set up)
        $candidates = Candidate::where('election_id', $electionId)
                        ->withCount('votes') // make sure you have a votes() relationship
                        ->get()
                        ->map(function ($candidate) {
                            return [
                                'name' => $candidate->name,
                                'vote_count' => $candidate->votes_count,
                            ];
                        });

        return response()->json([
            'election_title' => $election->title,
            'total_candidates' => $candidates->count(),
            'results' => $candidates,
        ], 200);
    }


    public function editEmailText(Request $request, $id)
{
    $user = $request->user();

    // Fields related to email text you want to allow updating
    $updatableFields = [
        'invite_from_name',
        'invite_subject',
        'invite_body',
        'reminder_from_name',
        'reminder_subject',
        'reminder_body',
    ];

    // Filter only present & non-empty fields
    $requestData = collect($request->only($updatableFields))
        ->filter(fn($value) => $value !== null && $value !== '')
        ->toArray();

    // Validation rules (all optional strings, invite_body and reminder_body can be nullable)
    $validator = Validator::make($requestData, [
        'invite_from_name'   => 'sometimes|string|max:255',
        'invite_subject'     => 'sometimes|string|max:255',
        'invite_body'        => 'sometimes|nullable|string',
        'reminder_from_name' => 'sometimes|string|max:255',
        'reminder_subject'   => 'sometimes|string|max:255',
        'reminder_body'      => 'sometimes|nullable|string',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors(),
        ], 422);
    }

    // Find election owned by user
    $election = Election::where('id', $id)
        ->where('organization_id', $user->id)
        ->first();

    if (!$election) {
        return response()->json([
            'success' => false,
            'message' => 'Election not found or unauthorized.',
        ], 404);
    }

    // Update only fields provided
    foreach ($requestData as $key => $value) {
        $election->$key = $value;
    }

    $election->save();

    return response()->json([
        'success' => true,
        'message' => 'Email text updated successfully.',
        'data' => $election->only($updatableFields),
    ]);
}


    
    public function launchElection(Request $request, $id)
    {
        $user = $request->user();

        $election = Election::where('id', $id)
            ->where('organization_id', $user->id)
            ->firstOrFail();

        $election->status = 'active';
        $election->save();

        $voters = Voter::where('election_id', $id)->get();

        foreach ($voters as $voter) {
            $votingLink = url("/voting?election_id={$election->id}&voter_key={$voter->voter_key}");
            \Mail::raw(
                "Dear {$voter->name},\n\nYou are invited to vote in the election: {$election->title}.\n\nPlease use the following link to vote:\n{$votingLink}\n\nThank you!",
                function ($message) use ($voter, $election) {
                    $message->to($voter->email)
                        ->subject('Your Voting Link for ' . $election->title);
                }
            );
        }

        return response()->json([
            'message' => 'Election launched and emails sent to all voters!'
        ]);
    }
    

public function sendReminder(Request $request, $id)
{
    $user = $request->user();

    $election = Election::where('id', $id)
        ->where('organization_id', $user->id)
        ->firstOrFail();

    $voters = Voter::where('election_id', $id)
        ->where('used', false)
        ->get();

    foreach ($voters as $voter) {
        $votingLink = url("/voting?election_id={$election->id}&voter_key={$voter->voter_key}");

        // Use subject from election or default
        $subject = $election->reminder_subject ?: "Reminder: Please vote in {$election->title}";

        // Compose a dynamic email body — inject voter's name and voting link even if reminder_body exists
        $bodyTemplate = $election->reminder_body;
        if (empty(trim($bodyTemplate))) {
            // Fallback if no template is set
            $body = "Dear {$voter->name},\n\nThis is a reminder to vote in the election: {$election->title}.\n\nUse the link below to vote:\n{$votingLink}\n\nThank you!";
        } else {
            // You can support placeholders if you want, e.g. replace {name} and {link}
            $body = str_replace(
                ['{name}', '{link}', '{title}'],
                [$voter->name, $votingLink, $election->title],
                $bodyTemplate
            );
        }

        \Mail::raw($body, function ($message) use ($voter, $subject) {
            $message->to($voter->email)
                    ->subject($subject);
        });
    }

    return response()->json([
        'message' => 'Reminder emails sent to voters who have not yet voted.'
    ]);
}





}

