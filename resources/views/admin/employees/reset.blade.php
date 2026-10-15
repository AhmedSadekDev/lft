@include('auth.passwords.set', [
    'action' => route('employees.update_password'),
    'audience' => 'لحساب الموظف',
])
