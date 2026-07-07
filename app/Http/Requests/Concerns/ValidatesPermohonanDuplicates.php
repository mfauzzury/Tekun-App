<?php

namespace App\Http\Requests\Concerns;

use App\Services\PermohonanDuplicateCheckService;
use Illuminate\Contracts\Validation\Validator;

trait ValidatesPermohonanDuplicates
{
    protected function validatePermohonanDuplicates(Validator $validator, ?int $excludePermohonanId = null): void
    {
        $details = $this->input('details');
        if (! is_array($details)) {
            return;
        }

        $service = app(PermohonanDuplicateCheckService::class);
        $errors = $service->findDuplicateFieldErrors($details, $excludePermohonanId);

        foreach ($errors as $field => $message) {
            $validator->errors()->add($field, $message);
        }
    }
}
