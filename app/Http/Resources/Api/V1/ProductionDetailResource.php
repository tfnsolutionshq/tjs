<?php

namespace App\Http\Resources\Api\V1;

use App\Support\ProductionChecklist;
use App\Support\SubmissionStatus;
use Illuminate\Http\Request;

/** @mixin \App\Models\Submission */
class ProductionDetailResource extends ProductionQueueResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);
        $checklist = ProductionChecklist::normalize($this->production_checklist);
        $currentFile = $this->relationLoaded('productionFiles')
            ? $this->currentProductionFile()
            : null;

        $data['abstract'] = $this->abstract ? strip_tags((string) $this->abstract) : null;
        $data['keywords'] = $this->keywords;
        $data['has_source_document'] = (bool) $this->document_path;
        $data['production_started_at'] = $this->production_started_at?->toIso8601String();
        $data['production_completed_at'] = $this->production_completed_at?->toIso8601String();
        $data['checklist'] = $checklist;
        $data['checklist_schema'] = [
            'keys' => ProductionChecklist::keys(),
            'labels' => ProductionChecklist::labels(),
        ];
        $data['current_production_file'] = $currentFile ? [
            'id' => $currentFile->id,
            'version' => $currentFile->version,
            'original_filename' => $currentFile->original_filename,
            'created_at' => $currentFile->created_at?->toIso8601String(),
            'uploader' => $currentFile->relationLoaded('uploader') && $currentFile->uploader ? [
                'id' => $currentFile->uploader->id,
                'name' => $currentFile->uploader->name,
            ] : null,
        ] : null;
        $data['can_start'] = in_array($this->status, [
            SubmissionStatus::READY_FOR_PRODUCTION,
            SubmissionStatus::IN_PRODUCTION,
        ], true);
        $data['can_upload'] = in_array($this->status, SubmissionStatus::productionQueueStatuses(), true);
        $data['can_complete'] = $this->status === SubmissionStatus::IN_PRODUCTION && $this->hasProductionDocument();
        $data['timeline'] = $this->when($this->relationLoaded('timelines'), fn () => $this->timelines->map(fn ($entry) => [
            'id' => $entry->id,
            'event' => $entry->event,
            'metadata' => $entry->metadata,
            'created_at' => $entry->created_at?->toIso8601String(),
            'user' => $entry->relationLoaded('user') && $entry->user ? [
                'id' => $entry->user->id,
                'name' => $entry->user->name,
            ] : null,
        ])->values());

        return $data;
    }
}
