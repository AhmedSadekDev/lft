@include('auth.passwords.set', [
    'action' => route('companies.update_password'),
    'audience' => 'لحساب الشركة',
])
