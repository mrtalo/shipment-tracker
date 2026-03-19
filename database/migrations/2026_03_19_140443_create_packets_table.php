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
        Schema::create('packets', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_code')->unique();
            $table->string('recipient_name');
            $table->string('recipient_email');
            $table->text('destination_address');
            $table->unsignedInteger('weight_grams');
            $table->string('status')->default('created')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packets');
    }
};
