<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use App\Models\Album;
use Illuminate\Support\Facades\Log;

class AnalyticsController extends Controller
{
    public function index()
    {
        try {
            $totalPhotos = \App\Models\Photo::count();
            $totalViews  = \App\Models\Photo::sum('views_count') ?? 0;
            $topPhotos   = \App\Models\Photo::with('album')->orderByDesc('views_count')->take(5)->get();
            $totalAlbums = \App\Models\Album::count();

            return response()->json([
                'total_photos' => $totalPhotos,
                'total_views'  => (int)$totalViews,
                'top_photos'   => $topPhotos,
                'total_albums' => $totalAlbums,
            ]);
        } catch (\Exception $e) {
            \Log::error('Analytics Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Analytics failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
