<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Settings (Comisión, Tarifa de Banner, SLA, etc.)
        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->string('group')->default('general');
                $table->string('description')->nullable();
                $table->timestamps();
            });

            DB::table('settings')->insert([
                [
                    'key' => 'commission_rate',
                    'value' => '8.0',
                    'group' => 'financial',
                    'description' => 'Porcentaje de comisión cobrado por FusaShop sobre las ventas (%)',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'key' => 'banner_fee',
                    'value' => '150000',
                    'group' => 'financial',
                    'description' => 'Tarifa de pauta de banner patrocinado en COP',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'key' => 'pqrs_sla_business_days',
                    'value' => '15',
                    'group' => 'operations',
                    'description' => 'Días hábiles máximos para responder PQRS (Ley 1755 de 2015)',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        // 2. Admin Audit Logs
        if (!Schema::hasTable('admin_audit_logs')) {
            Schema::create('admin_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action');
                $table->string('target_type')->nullable();
                $table->unsignedBigInteger('target_id')->nullable();
                $table->text('reason')->nullable();
                $table->json('before_state')->nullable();
                $table->json('after_state')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent')->nullable();
                $table->timestamps();

                $table->index(['action', 'created_at']);
                $table->index(['target_type', 'target_id']);
            });
        }

        // 3. Users additions (SoftDeletes, blocked_reason, admin_subrole, last_seen_at, 2FA)
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'admin_subrole')) {
                $table->string('admin_subrole')->default('super_admin')->after('role');
            }
            if (!Schema::hasColumn('users', 'blocked_reason')) {
                $table->text('blocked_reason')->nullable()->after('is_blocked');
            }
            if (!Schema::hasColumn('users', 'deleted_reason')) {
                $table->text('deleted_reason')->nullable()->after('blocked_reason');
            }
            if (!Schema::hasColumn('users', 'last_seen_at')) {
                $table->timestamp('last_seen_at')->nullable();
            }
            if (!Schema::hasColumn('users', 'two_factor_enabled')) {
                $table->boolean('two_factor_enabled')->default(false);
            }
            if (!Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // 4. Products additions (type: product|service, sector_key, subsector_key, views_count)
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'type')) {
                $table->string('type', 20)->default('product')->after('category');
            }
            if (!Schema::hasColumn('products', 'sector_key')) {
                $table->string('sector_key', 50)->nullable()->after('type');
            }
            if (!Schema::hasColumn('products', 'subsector_key')) {
                $table->string('subsector_key', 80)->nullable()->after('sector_key');
            }
            if (!Schema::hasColumn('products', 'views_count')) {
                $table->unsignedInteger('views_count')->default(0);
            }
        });

        // 5. Company Profiles additions (sector_override, subsector_override, settlement_status, kyc_rejection_code)
        Schema::table('company_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('company_profiles', 'sector_override')) {
                $table->string('sector_override', 50)->nullable()->after('business_type');
            }
            if (!Schema::hasColumn('company_profiles', 'subsector_override')) {
                $table->string('subsector_override', 80)->nullable()->after('sector_override');
            }
            if (!Schema::hasColumn('company_profiles', 'kyc_rejection_code')) {
                $table->string('kyc_rejection_code', 60)->nullable()->after('kyc_status');
            }
            if (!Schema::hasColumn('company_profiles', 'settlement_status')) {
                $table->string('settlement_status', 30)->default('pending');
            }
            if (!Schema::hasColumn('company_profiles', 'settled_at')) {
                $table->timestamp('settled_at')->nullable();
            }
        });

        // 6. Global Banners additions (starts_at, ends_at, impressions_count, clicks_count, softDeletes)
        Schema::table('global_banners', function (Blueprint $table) {
            if (!Schema::hasColumn('global_banners', 'starts_at')) {
                $table->timestamp('starts_at')->nullable();
            }
            if (!Schema::hasColumn('global_banners', 'ends_at')) {
                $table->timestamp('ends_at')->nullable();
            }
            if (!Schema::hasColumn('global_banners', 'impressions_count')) {
                $table->unsignedInteger('impressions_count')->default(0);
            }
            if (!Schema::hasColumn('global_banners', 'clicks_count')) {
                $table->unsignedInteger('clicks_count')->default(0);
            }
            if (!Schema::hasColumn('global_banners', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // 7. Banner Requests additions
        Schema::table('banner_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('banner_requests', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable();
            }
        });

        // 8. PQRS additions (category, priority, due_at, assigned_to, merchant_id, conversation_history)
        Schema::table('p_q_r_s', function (Blueprint $table) {
            if (!Schema::hasColumn('p_q_r_s', 'category')) {
                $table->string('category', 40)->default('plataforma')->after('type');
            }
            if (!Schema::hasColumn('p_q_r_s', 'priority')) {
                $table->string('priority', 20)->default('media')->after('category');
            }
            if (!Schema::hasColumn('p_q_r_s', 'due_at')) {
                $table->timestamp('due_at')->nullable()->after('resolved_at');
            }
            if (!Schema::hasColumn('p_q_r_s', 'assigned_to')) {
                $table->unsignedBigInteger('assigned_to')->nullable();
            }
            if (!Schema::hasColumn('p_q_r_s', 'merchant_id')) {
                $table->unsignedBigInteger('merchant_id')->nullable();
            }
            if (!Schema::hasColumn('p_q_r_s', 'conversation_history')) {
                $table->json('conversation_history')->nullable();
            }
        });

        // 9. Reviews additions (topics)
        Schema::table('reviews', function (Blueprint $table) {
            if (!Schema::hasColumn('reviews', 'topics')) {
                $table->json('topics')->nullable();
            }
        });

        // 10. ML Runs History
        if (!Schema::hasTable('ml_runs')) {
            Schema::create('ml_runs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status', 30)->default('completed');
                $table->json('parameters')->nullable();
                $table->json('results')->nullable();
                $table->text('summary')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }

        // 11. Search Logs (para detectar oportunidades y demanda no atendida)
        if (!Schema::hasTable('search_logs')) {
            Schema::create('search_logs', function (Blueprint $table) {
                $table->id();
                $table->string('query');
                $table->unsignedInteger('results_count')->default(0);
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamps();

                $table->index(['results_count', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('search_logs');
        Schema::dropIfExists('ml_runs');
        Schema::dropIfExists('admin_audit_logs');
        Schema::dropIfExists('settings');
    }
};
