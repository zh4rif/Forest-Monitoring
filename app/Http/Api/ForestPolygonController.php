<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ForestPolygon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ForestPolygonController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ForestPolygon::query()
            ->active()
            ->with(['user:id,name', 'organization:id,name']);

        // Apply filters
        if ($request->has('type')) {
            $query->byType($request->type);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('bounds')) {
            $bounds = explode(',', $request->bounds);
            if (count($bounds) === 4) {
                $query->withinBounds($bounds[0], $bounds[1], $bounds[2], $bounds[3]);
            }
        }

        // Pagination
        $polygons = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'status' => 'success',
            'data' => $polygons->map(fn($polygon) => $polygon->toGeoJsonFeature()),
            'meta' => [
                'current_page' => $polygons->currentPage(),
                'last_page' => $polygons->lastPage(),
                'per_page' => $polygons->perPage(),
                'total' => $polygons->total()
            ]
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'geometry' => 'required',
            'type' => 'required|in:primary,secondary,plantation,degraded,regrowth,mangrove,peat_swamp,montane,unknown',
            'height_category' => 'nullable|in:tall,medium,short,low',
            'canopy_density' => 'nullable|in:dense,medium,sparse,very_sparse',
            'source' => 'required|in:manual,satellite,lidar,drone,field_survey,imported',
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'observation_date' => 'nullable|date',
            'confidence_level' => 'nullable|in:high,medium,low',
            'is_public' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $polygon = ForestPolygon::create([
                ...$request->validated(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Forest polygon created successfully',
                'data' => $polygon->toGeoJsonFeature()
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create polygon: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(string $uuid): JsonResponse
    {
        $polygon = ForestPolygon::where('uuid', $uuid)->active()->first();

        if (!$polygon) {
            return response()->json([
                'status' => 'error',
                'message' => 'Polygon not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $polygon->toGeoJsonFeature()
        ]);
    }

    public function update(Request $request, string $uuid): JsonResponse
    {
        $polygon = ForestPolygon::where('uuid', $uuid)->first();

        if (!$polygon) {
            return response()->json([
                'status' => 'error',
                'message' => 'Polygon not found'
            ], 404);
        }

        // Check authorization
        if ($polygon->user_id !== auth()->id() && !auth()->user()->can('update-any-polygon')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'sometimes|in:primary,secondary,plantation,degraded,regrowth,mangrove,peat_swamp,montane,unknown',
            'height_category' => 'nullable|in:tall,medium,short,low',
            'canopy_density' => 'nullable|in:dense,medium,sparse,very_sparse',
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'observation_date' => 'nullable|date',
            'confidence_level' => 'nullable|in:high,medium,low',
            'is_public' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $polygon->update($request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Polygon updated successfully',
                'data' => $polygon->fresh()->toGeoJsonFeature()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update polygon: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(string $uuid): JsonResponse
    {
        $polygon = ForestPolygon::where('uuid', $uuid)->first();

        if (!$polygon) {
            return response()->json([
                'status' => 'error',
                'message' => 'Polygon not found'
            ], 404);
        }

        // Check authorization
        if ($polygon->user_id !== auth()->id() && !auth()->user()->can('delete-any-polygon')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
            // Soft delete by setting is_active to false
            $polygon->update(['is_active' => false]);

            return response()->json([
                'status' => 'success',
                'message' => 'Polygon deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete polygon: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkImport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'geojson' => 'required|array',
            'geojson.type' => 'required|in:FeatureCollection',
            'geojson.features' => 'required|array|min:1|max:100', // Limit bulk imports
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $imported = [];
            $errors = [];

            foreach ($request->geojson['features'] as $index => $feature) {
                try {
                    $polygon = ForestPolygon::createFromGeoJson($feature, auth()->id());
                    $imported[] = $polygon->toGeoJsonFeature();
                } catch (\Exception $e) {
                    $errors[] = [
                        'index' => $index,
                        'message' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Bulk import completed',
                'data' => [
                    'imported' => $imported,
                    'imported_count' => count($imported),
                    'error_count' => count($errors),
                    'errors' => $errors
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Bulk import failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function export(Request $request): JsonResponse
    {
        $query = ForestPolygon::query()->active();

        // Apply filters
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('type')) {
            $query->byType($request->type);
        }

        if ($request->has('bounds')) {
            $bounds = explode(',', $request->bounds);
            if (count($bounds) === 4) {
                $query->withinBounds($bounds[0], $bounds[1], $bounds[2], $bounds[3]);
            }
        }

        $polygons = $query->get();

        $geoJson = [
            'type' => 'FeatureCollection',
            'metadata' => [
                'title' => 'Forest Polygons Export',
                'description' => 'Exported forest polygon data',
                'export_timestamp' => now()->toISOString(),
                'total_features' => $polygons->count(),
                'total_area_hectares' => $polygons->sum('area_hectares'),
                'coordinate_system' => 'WGS84 (EPSG:4326)'
            ],
            'features' => $polygons->map(fn($polygon) => $polygon->toGeoJsonFeature())
        ];

        return response()->json([
            'status' => 'success',
            'data' => $geoJson
        ]);
    }

    public function statistics(Request $request): JsonResponse
    {
        $query = ForestPolygon::query()->active();

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $stats = [
            'total_polygons' => $query->count(),
            'total_area_hectares' => $query->sum('area_hectares'),
            'by_type' => $query->select('type', DB::raw('COUNT(*) as count'), DB::raw('SUM(area_hectares) as total_area'))
                ->groupBy('type')
                ->get(),
            'by_source' => $query->select('source', DB::raw('COUNT(*) as count'))
                ->groupBy('source')
                ->get(),
            'recent_activity' => $query->where('created_at', '>=', now()->subDays(30))->count()
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }
}
