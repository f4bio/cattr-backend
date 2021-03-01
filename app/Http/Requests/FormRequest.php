<?php

namespace App\Http\Requests;

use App\Exceptions\Entities\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest as BaseFormRequest;
use Illuminate\Support\Facades\Auth;

abstract class FormRequest extends BaseFormRequest
{
    private $userId;

    public function __construct(
        array $query = [],
        array $request = [],
        array $attributes = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        $content = null
    ) {
        parent::__construct($query, $request, $attributes, $cookies, $files, $server, $content);
        $this->userId = Auth::id();
    }

    /**
     * Handle a failed validation attempt.
     *
     * @throws AuthorizationException
     */
    protected function failedAuthorization()
    {
        throw new AuthorizationException(AuthorizationException::ERROR_TYPE_FORBIDDEN);
    }

    /**
     * @return int
     * @throws AuthorizationException
     */
    public function getAuthUserId(): int
    {
        if (is_int($this->userId)) {
            return $this->userId;
        }


        $this->failedAuthorization();
    }
}
