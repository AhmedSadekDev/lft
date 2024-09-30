@if(isset($compnyEmployees))

@foreach ($compnyEmployees as $employee)
    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
@endforeach

@endif
