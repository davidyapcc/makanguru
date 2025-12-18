<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds Google Maps/Places API integration fields to support
     * future data imports and enhanced place information.
     */
    public function up(): void
    {
        Schema::table('places', function (Blueprint $table) {
            // Google Maps/Places API Integration
            $table->string('google_place_id')->nullable()->unique()->after('id');
            $table->string('google_maps_url')->nullable()->after('address');
            $table->decimal('google_rating', 2, 1)->nullable()->after('google_maps_url');
            $table->integer('google_rating_count')->nullable()->after('google_rating');
            $table->string('google_price_level')->nullable()->after('google_rating_count'); // $, $$, $$$, $$$$
            $table->string('phone_number')->nullable()->after('opening_hours');
            $table->string('website')->nullable()->after('phone_number');
            $table->json('google_photos')->nullable()->after('website'); // Array of photo URLs
            $table->json('google_reviews')->nullable()->after('google_photos'); // Sample reviews
            $table->string('business_status')->nullable()->after('google_reviews'); // OPERATIONAL, CLOSED_TEMPORARILY, CLOSED_PERMANENTLY
            $table->boolean('wheelchair_accessible')->default(false)->after('business_status');
            $table->boolean('takeout_available')->default(false)->after('wheelchair_accessible');
            $table->boolean('delivery_available')->default(false)->after('takeout_available');
            $table->boolean('dine_in_available')->default(true)->after('delivery_available');
            $table->boolean('reservations_accepted')->default(false)->after('dine_in_available');

            // Indexing for performance
            $table->index('google_place_id');
            $table->index('google_rating');
            $table->index('business_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('places', function (Blueprint $table) {
            $table->dropIndex(['google_place_id']);
            $table->dropIndex(['google_rating']);
            $table->dropIndex(['business_status']);

            $table->dropColumn([
                'google_place_id',
                'google_maps_url',
                'google_rating',
                'google_rating_count',
                'google_price_level',
                'phone_number',
                'website',
                'google_photos',
                'google_reviews',
                'business_status',
                'wheelchair_accessible',
                'takeout_available',
                'delivery_available',
                'dine_in_available',
                'reservations_accepted',
            ]);
        });
    }
};
