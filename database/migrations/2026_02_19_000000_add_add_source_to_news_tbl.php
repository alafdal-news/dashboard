<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_tbl', function (Blueprint $table) {
            $table->enum('add_source', ['old', 'new'])->default('old')->after('user_id')
                  ->comment('Marks whether the article was added from the old or new system');
        });
    }

    public function down(): void
    {
        Schema::table('news_tbl', function (Blueprint $table) {
            $table->dropColumn('add_source');
        });
    }
};
