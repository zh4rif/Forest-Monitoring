<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class GeospatialService
{
    /**
     * Calculate the area of a polygon in hectares
     */
    public static function calculateArea(array $geometry): float
    {
        $geoJsonString = json_encode($geometry);

        $result = DB::selectOne(
            'SELECT ST_Area(ST_Transform(ST_SetSRID(ST_GeomFromGeoJSON(?), 4326), 3857)) / 10000.0 as area_hectares',
            [$geoJsonString]
        );

        return round($result->area_hectares, 6);
    }

    /**
     * Calculate the centroid of a polygon
     */
    public static function calculateCentroid(array $geometry): array
    {
        $geoJsonString = json_encode($geometry);

        $result = DB::selectOne(
            'SELECT ST_Y(ST_Centroid(ST_SetSRID(ST_GeomFromGeoJSON(?), 4326))) as lat,
                    ST_X(ST_Centroid(ST_SetSRID(ST_GeomFromGeoJSON(?), 4326))) as lng',
            [$geoJsonString, $geoJsonString]
        );

        return [
            'latitude' => round($result->lat, 7),
            'longitude' => round($result->lng, 7)
        ];
    }

    /**
     * Find polygons within a bounding box
     */
    public static function findWithinBounds(float $minLat, float $minLng, float $maxLat, float $maxLng)
    {
        return DB::select(
            'SELECT * FROM forest_polygons
             WHERE ST_Within(geometry, ST_MakeEnvelope(?, ?, ?, ?, 4326))
             AND is_active = true',
            [$minLng, $minLat, $maxLng, $maxLat]
        );
    }

    /**
     * Find polygons that intersect with a given polygon
     */
    public static function findIntersecting(array $geometry)
    {
        $geoJsonString = json_encode($geometry);

        return DB::select(
            'SELECT * FROM forest_polygons
             WHERE ST_Intersects(geometry, ST_SetSRID(ST_GeomFromGeoJSON(?), 4326))
             AND is_active = true',
            [$geoJsonString]
        );
    }

    /**
     * Validate polygon geometry
     */
    public static function isValidGeometry(array $geometry): bool
    {
        try {
            $geoJsonString = json_encode($geometry);

            $result = DB::selectOne(
                'SELECT ST_IsValid(ST_SetSRID(ST_GeomFromGeoJSON(?), 4326)) as is_valid',
                [$geoJsonString]
            );

            return (bool) $result->is_valid;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Simplify polygon geometry (reduce number of points)
     */
    public static function simplifyGeometry(array $geometry, float $tolerance = 0.0001): array
    {
        $geoJsonString = json_encode($geometry);

        $result = DB::selectOne(
            'SELECT ST_AsGeoJSON(ST_Simplify(ST_SetSRID(ST_GeomFromGeoJSON(?), 4326), ?)) as simplified',
            [$geoJsonString, $tolerance]
        );

        return json_decode($result->simplified, true);
    }

    /**
     * Calculate distance between two points in meters
     */
    public static function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $result = DB::selectOne(
            'SELECT ST_Distance(
                ST_Transform(ST_SetSRID(ST_MakePoint(?, ?), 4326), 3857),
                ST_Transform(ST_SetSRID(ST_MakePoint(?, ?), 4326), 3857)
            ) as distance',
            [$lng1, $lat1, $lng2, $lat2]
        );

        return round($result->distance, 2);
    }
}
