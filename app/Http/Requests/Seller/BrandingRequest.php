<?php

namespace App\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates a seller's avatar or store banner.
 *
 * Same discipline as listing media: both the file's own signature and its
 * extension are checked, because a browser-reported MIME type and a file
 * name are both supplied by the client.
 */
class BrandingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('seller')?->isApproved() ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $config = config("verum_media.{$this->kind()}");

        return [
            'kind' => ['required', Rule::in(['avatar', 'banner'])],
            'file' => [
                'required',
                'image',
                "max:{$config['max_kb']}",
                'mimetypes:'.implode(',', $config['mimes']),
                'mimes:'.implode(',', $config['extensions']),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        $config = config("verum_media.{$this->kind()}");
        $formats = mb_strtoupper(implode(', ', $config['extensions']));

        return [
            'file.max' => __('That image is too large. The limit is :mb MB.', [
                'mb' => round($config['max_kb'] / 1024),
            ]),
            'file.image' => __('That file is not an image.'),
            'file.mimetypes' => __('Only :formats images are accepted.', ['formats' => $formats]),
            'file.mimes' => __('Only :formats images are accepted.', ['formats' => $formats]),
        ];
    }

    public function kind(): string
    {
        return $this->input('kind') === 'banner' ? 'banner' : 'avatar';
    }
}
