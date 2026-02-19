<h2>Hello {{ $voter->name }},</h2>

<p>You are invited to vote in the election: <strong>{{ $election->title }}</strong></p>

<p>
Your Voter ID: <strong>{{ $voter->voter_id }}</strong><br>
Your Voting Key: <strong>{{ $voter->voter_key }}</strong>
</p>

<p>
Click the link below to vote:<br>
<a href="{{ url('/vote?key=' . $voter->voter_key . '&id=' . $voter->voter_id) }}">Vote Now</a>
</p>

<p>Thank you,<br>Online Voting System</p>