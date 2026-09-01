<?php

namespace App\Http\Requests\Api\Agent\Concerns;

trait MapsAgentExpenseNotes
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('notes')) {
            $this->merge(['notes' => trim((string) $this->input('notes'))]);

            return;
        }

        foreach (['text', 'note', 'receipt_number', 'receipt_no', 'receiptNumber'] as $field) {
            if ($this->filled($field)) {
                $this->merge(['notes' => trim((string) $this->input($field))]);
                break;
            }
        }
    }
}
