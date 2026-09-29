<?php

namespace App\Http\Requests;

use App\Reports\OvertimeReportFilters;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class OvertimeReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewReports') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->mergeIfMissing(OvertimeReportFilters::defaults());
    }

    public function rules(): array
    {
        return OvertimeReportFilters::rules();
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty() || $this->input('period') !== 'week') {
                return;
            }
            foreach (OvertimeReportFilters::validator($this->all())->errors()->get('week') as $message) {
                $validator->errors()->add('week', $message);
            }
        }];
    }
}
