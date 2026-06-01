<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appreciations', function (Blueprint $table) {
            // Nullable so existing rows survive and so deleting a reason can null it out.
            // Required selection is enforced at the request layer for new appreciations.
            $table->foreignId('reason_id')
                ->nullable()
                ->after('receiver_id')
                ->constrained('appreciation_reasons')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('appreciations', function (Blueprint $table) {
            $table->dropForeign(['reason_id']);
            $table->dropColumn('reason_id');
        });
    }
};
