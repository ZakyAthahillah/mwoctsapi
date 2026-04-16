<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'area_id')) {
                $table->foreignId('area_id')->nullable()->after('id')->constrained('areas')->nullOnDelete();
            }

            if (! Schema::hasColumn('users', 'username')) {
                $table->string('username', 20)->nullable()->after('email');
            }

            if (! Schema::hasColumn('users', 'image')) {
                $table->string('image')->nullable()->after('username');
            }

            if (! Schema::hasColumn('users', 'status')) {
                $table->unsignedTinyInteger('status')->default(1)->after('image');
            }

            if (! Schema::hasColumn('users', 'is_operator')) {
                $table->unsignedTinyInteger('is_operator')->default(0)->after('updated_at');
            }
        });

        if (Schema::hasColumn('users', 'username')) {
            $users = DB::table('users')->select('id', 'email', 'username')->get();

            foreach ($users as $user) {
                if ($user->username !== null && $user->username !== '') {
                    continue;
                }

                $baseUsername = strtolower((string) preg_replace('/[^A-Za-z0-9]/', '', strtok((string) $user->email, '@')));
                $baseUsername = $baseUsername !== '' ? substr($baseUsername, 0, 20) : 'user'.$user->id;
                $username = $baseUsername;
                $suffix = 1;

                while (DB::table('users')
                    ->where('username', $username)
                    ->where('id', '<>', $user->id)
                    ->exists()) {
                    $trimmedBase = substr($baseUsername, 0, max(1, 20 - strlen((string) $suffix)));
                    $username = $trimmedBase.$suffix;
                    $suffix++;
                }

                DB::table('users')->where('id', $user->id)->update([
                    'username' => $username,
                ]);
            }
        }

        if (Schema::hasColumn('users', 'username') && ! $this->hasUniqueIndex('users', 'users_username_unique')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unique('username', 'users_username_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'area_id')) {
                $table->dropConstrainedForeignId('area_id');
            }

            if (Schema::hasColumn('users', 'username')) {
                if ($this->hasUniqueIndex('users', 'users_username_unique')) {
                    $table->dropUnique('users_username_unique');
                }

                $table->dropColumn('username');
            }

            if (Schema::hasColumn('users', 'image')) {
                $table->dropColumn('image');
            }

            if (Schema::hasColumn('users', 'status')) {
                $table->dropColumn('status');
            }

            if (Schema::hasColumn('users', 'is_operator')) {
                $table->dropColumn('is_operator');
            }
        });
    }

    private function hasUniqueIndex(string $tableName, string $indexName): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        return match ($driver) {
            'sqlite' => collect(DB::select("PRAGMA index_list('{$tableName}')"))->contains(
                fn ($index) => $index->name === $indexName
            ),
            'sqlsrv' => DB::table('sys.indexes')->where('name', $indexName)->exists(),
            default => false,
        };
    }
};
