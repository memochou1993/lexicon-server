<?php

namespace App\Http\Requests\Client;

use App\Rules\Relations;
use Illuminate\Foundation\Http\FormRequest;

class KeyIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'relations' => [
                new Relations([
                    'values',
                    'values.languages',
                    'values.forms',
                ]),
            ],
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->prepareRelations();
    }

    /**
     * @return void
     */
    private function prepareRelations()
    {
        $relations = collect($this->relations)->explode(',')->merge([
            'values',
            'values.languages',
            'values.forms',
        ]);

        $this->merge([
            'relations' => $relations->toArray(),
        ]);
    }
}
