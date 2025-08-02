<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('decision_rules', function (Blueprint $table) {
            $table->id();
            $table->string('item_code', 50)->comment('The item code identifier');
            $table->string('frequency', 20)->comment('Frequency level (Almost Always, Frequently, etc.)');
            $table->string('domain', 50)->comment('Domain category for the item');
            $table->text('decision_text')->comment('The decision rule text for this item-frequency combination');
            $table->timestamps();

            // Add unique constraint on item_code + frequency combination
            $table->unique(['item_code', 'frequency'], 'unique_item_frequency');

            // Add indexes for performance optimization
            $table->index('item_code', 'idx_item_code');
            $table->index('frequency', 'idx_frequency');
            $table->index('domain', 'idx_domain');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('decision_rules');
    }
};