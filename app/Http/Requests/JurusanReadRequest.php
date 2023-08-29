<?php

namespace App\Http\Requests;


class JurusanReadRequest extends CoreRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'id_jurusan' => 'nullable',
            'start' => 'nullable|integer',
            'length' => 'nullable|integer',
            'search' => 'nullable',
            'order' => 'nullable',
        ];
    }
}
