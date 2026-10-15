@include('auth.passwords.set', [
    'action' => route('agents.update_password'),
    'audience' => 'لحساب المندوب',
])
