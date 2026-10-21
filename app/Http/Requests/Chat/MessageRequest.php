<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates one outgoing message and its images.
 *
 * Attachments are checked on both the MIME signature and the extension,
 * like every other upload here: a renamed file is supplied by the
 * client and neither claim is evidence on its own.
 */
class MessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Membership is decided by the controller against the thread;
        // this request only knows the shape.
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $files = config('verum_chat.attachments');

        return [
            'body' => [
                'required_without:attachments',
                'nullable',
                'string',
                'max:'.config('verum_chat.limits.body_max'),
            ],
            'attachments' => ['nullable', 'array', 'max:'.$files['max_per_message']],
            'attachments.*' => [
                'file',
                'image',
                "max:{$files['max_kb']}",
                'mimetypes:'.implode(',', $files['mimes']),
                'mimes:'.implode(',', $files['extensions']),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        $files = config('verum_chat.attachments');

        return [
            'body.required_without' => __('Write something, or attach a photo.'),
            'attachments.max' => __('You can attach up to :count images.', [
                'count' => $files['max_per_message'],
            ]),
            'attachments.*.max' => __('Images must be under :mb MB.', [
                'mb' => round($files['max_kb'] / 1024),
            ]),
            'attachments.*.image' => __('Only images can be attached.'),
            'attachments.*.mimetypes' => __('Only :formats images are accepted.', [
                'formats' => mb_strtoupper(implode(', ', $files['extensions'])),
            ]),
        ];
    }
}
