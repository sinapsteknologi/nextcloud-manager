<?php

namespace Sinapsteknologi\NextcloudManager\Commands;

use Illuminate\Console\Command;
use Sinapsteknologi\NextcloudManager\Models\NextcloudFile;
use Sinapsteknologi\NextcloudManager\NextcloudService;

class CleanNextcloudFiles extends Command
{
    protected $signature = 'nextcloud:clean {--days=30}';
    protected $description = 'Delete Nextcloud files older than X days and revoke their share links';

    public function handle(NextcloudService $nextcloud)
    {
        $days = (int) $this->option('days');
        $files = NextcloudFile::where('created_at', '<=', now()->subDays($days))->get();

        if ($files->isEmpty()) {
            $this->info('No files found to clean.');
            return;
        }

        $this->info("Found {$files->count()} file(s) to delete...");

        foreach ($files as $file) {
            $nextcloud->revokePublicShare($file->path, $file->share_id);
            $nextcloud->delete($file->path);
            $file->delete();
            $this->line("Deleted: {$file->path}");
        }

        $this->info("Clean-up done.");
    }
}
