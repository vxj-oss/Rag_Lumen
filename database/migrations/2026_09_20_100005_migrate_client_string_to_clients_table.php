<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $names = DB::table('projects')
            ->whereNull('client_id')
            ->whereNotNull('client')
            ->distinct()
            ->pluck('client');

        foreach ($names as $name) {
            $clientId = DB::table('clients')->where('name', $name)->value('id');

            if ($clientId === null) {
                $clientId = DB::table('clients')->insertGetId([
                    'name' => $name,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('projects')->whereNull('client_id')->where('client', $name)->update(['client_id' => $clientId]);
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('client');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('client', 150)->nullable()->after('type');
        });
    }
};
