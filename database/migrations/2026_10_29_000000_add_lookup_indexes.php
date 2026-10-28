<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Indexes for lookups the pages run on every visit: a member's quotes, an author's quotes,
// what a member liked or saved, and follower counts. Existing indexes are left as they are.
return new class extends Migration
{
    private array $indexes = [
        'quotes' => [['user_id', 'created_at'], ['author']],
        'quote_likes' => [['user_id']],
        'quote_favorites' => [['user_id']],
        'user_follows' => [['followed_id']],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $table => $sets) {
            foreach ($sets as $columns) {
                $name = $table.'_'.implode('_', $columns).'_lookup_index';
                if (! Schema::hasIndex($table, $name)) {
                    Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($columns, $name));
                }
            }
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $table => $sets) {
            foreach ($sets as $columns) {
                $name = $table.'_'.implode('_', $columns).'_lookup_index';
                if (Schema::hasIndex($table, $name)) {
                    Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($name));
                }
            }
        }
    }
};
