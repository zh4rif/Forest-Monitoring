<?php

// app/Models/ForestPolygon.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ForestPolygon extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'geometry',
        'type',
        'height_category',
        'canopy_density',
        'source',
        'area_hectares',
        'area_square_meters',
        'centroid_latitude',
        'centroid_longitude',
        'name',
        'description',
        'additional_properties',
        'confidence_level',
        'observation_date',
        'user_id',
        'organization_id',
        'is_active',
        'is_public',
        'verified_at',
        'verified_by'
    ];

    protected $casts = [
        'additional_properties' => 'array',
        'area_hectares' => 'decimal:6',
        'area_square_meters' => 'decimal:6',
        'centroid_latitude' => 'decimal:7',
        'centroid_longitude' => 'decimal:7',
        'observation_date' => 'date',
        'verified_at' => 'datetime',
        'is_active' => 'boolean',
        'is_public' => 'boolean'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
        });
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeWithinBounds($query, $minLat, $minLng, $maxLat, $maxLng)
    {
        return $query->whereRaw(
            'ST_Within(geometry, ST_MakeEnvelope(?, ?, ?, ?, 4326))',
            [$minLng, $minLat, $maxLng, $maxLat]
        );
    }

    // Accessors & Mutators
    public function getGeometryAttribute($value)
    {
        if ($value) {
            // Convert PostGIS geometry to GeoJSON
            $geoJson = \DB::selectOne(
                'SELECT ST_AsGeoJSON(?) as geojson',
                [$value]
            );
            return json_decode($geoJson->geojson, true);
        }
        return null;
    }

    public function setGeometryAttribute($value)
    {
        if (is_array($value)) {
            // Convert GeoJSON array to PostGIS geometry
            $geoJsonString = json_encode($value);
            $this->attributes['geometry'] = \DB::raw("ST_SetSRID(ST_GeomFromGeoJSON('$geoJsonString'), 4326)");
        } elseif (is_string($value)) {
            // Handle GeoJSON string or WKT
            if (str_starts_with(trim($value), '{')) {
                // GeoJSON string
                $this->attributes['geometry'] = \DB::raw("ST_SetSRID(ST_GeomFromGeoJSON('$value'), 4326)");
            } else {
                // WKT string
                $this->attributes['geometry'] = \DB::raw("ST_SetSRID(ST_GeomFromText('$value'), 4326)");
            }
        }
    }

    // Helper Methods
    public function toGeoJsonFeature(): array
    {
        return [
            'type' => 'Feature',
            'properties' => [
                'id' => $this->uuid,
                'type' => $this->type,
                'height_category' => $this->height_category,
                'canopy_density' => $this->canopy_density,
                'source' => $this->source,
                'area_hectares' => $this->area_hectares,
                'area_square_meters' => $this->area_square_meters,
                'name' => $this->name,
                'description' => $this->description,
                'confidence_level' => $this->confidence_level,
                'observation_date' => $this->observation_date?->toDateString(),
                'created_at' => $this->created_at->toISOString(),
                'updated_at' => $this->updated_at->toISOString(),
                ...$this->additional_properties ?? []
            ],
            'geometry' => $this->geometry
        ];
    }

    public static function createFromGeoJson(array $geoJsonFeature, ?int $userId = null): self
    {
        $properties = $geoJsonFeature['properties'] ?? [];

        return self::create([
            'geometry' => $geoJsonFeature['geometry'],
            'type' => $properties['type'] ?? $properties['forest_type'] ?? 'unknown',
            'height_category' => $properties['height_category'] ?? null,
            'canopy_density' => $properties['canopy_density'] ?? null,
            'source' => $properties['source'] ?? 'imported',
            'name' => $properties['name'] ?? null,
            'description' => $properties['description'] ?? null,
            'observation_date' => isset($properties['observation_date'])
                ? \Carbon\Carbon::parse($properties['observation_date'])
                : null,
            'confidence_level' => $properties['confidence_level'] ?? 'medium',
            'user_id' => $userId,
            'additional_properties' => $properties
        ]);
    }
}
