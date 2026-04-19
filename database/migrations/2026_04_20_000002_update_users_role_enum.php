<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Change role column from varchar to enum with new values
        DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('admin','manager','collector') NOT NULL DEFAULT 'collector'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `users` MODIFY `role` VARCHAR(50) NOT NULL DEFAULT 'user'");
    }
};