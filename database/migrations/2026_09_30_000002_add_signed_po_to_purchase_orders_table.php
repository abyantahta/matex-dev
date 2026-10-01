<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('signed_po_path')->nullable()->after('qad_response');
            $table->string('signed_po_original_filename')->nullable()->after('signed_po_path');
            $table->timestamp('signed_po_uploaded_at')->nullable()->after('signed_po_original_filename');
            $table->foreignId('signed_po_uploaded_by')->nullable()->after('signed_po_uploaded_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('signed_po_uploaded_by');
            $table->dropColumn(['signed_po_path', 'signed_po_original_filename', 'signed_po_uploaded_at']);
        });
    }
};
