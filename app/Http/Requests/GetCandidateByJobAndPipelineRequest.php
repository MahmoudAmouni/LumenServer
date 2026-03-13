<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetCandidateByJobAndPipelineRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(){
        return [
            'pipeline_stage_id' => 'nullable|integer',
            'stage_name' => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1'
        ];
    }

    protected function prepareForValidation(){
        $this->merge([
            'per_page' => $this->per_page ?? null,
            'page' => $this->page ?? 1
        ]);
    }

    public function withValidator($validator){

        $validator->after(function ($validator){
            if (!$this->pipeline_stage_id && !$this->stage_name) {
                $validator->errors()->add(
                    'pipeline_stage_id',
                    'Either pipeline_stage_id or stage_name must be provided.'
                );
            }
        });
    }
}
