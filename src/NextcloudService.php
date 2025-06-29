<?php

namespace Sinapsteknologi\NextcloudManager;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Sinapsteknologi\NextcloudManager\Models\NextcloudFile;
use Illuminate\Support\Str;

class NextcloudService
{
    public function uploadAndShare(UploadedFile $file): ?string
    {
        $disk = config('nextcloud.disk', 'nextcloud');
        $folder = config('nextcloud.path', '');
        $fileName = Str::random(16) . '_' . $file->getClientOriginalName();

        $path = null;

        // Coba pakai storeAs() jika file valid
        if ($file->isValid()) {
            Log::debug('Trying storeAs() method for file upload...');
            $path = $file->storeAs($folder, $fileName, $disk);
        } else {
            Log::warning('UploadedFile is not valid. Falling back to put().', [
                'realPath' => $file->getRealPath(),
                'error' => $file->getError(),
            ]);
        }

        // Kalau storeAs gagal, fallback ke put()
        if (!$path || !Storage::disk($disk)->exists($path)) {
            \Log::debug('Fallback to put() method for file upload...');

            $rawContents = file_get_contents($file->getRealPath());
            $fullPath = trim($folder . '/' . $fileName, '/');

            $success = Storage::disk($disk)->put($fullPath, $rawContents);

            $path = $success ? $fullPath : null;
        }

        // Gagal upload sepenuhnya
        if (!$path) {
            Log::error('Failed to upload file to Nextcloud.');
            return null;
        }

        // Lanjut share
        $shareData = $this->createPublicShare($path);

        if (!$shareData || empty($shareData['url'])) {
            Log::error('Failed to create public share.');
            return null;
        }

        // Simpan ke DB
        $record = NextcloudFile::create([
            'name' => $fileName,
            'path' => $path,
            'url' => $shareData['url'],
            'share_id' => $shareData['share_id'] ?? null,
        ]);

        return $record->url;
    }

    public function createPublicShare(string $path): ?array
    {
        $response = \Http::withBasicAuth(
            config('nextcloud.username'),
            config('nextcloud.password')
        )->withHeaders([
            'OCS-APIREQUEST' => 'true',
        ])->asForm()->post(
            rtrim(config('nextcloud.api_base'), '/') . '/apps/files_sharing/api/v1/shares',
            [
                'path' => '/' . ltrim($path, '/'),
                'shareType' => 3,
                'permissions' => 1,
            ]
        );
    
        $json = $response->json();
        \Log::debug('Create public share response', ['res' => $json]);
    
        if (
            $response->successful() &&
            isset($json['ocs']['data']['url'])
        ) {
            return [
                'url' => rtrim($json['ocs']['data']['url'], '/') . '/download/' . basename($path),
                'share_id' => $json['ocs']['data']['id'] ?? null,
            ];
        }
    
        // Fallback: check if share already exists
        return $this->getExistingPublicShare($path);
    }

    protected function getExistingPublicShare(string $path): ?array
    {
        $response = \Http::withBasicAuth(
            config('nextcloud.username'),
            config('nextcloud.password')
        )->withHeaders([
            'OCS-APIREQUEST' => 'true',
        ])->get(
            rtrim(config('nextcloud.api_base'), '/') . '/apps/files_sharing/api/v1/shares',
            [
                'path' => '/' . ltrim($path, '/'),
            ]
        );

        $json = $response->json();
        \Log::debug('Fallback GET existing share', ['res' => $json]);

        if (
            $response->successful() &&
            isset($json['ocs']['data'][0]['url'])
        ) {
            return [
                'url' => rtrim($json['ocs']['data'][0]['url'], '/') . '/download/' . basename($path),
                'share_id' => $json['ocs']['data'][0]['id'] ?? null,
            ];
        }

        return null;
    }


    public function delete(string $path): void
    {
        Storage::disk(config('nextcloud.disk'))->delete($path);
    }

    public function revokePublicShare(string $path, ?string $shareId = null): void
    {
        if ($shareId) {
            Http::withBasicAuth(
                config('nextcloud.username'),
                config('nextcloud.password')
            )->withHeaders(['OCS-APIREQUEST' => 'true'])
             ->delete(config('nextcloud.api_base') . '/apps/files_sharing/api/v1/shares/' . $shareId);
        }
    }
}
