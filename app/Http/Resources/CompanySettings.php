<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanySettings extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'timezone' => $this['timezone'] ?? null,
            'language' => $this['language'] ?? null,
            'work_time' => $this['work_time'] ?? 0,
            'color' => $this['color'] ?? [],
            'internal_priorities' => $this['internal_priorities'] ?? [],
            'heartbeat_period' => config('app.user_activity.online_status_time'),
            'auto_thinning' => (bool)($this['auto_thinning'] ?? false),
            'default_priority_id' => (int)($this['default_priority_id'] ?? 2),
            'google_client_id' => $this['google_client_id'] ?? null,
            'google_project_id' => $this['google_project_id'] ?? null,
            'google_client_secret' => $this['google_client_secret'] ?? null,
        ];
    }
}
