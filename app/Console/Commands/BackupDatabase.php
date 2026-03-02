<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class BackupDatabase extends Command
{
    protected $signature = 'db:backup';
    protected $description = 'Backup database to Backblaze B2 (one backup per day of week)';

    public function handle(): int
    {
        if (!env('B2_ACCESS_KEY_ID') || !env('B2_BUCKET')) {
            $this->error('B2 credentials not configured in .env');
            return self::FAILURE;
        }

        $dayOfWeek = strtolower(date('l'));
        $filename = "db-backup-{$dayOfWeek}.sql.gz";

        $dbHost = config('database.connections.mysql.host');
        $dbPort = config('database.connections.mysql.port', 3306);
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');

        $tempFile = storage_path("app/{$filename}");

        $this->info("Starting database backup for {$dayOfWeek}...");
        $this->info("Database: {$dbName}");

        $command = "mysqldump -h" . escapeshellarg($dbHost) .
            " -P" . escapeshellarg($dbPort) .
            " -u" . escapeshellarg($dbUser) .
            " -p" . escapeshellarg($dbPass) .
            " " . escapeshellarg($dbName) .
            " 2>&1 | gzip > " . escapeshellarg($tempFile);

        $result = null;
        $output = [];
        exec($command, $output, $result);

        $minValidSize = 100;
        if (!file_exists($tempFile) || filesize($tempFile) < $minValidSize) {
            $this->error('Database dump failed');
            $this->error(implode("\n", $output));
            Log::error('Database backup failed', ['output' => $output]);
            return self::FAILURE;
        }

        $fileSize = filesize($tempFile);
        $this->info("Database dumped: " . number_format($fileSize / 1024 / 1024, 2) . " MB");

        try {
            $this->info("Uploading to B2 bucket: " . env('B2_BUCKET'));
            $this->info("B2 Endpoint: " . env('B2_ENDPOINT'));

            /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
            $disk = Storage::disk('b2');

            $remotePath = "backups/{$filename}";
            $contents = file_get_contents($tempFile);

            $this->info("File size to upload: " . strlen($contents) . " bytes");

            $result = $disk->put($remotePath, $contents);

            if (!$result) {
                throw new \Exception('Storage::put() returned false - upload failed');
            }

            $this->info("Uploaded to B2: {$remotePath}");

            Log::info("Database backup completed", [
                'file' => $filename,
                'size' => $fileSize,
                'bucket' => env('B2_BUCKET')
            ]);

        } catch (\Exception $e) {
            $this->error('Upload to B2 failed: ' . $e->getMessage());
            Log::error('Database backup upload failed', ['error' => $e->getMessage()]);
            @unlink($tempFile);
            return self::FAILURE;
        }

        @unlink($tempFile);

        Storage::put('last_backup.json', json_encode([
            'timestamp' => time(),
            'day' => $dayOfWeek,
            'size' => $fileSize,
        ]));

        $this->info('Backup completed successfully!');
        return self::SUCCESS;
    }
}
