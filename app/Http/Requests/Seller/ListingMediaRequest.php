<?php

namespace App\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates one uploaded file.
 *
 * Both MIME type and extension are checked: a renamed .exe can claim an
 * image extension, and a browser-reported MIME alone is not evidence.
 */
class ListingMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('seller')?->isApproved() ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $kind = $this->kind();
        $config = config("verum_media.{$kind}");

        return [
            'kind' => ['required', Rule::in(['images', 'videos'])],
            'file' => [
                'required',
                'file',
                "max:{$config['max_kb']}",
                // `mimetypes` reads the file's own signature, not the name.
                'mimetypes:'.implode(',', $config['mimes']),
                'mimes:'.implode(',', $config['extensions']),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        $kind = $this->kind();
        $config = config("verum_media.{$kind}");
        $mb = round($config['max_kb'] / 1024);

        return [
            'file.max' => __('That file is too large. The limit is :mb MB.', ['mb' => $mb]),
            'file.mimetypes' => __('Only :formats files are accepted.', [
                'formats' => mb_strtoupper(implode(', ', $config['extensions'])),
            ]),
            'file.mimes' => __('Only :formats files are accepted.', [
                'formats' => mb_strtoupper(implode(', ', $config['extensions'])),
            ]),
        ];
    }

    public function kind(): string
    {
        return $this->input('kind') === 'videos' ? 'videos' : 'images';
    }
}
