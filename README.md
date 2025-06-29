# Nextcloud Manager for Laravel

A Laravel package to upload files to Nextcloud, generate public share links, and clean them programmatically.

## Features

- Upload file to Nextcloud via Laravel storage
- Automatically generate public share link
- Store file info in database
- Revoke public shares
- Artisan command to clean old files

## Installation

```bash
composer require sinapsteknologi/nextcloud-manager
```

## Configuration

```bash
php artisan vendor:publish --tag=nextcloud-config
```

## Usage

```php
use Sinapsteknologi\NextcloudManager\NextcloudService;

$url = app(NextcloudService::class)->uploadAndShare($request->file('file'));
```

## Cleanup

```bash
php artisan nextcloud:clean --days=30
```
