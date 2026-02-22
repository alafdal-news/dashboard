<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanArticleTitles extends Command
{
    protected $signature = 'articles:clean-titles
                            {--dry-run : Preview changes without saving}
                            {--limit=0 : Limit number of articles to process (0 = all)}
                            {--id= : Clean a specific article by news_id}';

    protected $description = 'Clean legacy HTML from news_title (strip tags, decode entities, normalize whitespace)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');
        $specificId = $this->option('id');

        if ($dryRun) {
            $this->info('🔍 DRY RUN — no changes will be saved.');
        }

        $query = DB::table('news_tbl')->select('news_id', 'news_title');

        if ($specificId) {
            $query->where('news_id', $specificId);
        }
        if ($limit > 0) {
            $query->limit($limit);
        }

        $articles = $query->get();
        $this->info("Found {$articles->count()} articles to process.");

        $changed = 0;
        $skipped = 0;

        $bar = $this->output->createProgressBar($articles->count());
        $bar->start();

        foreach ($articles as $article) {
            $original = $article->news_title ?? '';
            $cleaned = $this->cleanTitle($original);

            if ($original !== $cleaned) {
                $changed++;

                if ($dryRun) {
                    $bar->clear();
                    $this->newLine();
                    $this->warn("━━━ Article #{$article->news_id}");
                    $this->line("<fg=red>BEFORE:</> {$original}");
                    $this->line("<fg=green>AFTER:</>  {$cleaned}");
                    $bar->display();
                } else {
                    DB::table('news_tbl')
                        ->where('news_id', $article->news_id)
                        ->update(['news_title' => $cleaned]);
                }
            } else {
                $skipped++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ Done! Changed: {$changed} | Already clean: {$skipped}");

        if ($dryRun && $changed > 0) {
            $this->warn("Run without --dry-run to apply changes.");
        }

        return Command::SUCCESS;
    }

    private function cleanTitle(string $title): string
    {
        // 1. Decode HTML entities (twice for double-encoded)
        $title = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $title = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // 2. Strip ALL HTML tags — titles should be plain text
        $title = strip_tags($title);

        // 3. Remove non-breaking spaces (replace with regular space)
        $title = str_replace(["\xC2\xA0", '&nbsp;'], ' ', $title);

        // 4. Collapse multiple spaces into one
        $title = preg_replace('/\s+/', ' ', $title);

        // 5. Trim
        $title = trim($title);

        return $title;
    }
}
