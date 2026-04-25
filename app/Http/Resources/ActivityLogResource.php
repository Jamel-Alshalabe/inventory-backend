<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ActivityLog */
class ActivityLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'adminId' => $this->admin_id,
            'logName' => $this->log_name,
            'description' => $this->description,
            'subjectType' => $this->subject_type,
            'subjectId' => $this->subject_id,
            'causerType' => $this->causer_type,
            'causerId' => $this->causer_id,
            'causerUsername' => $this->causer?->username ?? 'system',
            'properties' => $this->properties ? json_decode($this->properties, true) : null,
            'batchUuid' => $this->batch_uuid,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
