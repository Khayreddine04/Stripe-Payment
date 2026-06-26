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
        if (!Schema::hasTable('pt_domains')) {
            Schema::create('pt_domains', function (Blueprint $table): void {
                $table->id();
                $table->string('domain', 255)->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('pt_item_domains')) {
            Schema::create('pt_item_domains', function (Blueprint $table): void {
                $table->id();
                $table->string('item_id', 50);
                $table->unsignedBigInteger('domain_id');
                $table->timestamps();

                $table->unique(['item_id', 'domain_id'], 'pt_item_domains_item_domain_unique');
                $table->index('item_id', 'pt_item_domains_item_id_index');
                $table->foreign('domain_id', 'pt_item_domains_domain_fk')
                    ->references('id')
                    ->on('pt_domains')
                    ->onDelete('cascade');
            });
        }

        if (Schema::hasTable('pt_invoices')) {
            Schema::table('pt_invoices', function (Blueprint $table): void {
                if (!Schema::hasColumn('pt_invoices', 'checkout_domain')) {
                    $table->string('checkout_domain', 255)->nullable()->after('idInvoice');
                    $table->index('checkout_domain', 'pt_invoices_checkout_domain_index');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('pt_invoices') && Schema::hasColumn('pt_invoices', 'checkout_domain')) {
            Schema::table('pt_invoices', function (Blueprint $table): void {
                $table->dropIndex('pt_invoices_checkout_domain_index');
                $table->dropColumn('checkout_domain');
            });
        }

        if (Schema::hasTable('pt_item_domains')) {
            Schema::table('pt_item_domains', function (Blueprint $table): void {
                $table->dropForeign('pt_item_domains_domain_fk');
                $table->dropUnique('pt_item_domains_item_domain_unique');
                $table->dropIndex('pt_item_domains_item_id_index');
            });
            Schema::dropIfExists('pt_item_domains');
        }

        Schema::dropIfExists('pt_domains');
    }
};
