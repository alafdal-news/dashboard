<?php

namespace App\Console\Commands;

use App\Models\Article;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OptimizeArticleImages extends Command
{
    protected $signature = 'articles:optimize-images
                            {--dry-run : Preview what would be optimized without making changes}
                            {--chunk=200 : Number of articles to process per batch}
                            {--max-width=1920 : Maximum image width in pixels}
                            {--quality=75 : WebP output quality (1-100)}
                            {--active-only : Only process active articles}
                            {--from-id=0 : Start from this news_id (skip all articles with lower IDs)}
                            {--limit=0 : Maximum number of articles to process (0 = all)}';

    protected $description = 'Resize oversized images and convert JPEG/PNG to WebP across all article directories';

    private int $imagesConverted = 0;
    private int $imagesResized = 0;
    private int $imagesSkipped = 0;
    private int $errors = 0;
    private int $bytesSaved = 0;

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $chunkSize = (int) $this->option('chunk');
        $maxWidth = (int) $this->option('max-width');
        $quality = (int) $this->option('quality');
        $activeOnly = $this->option('active-only');
        $fromId = (int) $this->option('from-id');
        $limit = (int) $this->option('limit');

        // Verify GD supports WebP
        if (!function_exists('imagewebp')) {
            $this->error('PHP GD extension does not support WebP. Please recompile GD with WebP support.');
            return self::FAILURE;
        }

        if ($dryRun) {
            $this->warn("DRY RUN — no files will be modified.");
        }

        $this->info("Settings: max-width={$maxWidth}px, quality={$quality}%, active-only=" . ($activeOnly ? 'yes' : 'no'));
        if ($fromId > 0) {
            $this->info("Resuming from news_id > {$fromId}");
        }
        if ($limit > 0) {
            $this->info("Limiting to {$limit} articles.");
        }

        $query = Article::query();
        if ($activeOnly) {
            $query->where('active', '1');
        }
        if ($fromId > 0) {
            $query->where('news_id', '>', $fromId);
        }

        $total = $query->count();
        $toProcess = ($limit > 0) ? min($limit, $total) : $total;
        $this->info("Processing images for {$toProcess} articles" . ($fromId > 0 ? " (of {$total} remaining)" : '') . '...');

        $bar = $this->output->createProgressBar($toProcess);
        $bar->start();

        $processed = 0;
        $lastId = $fromId;

        $query->chunkById($chunkSize, function ($articles) use ($dryRun, $maxWidth, $quality, $bar, $limit, &$processed, &$lastId) {
            foreach ($articles as $article) {
                if ($limit > 0 && $processed >= $limit) {
                    return false; // Stop chunking
                }
                $this->processArticle($article, $dryRun, $maxWidth, $quality);
                $lastId = $article->news_id;
                $processed++;
                $bar->advance();
            }

            if ($limit > 0 && $processed >= $limit) {
                return false; // Stop chunking
            }
        }, 'news_id');

        $bar->finish();
        $this->newLine(2);

        // Summary
        $savedMB = round($this->bytesSaved / 1024 / 1024, 2);
        $this->table(
            ['Metric', 'Count'],
            [
                ['Images converted to WebP', $this->imagesConverted],
                ['Images resized (>{$maxWidth}px)', $this->imagesResized],
                ['Images skipped (already WebP)', $this->imagesSkipped],
                ['Space saved', "{$savedMB} MB"],
                ['Errors', $this->errors],
                ['Last processed news_id', $lastId],
            ]
        );

        if ($dryRun) {
            $this->warn('DRY RUN complete — no changes were made.');
        } else {
            $this->info('Optimization complete.');
        }

        // Show resume command if there are more articles to process
        if ($limit > 0 && $processed >= $limit) {
            $this->newLine();
            $this->info("To continue from where you left off, run:");
            $this->comment("  php artisan articles:optimize-images --from-id={$lastId} --limit={$limit}");
        }

        Log::info('[OptimizeArticleImages] Finished', [
            'dry_run' => $dryRun,
            'converted' => $this->imagesConverted,
            'resized' => $this->imagesResized,
            'skipped' => $this->imagesSkipped,
            'bytes_saved' => $this->bytesSaved,
            'errors' => $this->errors,
            'last_news_id' => $lastId,
        ]);

        return self::SUCCESS;
    }

    private function processArticle(Article $article, bool $dryRun, int $maxWidth, int $quality): void
    {
        $newsId = $article->news_id;
        $dir = "uploads/news/{$newsId}";
        $disk = Storage::disk('public');

        if (!$disk->exists($dir)) {
            return; // No image directory for this article
        }

        $basePath = $disk->path($dir);

        // --- Process cover image ---
        $rawImage = $article->getAttributes()['image'] ?? null;
        if ($rawImage && !str_contains($rawImage, '/')) {
            $coverResult = $this->processImageFile($basePath, $rawImage, $dryRun, $maxWidth, $quality);

            if ($coverResult && !$dryRun) {
                // Update the article's image column with new WebP filename
                DB::table('news_tbl')
                    ->where('news_id', $newsId)
                    ->update(['image' => $coverResult['filename']]);

                // If a thumbnail exists, convert that too
                $rawThumb = $article->getAttributes()['thumbnail_image'] ?? null;
                if ($rawThumb) {
                    $thumbDir = $basePath . DIRECTORY_SEPARATOR . 'thumb';
                    if (is_dir($thumbDir)) {
                        $thumbResult = $this->processImageFile($thumbDir, $rawThumb, $dryRun, $maxWidth, $quality);
                        if ($thumbResult) {
                            DB::table('news_tbl')
                                ->where('news_id', $newsId)
                                ->update(['thumbnail_image' => $thumbResult['filename']]);
                        }
                    }
                }
            } elseif ($coverResult && $dryRun) {
                // Still process thumbnail in dry-run for accurate counts
                $rawThumb = $article->getAttributes()['thumbnail_image'] ?? null;
                if ($rawThumb) {
                    $thumbDir = $basePath . DIRECTORY_SEPARATOR . 'thumb';
                    if (is_dir($thumbDir)) {
                        $this->processImageFile($thumbDir, $rawThumb, $dryRun, $maxWidth, $quality);
                    }
                }
            }
        }

        // --- Process gallery images ---
        $galleryImages = DB::table('news_gallery')
            ->where('news_id', $newsId)
            ->select('gallery_id', 'image_name', 'thumb_name')
            ->get();

        foreach ($galleryImages as $gi) {
            $rawName = $gi->image_name;

            // Skip if the stored value is a full path (not yet finalized) or empty
            if (!$rawName || str_contains($rawName, '/')) {
                continue;
            }

            $galleryResult = $this->processImageFile($basePath, $rawName, $dryRun, $maxWidth, $quality);

            if ($galleryResult && !$dryRun) {
                $updateData = ['image_name' => $galleryResult['filename']];

                // Convert gallery thumbnail too
                if ($gi->thumb_name) {
                    $thumbDir = $basePath . DIRECTORY_SEPARATOR . 'thumb';
                    if (is_dir($thumbDir)) {
                        $thumbResult = $this->processImageFile($thumbDir, $gi->thumb_name, $dryRun, $maxWidth, $quality);
                        if ($thumbResult) {
                            $updateData['thumb_name'] = $thumbResult['filename'];
                        }
                    }
                }

                DB::table('news_gallery')
                    ->where('gallery_id', $gi->gallery_id)
                    ->update($updateData);
            } elseif ($galleryResult && $dryRun && $gi->thumb_name) {
                $thumbDir = $basePath . DIRECTORY_SEPARATOR . 'thumb';
                if (is_dir($thumbDir)) {
                    $this->processImageFile($thumbDir, $gi->thumb_name, $dryRun, $maxWidth, $quality);
                }
            }
        }
    }

    /**
     * Process a single image file: resize if oversized, convert to WebP.
     *
     * @return array{filename: string}|null  New filename on success, null if skipped/failed.
     */
    private function processImageFile(string $directory, string $filename, bool $dryRun, int $maxWidth, int $quality): ?array
    {
        $fullPath = $directory . DIRECTORY_SEPARATOR . $filename;

        if (!file_exists($fullPath)) {
            return null;
        }

        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // Already WebP — skip
        if ($ext === 'webp') {
            $this->imagesSkipped++;
            return null;
        }

        // Only process JPEG, PNG, GIF
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'], true)) {
            $this->imagesSkipped++;
            return null;
        }

        try {
            $info = @getimagesize($fullPath);
            if (!$info) {
                $this->errors++;
                Log::warning('[OptimizeArticleImages] Cannot read image', ['path' => $fullPath]);
                return null;
            }

            $originalSize = filesize($fullPath);
            $width = $info[0];
            $height = $info[1];
            $mime = $info['mime'];
            $needsResize = $width > $maxWidth;

            $newFilename = pathinfo($filename, PATHINFO_FILENAME) . '.webp';
            $newFullPath = $directory . DIRECTORY_SEPARATOR . $newFilename;

            if ($dryRun) {
                $action = $needsResize ? 'resize+convert' : 'convert';
                $this->components->twoColumnDetail(
                    basename($directory) . "/{$filename}",
                    "{$action} ({$width}x{$height}, " . $this->formatBytes($originalSize) . ")"
                );
                $this->imagesConverted++;
                if ($needsResize) {
                    $this->imagesResized++;
                }
                return ['filename' => $newFilename];
            }

            // Load the image into GD
            $img = match ($mime) {
                'image/jpeg' => @imagecreatefromjpeg($fullPath),
                'image/png'  => @imagecreatefrompng($fullPath),
                'image/gif'  => @imagecreatefromgif($fullPath),
                default      => null,
            };

            if (!$img) {
                $this->errors++;
                Log::warning('[OptimizeArticleImages] GD failed to load image', ['path' => $fullPath, 'mime' => $mime]);
                return null;
            }

            // Resize if needed (maintain aspect ratio)
            if ($needsResize) {
                $newHeight = (int) round($height * ($maxWidth / $width));
                $resized = imagecreatetruecolor($maxWidth, $newHeight);

                // Preserve transparency for PNG sources
                if ($mime === 'image/png') {
                    imagepalettetotruecolor($resized);
                    imagealphablending($resized, false);
                    imagesavealpha($resized, true);
                }

                imagecopyresampled($resized, $img, 0, 0, 0, 0, $maxWidth, $newHeight, $width, $height);
                imagedestroy($img);
                $img = $resized;
                $this->imagesResized++;
            }

            // Encode as WebP
            imagepalettetotruecolor($img);
            imagealphablending($img, true);
            imagesavealpha($img, true);
            imagewebp($img, $newFullPath, $quality);
            imagedestroy($img);

            // Verify the new file was created
            if (!file_exists($newFullPath)) {
                $this->errors++;
                Log::error('[OptimizeArticleImages] WebP file not created', ['path' => $newFullPath]);
                return null;
            }

            $newSize = filesize($newFullPath);
            $this->bytesSaved += max(0, $originalSize - $newSize);
            $this->imagesConverted++;

            // Delete original file (only if new file has a different name)
            if ($fullPath !== $newFullPath && file_exists($fullPath)) {
                @unlink($fullPath);
            }

            return ['filename' => $newFilename];

        } catch (\Throwable $e) {
            $this->errors++;
            Log::error('[OptimizeArticleImages] Failed to process image', [
                'path' => $fullPath,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        return round($bytes / 1024, 1) . ' KB';
    }
}
