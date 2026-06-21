<div class="form-group">
    <label for="company_id">{{ __('main.company') }}</label>
    <select name="company_id" id="company_id" class="form-control selectpicker select-company">
        <option value="">{{ __('admin.select') }}</option>
        @foreach ($companies as $company)
            <option value="{{ $company->id }}" {{ old('company_id', $booking?->company_id ?? '') == $company->id ? 'selected' : '' }}>
                {{ $company->name }}
            </option>
        @endforeach
    </select>
</div>
