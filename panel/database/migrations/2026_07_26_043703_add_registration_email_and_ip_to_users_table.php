<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('registration_email')->nullable()->after('email');
            $table->string('ip', 45)->nullable()->after('registration_email');
        });

        User::query()
            ->whereNull('registration_email')
            ->update(['registration_email' => DB::raw('email')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['registration_email', 'ip']);
        });
    }
};
