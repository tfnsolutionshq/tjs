<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

/** @mixin \App\Models\Submission */
class JournalManageSubmissionResource extends SubmissionResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);

        $data['author'] = $this->when($this->relationLoaded('author') && $this->author, fn () => [
            'id' => $this->author->id,
            'name' => $this->author->name,
            'email' => $this->author->email,
        ]);
        $data['reviewer'] = $this->when($this->relationLoaded('reviewer') && $this->reviewer, fn () => [
            'id' => $this->reviewer->id,
            'name' => $this->reviewer->name,
        ]);

        return $data;
    }
}
