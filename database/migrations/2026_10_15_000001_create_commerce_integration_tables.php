<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('jobs')) {
            Schema::create('jobs', function (Blueprint $table) {
                $table->id();
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        }

        if (!Schema::hasTable('job_batches')) {
            Schema::create('job_batches', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('name');
                $table->integer('total_jobs');
                $table->integer('pending_jobs');
                $table->integer('failed_jobs');
                $table->longText('failed_job_ids');
                $table->mediumText('options')->nullable();
                $table->integer('cancelled_at')->nullable();
                $table->integer('created_at');
                $table->integer('finished_at')->nullable();
            });
        }

        if (!Schema::hasTable('failed_jobs')) {
            Schema::create('failed_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();
            });
        }

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('reserved_stock')->default(0);
            $table->unsignedInteger('low_stock_threshold')->default(5);
            $table->unsignedInteger('weight_grams')->nullable();
            $table->unsignedSmallInteger('length_cm')->nullable();
            $table->unsignedSmallInteger('width_cm')->nullable();
            $table->unsignedSmallInteger('height_cm')->nullable();
            $table->boolean('is_taxed')->default(true);
        });

        Schema::create('shipping_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label', 60)->default('Casa');
            $table->string('recipient', 120);
            $table->string('phone', 30);
            $table->string('street', 160);
            $table->string('number', 30)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('city', 100);
            $table->string('state', 10); // código ISO/DANE departamento (ej. CU)
            $table->string('postal_code', 12);
            $table->string('country', 2)->default('CO');
            $table->string('reference', 255)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->index(['user_id', 'is_default']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('reference', 60)->nullable()->unique();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->string('currency', 3)->default('COP');

            $table->foreignId('shipping_address_id')->nullable()->constrained()->nullOnDelete();
            $table->json('shipping_snapshot')->nullable();
            $table->string('shipping_postal_code', 12)->nullable();
            $table->string('shipping_carrier', 40)->nullable();
            $table->string('shipping_service', 60)->nullable();

            $table->string('customer_document_type', 10)->nullable(); // CC, NIT, CE...
            $table->string('customer_document', 30)->nullable();

            $table->string('wompi_transaction_id', 80)->nullable()->index();
            $table->string('wompi_status', 20)->nullable();
            $table->boolean('stock_reserved')->default(false);
            $table->timestamp('reservation_expires_at')->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('packed_at')->nullable();

            $table->string('tracking_number', 80)->nullable()->index();
            $table->string('tracking_url')->nullable();
            $table->string('label_url')->nullable();
            $table->string('tracking_status', 60)->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();

            $table->string('invoice_number', 40)->nullable();
            $table->string('invoice_cufe', 200)->nullable();
            $table->string('invoice_pdf_path')->nullable();
            $table->string('invoice_xml_path')->nullable();
            $table->timestamp('invoiced_at')->nullable();
        });

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('transaction_id', 80)->unique(); // idempotencia
            $table->string('reference', 60)->index();
            $table->string('status', 20);
            $table->unsignedBigInteger('amount_in_cents');
            $table->string('currency', 3)->default('COP');
            $table->json('payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('integration_logs', function (Blueprint $table) {
            $table->id();
            $table->string('service', 20)->index(); // wompi|envia|factus|resend
            $table->string('action', 60);
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 10)->index(); // success|failed
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->json('request')->nullable();
            $table->json('response')->nullable();
            $table->text('error')->nullable();
            $table->string('retry_job', 120)->nullable(); // clase del job para reintento manual
            $table->unsignedInteger('retries')->default(0);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['service', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_logs');
        Schema::dropIfExists('payment_transactions');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['shipping_address_id']);
            $table->dropColumn([
                'reference', 'subtotal', 'shipping_cost', 'commission_amount', 'currency',
                'shipping_address_id', 'shipping_snapshot', 'shipping_postal_code', 'shipping_carrier',
                'shipping_service', 'customer_document_type', 'customer_document', 'wompi_transaction_id',
                'wompi_status', 'stock_reserved', 'reservation_expires_at', 'paid_at', 'packed_at',
                'tracking_number', 'tracking_url', 'label_url', 'tracking_status', 'shipped_at',
                'delivered_at', 'invoice_number', 'invoice_cufe', 'invoice_pdf_path', 'invoice_xml_path',
                'invoiced_at',
            ]);
        });

        Schema::dropIfExists('shipping_addresses');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'reserved_stock', 'low_stock_threshold', 'weight_grams',
                'length_cm', 'width_cm', 'height_cm', 'is_taxed',
            ]);
        });
    }
};
