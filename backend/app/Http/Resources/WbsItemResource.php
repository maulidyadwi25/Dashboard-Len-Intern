<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WbsItemResource extends JsonResource
{
    /**
     * Transform the WBS item into an array.
     *
     * Weekly values are intentionally excluded. Use
     * GET /projects/{id}/wbs/weekly for the Gantt grid payload.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'wbs_no' => $this->wbs_no,
            'parent_wbs_no' => $this->parent_wbs_no,
            'level' => (int) $this->level,
            'milestone' => $this->milestone,
            'activity_name' => $this->activity_name,
            'pic' => $this->pic,
            'divisi' => $this->divisi,
            'nilai_kegiatan' => (float) $this->nilai_kegiatan,
            'bobot_plan' => (float) $this->bobot_plan,
            'bobot_real' => (float) $this->bobot_real,
            'latest_progress_percent' => $this->latest_progress_percent === null ? null : (float) $this->latest_progress_percent,
            'progress_sum' => $this->progress_sum === null ? null : (float) $this->progress_sum,
            'actual_start_week' => $this->actual_start_week === null ? null : (int) $this->actual_start_week,
            'actual_finish_week' => $this->actual_finish_week === null ? null : (int) $this->actual_finish_week,
            'actual_source' => $this->actual_source,
            'plan_start_period' => $this->plan_start_period,
            'plan_end_period' => $this->plan_end_period,
            'actual_start_period' => $this->actual_start_period,
            'actual_end_period' => $this->actual_end_period,
            'durasi' => $this->durasi,
            'status' => $this->status ?? null,
            'order_index' => (int) $this->order_index,
        ];
    }
}
