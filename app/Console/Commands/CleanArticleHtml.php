<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanArticleHtml extends Command
{
    protected $signature = 'articles:clean-html
                            {--dry-run : Preview changes without saving}
                            {--limit=0 : Limit number of articles to process (0 = all)}
                            {--id= : Clean a specific article by news_id}';

    protected $description = 'Clean legacy HTML from news_desc (remove empty paragraphs, &nbsp;, normalize tags)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');
        $specificId = $this->option('id');

        if ($dryRun) {
            $this->info('🔍 DRY RUN — no changes will be saved.');
        }

        // Build query
        $query = DB::table('news_tbl')->select('news_id', 'news_title', 'news_desc');

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
            $original = $article->news_desc ?? '';
            $cleaned = $this->cleanHtml($original);

            if ($original !== $cleaned) {
                $changed++;

                if ($dryRun) {
                    $bar->clear();
                    $this->newLine();
                    $this->warn("━━━ Article #{$article->news_id}: {$article->news_title}");
                    $this->line("<fg=red>BEFORE:</> " . mb_substr($original, 0, 200) . '…');
                    $this->line("<fg=green>AFTER:</>  " . mb_substr($cleaned, 0, 200) . '…');
                    $bar->display();
                } else {
                    DB::table('news_tbl')
                        ->where('news_id', $article->news_id)
                        ->update(['news_desc' => $cleaned]);
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

    /**
     * Clean legacy HTML content.
     */
    private function cleanHtml(string $html): string
    {
        // 1. Decode double-encoded HTML entities (e.g. &amp;nbsp; → &nbsp; → actual space)
        //    Run twice to handle double-encoding
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // 2. Remove <p>&nbsp;</p> and <p> </p> empty paragraphs
        $html = preg_replace('/<p>\s*(&nbsp;|\x{00A0}|\s)*\s*<\/p>/u', '', $html);

        // 3. Remove <br>&nbsp; and standalone &nbsp;
        $html = preg_replace('/(<br\s*\/?>)\s*(&nbsp;|\x{00A0})+/u', '$1', $html);

        // 4. Remove empty tags: <p></p>, <div></div>, <span></span>
        $html = preg_replace('/<(p|div|span)>\s*<\/\1>/i', '', $html);

        // 5. Collapse 3+ consecutive <br> into 2
        $html = preg_replace('/(<br\s*\/?>\s*){3,}/i', '<br><br>', $html);

        // 6. Remove leading/trailing <br> tags
        $html = preg_replace('/^(\s*<br\s*\/?>\s*)+/i', '', $html);
        $html = preg_replace('/(\s*<br\s*\/?>\s*)+$/i', '', $html);

        // 7. Remove inline styles (font-family, font-size, color from old editors)
        $html = preg_replace('/\s*style="[^"]*"/i', '', $html);

        // 8. Remove empty class attributes
        $html = preg_replace('/\s*class=""/i', '', $html);

        // 9. Remove Word/Office junk tags
        $html = preg_replace('/<\/?o:[^>]*>/i', '', $html);    // <o:p> tags
        $html = preg_replace('/<\/?st1:[^>]*>/i', '', $html);  // <st1:*> tags

        // 10. Normalize whitespace between tags (but preserve content whitespace)
        $html = preg_replace('/>\s{2,}</', '> <', $html);

        // 11. Trim
        $html = trim($html);

        return $html;
    }
}
