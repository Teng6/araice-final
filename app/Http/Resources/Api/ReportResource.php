<?php

namespace App\Http\Resources\Api;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Report */
class ReportResource extends JsonResource
{
    private bool $includeSummaryData = false;

    public function withSummaryData(): self
    {
        $this->includeSummaryData = true;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'generated_by' => $this->generatedBy === null ? null : [
                'id' => $this->generatedBy->id,
                'name' => $this->generatedBy->name,
            ],
            'municipality' => $this->municipality->value,
            'range_start' => $this->range_start->toDateString(),
            'range_end' => $this->range_end->toDateString(),
            'created_at' => $this->created_at,
            ...($this->includeSummaryData ? [
                'summary_data' => $this->summary_data,
            ] : []),
        ];
    }
}
