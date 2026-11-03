<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

final class PreferenceRequest extends ApiRequest
{
    public function rules(): array
    {
        $key = $this->route('key');
        $rules = ['expectedVersion' => ['required', 'integer', 'min:0'], 'value' => ['required']];
        if ($key === 'theme') {
            $rules['value'] = ['required', Rule::in(['light', 'dark'])];
        } elseif ($key === 'sidebar-collapsed') {
            $rules['value'] = ['required', 'boolean'];
        } elseif ($key === 'sidebar-hidden') {
            $rules['value'] = ['present', 'array', 'max:1000'];
            $rules['value.*'] = ['string', 'max:80'];
        } else {
            $rules['value'] = ['required', 'array:color,image'];
            $rules['value.color'] = ['required', 'regex:/^#[0-9a-fA-F]{6}$/'];
            $rules['value.image'] = ['present', 'nullable', 'string', 'max:2800000', function ($attribute, $value, $fail) {
                if ($value === '') {
                    return;
                }if (! preg_match('/^data:image\/(jpeg|png|webp);base64,([A-Za-z0-9+\/=]+)$/D', $value, $matches)) {
                    $fail('Invalid banner image.');

                    return;
                }$bytes = base64_decode($matches[2], true);
                $info = $bytes === false ? false : @getimagesizefromstring($bytes);
                if ($bytes === false || strlen($bytes) > 2097152 || ! $info || ! in_array($info['mime'], ['image/jpeg', 'image/png', 'image/webp'], true)) {
                    $fail('Invalid banner image or image exceeds 2 MiB.');
                }
            }];
        }

        return $rules;
    }
}
