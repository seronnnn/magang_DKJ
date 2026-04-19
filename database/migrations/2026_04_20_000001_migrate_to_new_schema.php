<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for dkj_ar_dashboard_new schema.
 *
 * Replaces the old single `ar_data` table with a fully normalised structure:
 *   users → collectors → customers → plants
 *                                 → invoice → ar_records → collection_logs
 *                                          → so_overlimit
 *   ar_periods
 *   sales, trade
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Drop old table if it exists ──────────────────────────────────
        Schema::dropIfExists('ar_data');

        // ── sales ────────────────────────────────────────────────────────
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('sales_name', 100);
        });

        // ── trade ────────────────────────────────────────────────────────
        Schema::create('trade', function (Blueprint $table) {
            $table->id();
            $table->string('trade_type', 50);
            $table->bigInteger('trade_number');
        });

        // ── ar_periods ───────────────────────────────────────────────────
        Schema::create('ar_periods', function (Blueprint $table) {
            $table->id();
            $table->string('period_label', 50);
            $table->date('period_month')->unique();
            $table->timestamp('created_at')->nullable()->useCurrent();
        });

        // ── collectors ───────────────────────────────────────────────────
        Schema::create('collectors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('name', 100);
            $table->timestamp('created_at')->nullable()->useCurrent();
        });

        // ── customers ────────────────────────────────────────────────────
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collector_id')->constrained('collectors');
            $table->string('customer_id', 20)->index('idx_customer_sap');
            $table->string('customer_name', 150);
            $table->string('pic_name', 100)->nullable();
            $table->string('email', 150)->nullable();
            $table->text('address')->nullable();
            $table->text('remark')->nullable();
            $table->bigInteger('whatsapp_number')->nullable();
            $table->bigInteger('office_number')->nullable();
            $table->timestamps();
        });

        // ── plants ───────────────────────────────────────────────────────
        Schema::create('plants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->string('code', 10);
            $table->string('name', 100);
            $table->timestamp('created_at')->nullable()->useCurrent();
        });

        // ── invoice ──────────────────────────────────────────────────────
        Schema::create('invoice', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_id')->constrained('sales');
            $table->foreignId('trade_id')->constrained('trade');
            $table->foreignId('customer_id')->constrained('customers');
            $table->date('due_date');
            $table->date('baseline_date');
            $table->date('tax_date')->nullable();
            $table->string('currency_type', 10)->default('IDR');
            $table->decimal('amount_paid', 20, 2)->default(0);
        });

        // ── ar_records ───────────────────────────────────────────────────
        Schema::create('ar_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoice')->onDelete('cascade');
            $table->foreignId('period_id')->constrained('ar_periods');
            $table->decimal('amount_current', 20, 2)->default(0);
            $table->decimal('amount_1_30_days', 20, 2)->default(0);
            $table->decimal('amount_30_60_days', 20, 2)->default(0);
            $table->decimal('amount_60_90_days', 20, 2)->default(0);
            $table->decimal('amount_over_90_days', 20, 2)->default(0);
            $table->decimal('total_ar', 20, 2)->default(0);
            $table->decimal('ar_target', 20, 2)->default(0);
            $table->decimal('ar_actual', 20, 2)->default(0);
            $table->timestamps();
        });

        // ── collection_logs ──────────────────────────────────────────────
        Schema::create('collection_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ar_record_id')->constrained('ar_records')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users');
            $table->decimal('amount_collected', 20, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('collected_at')->nullable()->useCurrent();
        });

        // ── so_overlimit ─────────────────────────────────────────────────
        Schema::create('so_overlimit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoice')->onDelete('cascade');
            $table->foreignId('period_id')->constrained('ar_periods');
            $table->integer('so_without_od')->default(0);
            $table->integer('so_with_od')->default(0);
            $table->integer('total_so')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('so_overlimit');
        Schema::dropIfExists('collection_logs');
        Schema::dropIfExists('ar_records');
        Schema::dropIfExists('invoice');
        Schema::dropIfExists('plants');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('collectors');
        Schema::dropIfExists('ar_periods');
        Schema::dropIfExists('trade');
        Schema::dropIfExists('sales');

        // Restore old table
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
};