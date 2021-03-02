<?php


namespace App\Http\Requests\Google\Sheets;

use App\Http\Requests\FormRequest;

class ExportReportEndRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'state' => 'required|string',
        ];
    }

    public function getDecodedStateAsArray(): array
    {
        return json_decode(
            $this->getDecodedStateAsJson(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }

    public function getDecodedStateAsJson(): string
    {
        return base64_decode($this->query->get('state'));
    }
}
