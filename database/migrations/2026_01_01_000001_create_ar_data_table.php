<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ar_data', function (Blueprint $table) {
            $table->id();
            $table->string('plant', 10)->index();
            $table->string('customer_id', 20)->index();
            $table->string('customer_name', 150);
            $table->string('collection_by', 50)->index();
            $table->bigInteger('current')->default(0);
            $table->bigInteger('days_1_30')->default(0);
            $table->bigInteger('days_30_60')->default(0);
            $table->bigInteger('days_60_90')->default(0);
            $table->bigInteger('days_over_90')->default(0);
            $table->bigInteger('total')->default(0);
            $table->integer('so_without_od')->default(0);
            $table->integer('so_with_od')->default(0);
            $table->integer('total_so')->default(0);
            $table->bigInteger('ar_target')->default(0);
            $table->bigInteger('ar_actual')->default(0);
            $table->date('period')->default('2026-01-31')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ar_data');
    }
};
