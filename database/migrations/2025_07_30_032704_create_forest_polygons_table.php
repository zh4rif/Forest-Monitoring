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
        Schema::create('forest_polygons', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index(); // For public API access

            // Geometry field using PostGIS
            // If PostGIS is not available, you can use 'text' and store as GeoJSON/WKT
            $table->geometry('geometry'); // PostGIS GEOMETRY type

            // Forest classification fields
            $table->enum('type', [
                'primary',
                'secondary',
                'plantation',
                'degraded',
                'regrowth',
                'mangrove',
                'peat_swamp',
                'montane',
                'unknown'
            ])->default('unknown');

            $table->enum('height_category', [
                'tall',      // >20m
                'medium',    // 10-20m
                'short',     // 5-10m
                'low'        // <5m
            ])->nullable();

            $table->enum('canopy_density', [
                'dense',        // >70%
                'medium',       // 40-70%
                'sparse',       // 10-40%
                'very_sparse'   // <10%
            ])->nullable();

            $table->enum('source', [
                'manual',
                'satellite',
                'lidar',
                'drone',
                'field_survey',
                'imported'
            ])->default('manual');

            // Area calculations (stored for quick access)
            $table->decimal('area_hectares', 15, 6)->nullable();
            $table->decimal('area_square_meters', 20, 6)->nullable();

            // Centroid coordinates (for quick spatial queries)
            $table->decimal('centroid_latitude', 10, 7)->nullable();
            $table->decimal('centroid_longitude', 11, 7)->nullable();

            // Additional metadata
            $table->text('name')->nullable(); // User-defined name
            $table->text('description')->nullable();
            $table->json('additional_properties')->nullable(); // Store extra GeoJSON properties

            // Data quality and confidence
            $table->enum('confidence_level', ['high', 'medium', 'low'])->default('medium');
            $table->date('observation_date')->nullable(); // When the forest was observed/measured

            // User and organization tracking
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->onDelete('set null');

            // Versioning and audit trail
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(false); // For data sharing
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();

            // Indexes for performance
            $table->index(['type', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['area_hectares']);
            $table->index(['observation_date']);
            $table->index(['is_active', 'is_public']);

            // Spatial indexes (PostGIS)
            // These will be added in the migration using DB::statement
        });

        // Add spatial indexes and constraints (PostGIS specific)
        if (config('database.default') === 'pgsql') {
            // Create spatial index for faster geometric queries
            DB::statement('CREATE INDEX forest_polygons_geometry_idx ON forest_polygons USING GIST (geometry)');

            // Add constraint to ensure only Polygon geometries
            DB::statement('ALTER TABLE forest_polygons ADD CONSTRAINT enforce_geotype_geometry CHECK (ST_GeometryType(geometry) = \'ST_Polygon\'::text OR geometry IS NULL)');

            // Add constraint to ensure geometries are in WGS84 (SRID 4326)
            DB::statement('ALTER TABLE forest_polygons ADD CONSTRAINT enforce_srid_geometry CHECK (ST_SRID(geometry) = 4326)');

            // Create function to automatically calculate area and centroid
            DB::statement('
                CREATE OR REPLACE FUNCTION update_forest_polygon_metrics()
                RETURNS TRIGGER AS $
                BEGIN
                    IF NEW.geometry IS NOT NULL THEN
                        -- Calculate area in square meters and hectares
                        NEW.area_square_meters := ST_Area(ST_Transform(NEW.geometry, 3857));
                        NEW.area_hectares := NEW.area_square_meters / 10000.0;

                        -- Calculate centroid
                        NEW.centroid_latitude := ST_Y(ST_Centroid(NEW.geometry));
                        NEW.centroid_longitude := ST_X(ST_Centroid(NEW.geometry));
                    END IF;
                    RETURN NEW;
                END;
                $ LANGUAGE plpgsql;
            ');

            // Create trigger to automatically update metrics
            DB::statement('
                CREATE TRIGGER forest_polygon_metrics_trigger
                BEFORE INSERT OR UPDATE ON forest_polygons
                FOR EACH ROW
                EXECUTE FUNCTION update_forest_polygon_metrics();
            ');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop PostGIS specific elements first
        if (config('database.default') === 'pgsql') {
            DB::statement('DROP TRIGGER IF EXISTS forest_polygon_metrics_trigger ON forest_polygons');
            DB::statement('DROP FUNCTION IF EXISTS update_forest_polygon_metrics()');
        }

        Schema::dropIfExists('forest_polygons');
    }
};
