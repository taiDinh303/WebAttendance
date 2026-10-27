<?php

namespace App\Http\Resources;

use App\Models\AttendanceWindow;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AttendanceWindow */
final class AttendanceWindowResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'sessionId' => $this->session_id, 'opensAt' => $this->opens_at->toISOString(),
            'lateAfter' => $this->late_after?->toISOString(), 'closesAt' => $this->closes_at->toISOString(),
            'state' => $this->state === 'open' && now()->gte($this->closes_at) ? 'expired' : $this->state,
            'version' => $this->version, 'serverTime' => now()->toISOString()];
    }
}
