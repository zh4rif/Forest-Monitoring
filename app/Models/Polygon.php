<?php
// app/Models/Polygon.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Objects\Polygon as SpatialPolygon;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;

class Polygon extends Model
{
    use HasFactory, HasSpatial;

    protected $fillable = [
        'user_id',
        'license_number',
        'lot_number',
        'mspo_certificate',
        'geometry',
        'centroid',
        'area_hectares',
        'tree_height',
        'canopy_cover_percentage',
        'ndvi_value',
        'savi_value',
        'classification',
        'notes',
    ];

    protected $casts = [
        'geometry' => SpatialPolygon::class,
        'centroid' => Point::class,
        'area_hectares' => 'decimal:4',
        'tree_height' => 'decimal:2',
        'canopy_cover_percentage' => 'decimal:2',
        'ndvi_value' => 'decimal:3',
        'savi_value' => 'decimal:3',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Auto-classify based on criteria
    public function updateClassification(): void
    {
        $classification = $this->determineClassification();
        $this->update(['classification' => $classification]);
    }

    private function determineClassification(): string
    {
        $treeHeight = $this->tree_height ?? 0;
        $canopyCover = $this->canopy_cover_percentage ?? 0;
        $vegetationIndex = ($this->ndvi_value ?? 0) + ($this->savi_value ?? 0);

        // Classification logic based on criteria
        if ($treeHeight > 5 && $canopyCover > 10 && $vegetationIndex > 1.0) {
            return 'forest';
        } elseif ($treeHeight > 3 && $canopyCover > 5 && $vegetationIndex > 0.7) {
            return 'plantation';
        } elseif ($treeHeight > 1 && $canopyCover > 2 && $vegetationIndex > 0.4) {
            return 'regrowth';
        } elseif ($vegetationIndex > 0.2) {
            return $canopyCover > 1 ? 'new_growth' : 'low_vegetation';
        } else {
            return 'bare_land';
        }
    }
}
