<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('polygons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('license_number')->nullable();
            $table->string('lot_number')->nullable();
            $table->string('mspo_certificate')->nullable();

            // Store geometry as Well-Known Text instead of PostGIS binary
            $table->text('geometry_wkt'); // POLYGON((lng lat, lng lat, ...))

            // Store centroid as separate latitude/longitude columns
            $table->decimal('centroid_latitude', 10, 7);
            $table->decimal('centroid_longitude', 10, 7);

            $table->decimal('area_hectares', 10, 4);
            $table->decimal('tree_height', 5, 2)->nullable();
            $table->decimal('canopy_cover_percentage', 5, 2)->nullable();
            $table->decimal('ndvi_value', 4, 3)->nullable();
            $table->decimal('savi_value', 4, 3)->nullable();

            $table->enum('classification', [
                'bare_land',
                'new_growth',
                'regrowth',
                'plantation',
                'low_vegetation',
                'forest'
            ])->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            // Add index for spatial queries on centroid
            $table->index(['centroid_latitude', 'centroid_longitude'], 'polygons_centroid_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('polygons');
    }
};
