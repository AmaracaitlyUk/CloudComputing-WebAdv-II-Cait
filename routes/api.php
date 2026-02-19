<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiFrontController;
use App\Http\Controllers\ApiBackController;


// User Routes---------------------------------------------------------------------------
// http://127.0.0.1:8092/api/user/....
Route::prefix('user')->group(function(){

    // http://127.0.0.1:8092/api/user/register
    Route::post('register', [ApiFrontController::class, 'register']);
    // http://127.0.0.1:8092/api/user/login
    Route::post('login', [ApiFrontController::class, 'login']);


    Route::middleware('auth:sanctum')->group(function () {
        // http://127.0.0.1:8092/api/user/profile
        Route::get('profile', [ApiFrontController::class, 'profile']);
        // http://127.0.0.1:8092/api/user/logout
        Route::post('logout', [ApiFrontController::class, 'logout']);


        // http://127.0.0.1:8092/api/user/add_election
        Route::post('/add_election', [ApiFrontController::class, 'addElection']);
        // http://127.0.0.1:8092/api/user/elections
        Route::get('/elections', [ApiFrontController::class, 'getElection']);
        // http://127.0.0.1:8092/api/user/election/{id}
        Route::get('/election/{id}', [ApiFrontController::class, 'getElection']);
        // http://127.0.0.1:8092/api/user/edit_election/{id}
        Route::post('/edit_election/{id}', [ApiFrontController::class, 'updateElectionDetails']);
        // http://127.0.0.1:8092/api/user/election/{id}
        Route::delete('/election/{id}', [ApiFrontController::class, 'deleteElection']);


        // http://127.0.0.1:8092/api/user/dashboard
        Route::get('/dashboard', [ApiFrontController::class, 'dashboard']);


        // http://127.0.0.1:8092/api/user/add_candidate
        Route::post('/add_candidate', [ApiFrontController::class, 'addCandidate']);
        // http://127.0.0.1:8092/api/user/candidates
        Route::get('/candidates', [ApiFrontController::class, 'getCandidates']);
        // http://127.0.0.1:8092/api/user/elections/{id}/candidates
        Route::get('/elections/{id}/candidates', [ApiFrontController::class, 'getCandidates']);
        

        // http://127.0.0.1:8092/api/user/add_voter
         Route::post('/add_voter', [ApiFrontController::class, 'addVoter']);
         // http://127.0.0.1:8092/api/user/import_voter
         Route::post('/import_voter', [ApiFrontController::class, 'importVoters']);
         // http://127.0.0.1:8092/api/user/elections/{electionId}/voters
         Route::get('/elections/{electionId}/voters', [ApiFrontController::class, 'getVotersByElection']);
         // http://127.0.0.1:8092/api/user/voter/{id}
         Route::post('/voter/{id}', [ApiFrontController::class, 'editVoter']);
         // http://127.0.0.1:8092/api/user/voter/{id}
         Route::delete('/voter/{id}', [ApiFrontController::class, 'deleteVoter']);


         // http://127.0.0.1:8092/api/user/votes/by-election
         Route::get('/votes/by-election', [ApiFrontController::class, 'getVoteCountsPerElection']);

         // http://127.0.0.1:8092/api/user//elections/{id}/edit-email-text
         Route::post('/elections/{id}/edit-email-text', [ApiFrontController::class, 'editEmailText']);

         // http://127.0.0.1:8092/api/user/election/{id}/launch
         Route::post('/election/{id}/launch', [ApiFrontController::class, 'launchElection']);

         // http://127.0.0.1:8092/api/user/elections/{id}/results
         Route::get('/elections/{id}/results', [ApiFrontController::class, 'getElectionResults']);

          // http://127.0.0.1:8092/api/user/elections/{id}/send-reminder
        Route::post('/elections/{id}/send-reminder', [ApiFrontController::class, 'sendReminder']);


        
    });

});



// Admin Routes---------------------------------------------------------------------------
// http://127.0.0.1:8092/api/admin/....
Route::prefix('admin')->group(function(){

    // // http://127.0.0.1:8092/api/admin/register
    // Route::post('register', [ApiBackController::class, 'register']);

      
    // http://127.0.0.1:8092/api/admin/admin_login
    Route::post('admin_login', [ApiBackController::class, 'login']);


    Route::middleware('auth:admin_api')->group(function(){

        // http://127.0.0.1:8092/api/admin/logout
        Route::post('logout', [ApiBackController::class, 'logout']);

        // http://127.0.0.1:8092/api/admin/dashboard
        Route::get('dashboard', [ApiBackController::class, 'dashboard']);


        // http://127.0.0.1:8092/api/admin/{id}/profile
        Route::get('{id}/profile', [ApiBackController::class, 'profile']);

         // http://127.0.0.1:8092/api/admin/{election}/results
        Route::get('{election}/results', [ApiBackController::class, 'results']);

        // http://127.0.0.1:8092/api/admin/timeline
        Route::get('timeline', [ApiBackController::class, 'timeline']);

        

    });
});


