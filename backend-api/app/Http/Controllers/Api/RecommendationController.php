<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProcurementRecommendation;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    public function show(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|integer',
            'no' => 'required|string|max:20',
        ]);

        $record = ProcurementRecommendation::where('master_project_id', $data['id'])
            ->where('procurement_no', $data['no'])
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => $record,
        ]);
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|integer',
            'no' => 'required|string|max:20',
            'summary' => 'nullable|string|max:4000',
            'recommendations' => 'required|array|max:20',
            'recommendations.*.category' => 'required|string|max:60',
            'recommendations.*.tone' => 'required|in:error,primary,slate',
            'recommendations.*.metricLabel' => 'nullable|string|max:80',
            'recommendations.*.metricValue' => 'nullable|string|max:80',
            'recommendations.*.title' => 'required|string|max:200',
            'recommendations.*.body' => 'required|string|max:2000',
            'recommendations.*.source' => 'required|in:ai,manual',
            'recommendations.*.edited' => 'nullable|boolean',
        ]);

        $recommendations = collect($data['recommendations'])->map(function ($rec) {
            return [
                'category' => $rec['category'],
                'tone' => $rec['tone'],
                'metricLabel' => $rec['metricLabel'] ?? null,
                'metricValue' => $rec['metricValue'] ?? null,
                'title' => $rec['title'],
                'body' => $rec['body'],
                'source' => $rec['source'],
                'edited' => (bool) ($rec['edited'] ?? false),
            ];
        })->values()->all();

        $hasManual = collect($recommendations)->contains('source', 'manual');
        $hasAi = collect($recommendations)->contains('source', 'ai');
        $source = ($hasManual && $hasAi) ? 'mixed' : ($hasManual ? 'manual' : 'ai');

        $record = ProcurementRecommendation::updateOrCreate(
            [
                'master_project_id' => $data['id'],
                'procurement_no' => $data['no'],
            ],
            [
                'summary' => $data['summary'] ?? null,
                'recommendations' => $recommendations,
                'source' => $source,
            ]
        );

        return response()->json([
            'status' => 'success',
            'data' => $record,
        ]);
    }

    public function destroy(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|integer',
            'no' => 'required|string|max:20',
        ]);

        ProcurementRecommendation::where('master_project_id', $data['id'])
            ->where('procurement_no', $data['no'])
            ->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Recommendation dihapus',
        ]);
    }
}
