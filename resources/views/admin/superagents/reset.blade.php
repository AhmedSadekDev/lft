@include('auth.passwords.set', [
    'action' => route('superagents.update_password'),
    'audience' => 'لحساب المندوب المسؤول',
])
