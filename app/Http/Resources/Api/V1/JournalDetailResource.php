<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

/** @mixin \App\Models\Journal */
class JournalDetailResource extends JournalResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);

        $data['description'] = $this->description ? strip_tags((string) $this->description) : null;
        $data['publisher'] = $this->publisher;
        $data['language'] = $this->language;
        $data['default_license'] = $this->default_license;
        $data['review_type'] = $this->review_type;
        $data['urls']['archive'] = route('journals.archive', $this->resource);
        $data['urls']['about'] = route('journals.about', $this->resource);
        $data['current_issue'] = $this->when(
            $this->relationLoaded('currentIssue') && $this->currentIssue,
            fn () => [
                'id' => $this->currentIssue->id,
                'label' => $this->currentIssue->label(),
                'urls' => [
                    'web' => route('journals.issues.show', [$this->resource, $this->currentIssue]),
                ],
            ]
        );

        return $data;
    }
}
