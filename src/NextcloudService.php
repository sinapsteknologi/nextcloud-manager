<?php

namespace Sinapsteknologi\NextcloudManager;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Sinapsteknologi\NextcloudManager\Models\NextcloudFile;

class NextcloudService
{
    public function uploadAndShare(\Illuminate\Http\UploadedFile $file): ?string
    {
        $fileName = uniqid() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs('', $fileName, config('nextcloud.disk'));

        $shareData = $this->createPublicShare($path);

        return NextcloudFile::create([
            'name' => $fileName,
            'path' => $path,
            'url' => $shareData['url'] ?? null,
            'share_id' => $shareData['share_id'] ?? null,
        ])->url;
    }

    protected function createPublicShare(string $path): ?array
    {
        $res = Http::withBasicAuth(
            config('nextcloud.username'),
            config('nextcloud.password')
        )->withHeaders(['OCS-APIREQUEST' => 'true'])
         ->asForm()->post(config('nextcloud.api_base') . '/apps/files_sharing/api/v1/shares', [
            'path' => '/' . ltrim($path, '/'),
            'shareType' => 3,
        ]);

        if ($res->successful() && isset($res['ocs']['data']['url'])) {
            return [
                'url' => $res['ocs']['data']['url'],
                'share_id' => $res['ocs']['data']['id'] ?? null,
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
