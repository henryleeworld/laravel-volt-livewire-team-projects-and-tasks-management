<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('task'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assigned_to_user_id' => ['nullable', 'exists:users,id'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpeg,png,gif,webp', 'max:5120'],
            'delete_images' => ['nullable', 'array'],
            'delete_images.*' => ['integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('Please provide a task name.'),
            'name.max' => __('The task name must not exceed 255 characters.'),
            'images.max' => __('You can upload a maximum of 10 images.'),
            'images.*.image' => __('Each file must be an image.'),
            'images.*.mimes' => __('Images must be jpeg, png, gif, or webp format.'),
            'images.*.max' => __('Each image must not exceed 5MB.'),
        ];
    }
}
