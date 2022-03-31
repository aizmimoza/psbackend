<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSurveyRequest extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() {
        $survey = $this->route('survey');
        if( $this->user()->id !== $survey->user_id ) {
            return false;
        };
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        return [
            'user_id' => 'exists:users,id',
            'title' => 'required|string|max:1000',
            'status' => 'required|boolean',
            'image' => 'nullable|string',
            'description' => 'nullable|string',
            'questions' => 'array',
            'expire_date' => 'nullable|date|after:tomorrow',
        ];
    }
}
