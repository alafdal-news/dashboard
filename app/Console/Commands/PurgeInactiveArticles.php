<?php

namespace App\Console\Commands;

use App\Models\Article;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PurgeInactiveArticles extends Command
{
    protected $signature = 'articles:purge-inactive
                            {--dry-run : Preview what would be deleted without making changes}
                            {--chunk=200 : Number of articles to process per batch}';

    protected $description = 'Permanently delete all inactive articles (active=0), their gallery records, and physical image directories';

    private int $deletedArticles = 0;
    private int $deletedGalleryRows = 0;
    private int $deletedDirectories = 0;
    private int $errors = 0;

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $chunkSize = (int) $this->option('chunk');

        $total = Article::where('active', '0')->count();

        if ($total === 0) {
            $this->info('No inactive articles found. Nothing to do.');
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn("DRY RUN — no data will be modified.");
        }

        $this->info("Found {$total} inactive articles to purge.");

        if (!$dryRun && !$this->confirm("This will permanently delete {$total} articles, their gallery records, and image files. Continue?")) {
            $this->info('Aborted.');
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        // chunkById uses the PK (news_id) as cursor, which is stable even
        // when rows are deleted during iteration.
        Article::where('active', '0')
            ->chunkById($chunkSize, function ($articles) use ($dryRun, $bar) {
                foreach ($articles as $article) {
                    $this->processArticle($article, $dryRun);
                    $bar->advance();
                }
            }, 'news_id');

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->table(
            ['Metric', 'Count'],
            [
                ['Articles deleted', $this->deletedArticles],
                ['Gallery rows deleted', $this->deletedGalleryRows],
                ['Directories deleted', $this->deletedDirectories],
                ['Errors', $this->errors],
            ]
        );

        if ($dryRun) {
            $this->warn('DRY RUN complete — no changes were made. Re-run without --dry-run to execute.');
        } else {
            $this->info('Purge complete.');
        }

        Log::info('[PurgeInactiveArticles] Finished', [
            'dry_run' => $dryRun,
            'articles' => $this->deletedArticles,
            'gallery_rows' => $this->deletedGalleryRows,
            'directories' => $this->deletedDirectories,
            'errors' => $this->errors,
        ]);

        return self::SUCCESS;
    }

    private function processArticle(Article $article, bool $dryRun): void
    {
        $newsId = $article->news_id;
        $galleryCount = $article->images()->count();
        $dir = "uploads/news/{$newsId}";
        $disk = Storage::disk('public');
        $dirExists = $disk->exists($dir);

        if ($dryRun) {
            $this->components->twoColumnDetail(
                "Article #{$newsId}",
                "gallery: {$galleryCount} | dir: " . ($dirExists ? 'yes' : 'no')
            );
            $this->deletedArticles++;
            $this->deletedGalleryRows += $galleryCount;
            if ($dirExists) {
                $this->deletedDirectories++;
            }
            return;
        }

        try {
            // 1. Delete physical files first (safest order)
            if ($dirExists) {
                $disk->deleteDirectory($dir);
                $this->deletedDirectories++;
            }

            // 2. Delete gallery (child) rows
            $deleted = $article->images()->delete();
            $this->deletedGalleryRows += $deleted;

            // 3. Delete the article (parent) row
            $article->delete();
            $this->deletedArticles++;

        } catch (\Throwable $e) {
            $this->errors++;
            Log::error('[PurgeInactiveArticles] Failed to purge article', [
                'news_id' => $newsId,
                'error' => $e->getMessage(),
            ]);
            $this->components->error("Failed article #{$newsId}: {$e->getMessage()}");
        }
    }
}
