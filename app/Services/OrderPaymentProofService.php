<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class OrderPaymentProofService
{
    public const DISK = 'public';

    public const DIRECTORY = 'order_payments';

    /** @return list<string> */
    public static function allowedMimeTypes(): array
    {
        return [
            'image/jpeg',
            'image/png',
            'image/heic',
            'image/heif',
            'application/pdf',
        ];
    }

    public function store(UploadedFile $file): string
    {
        $name = Str::uuid().'.'.$file->getClientOriginalExtension();

        return $file->storeAs(self::DIRECTORY, $name, self::DISK);
    }

    public function url(?string $path): ?string
    {
        if (! $path || ! Storage::disk(self::DISK)->exists($path)) {
            return null;
        }

        return Storage::disk(self::DISK)->url($path);
    }

    public function isPdf(?string $path): bool
    {
        return $path !== null && str_ends_with(strtolower($path), '.pdf');
    }

    public function isImage(?string $path): bool
    {
        if ($path === null || $this->isPdf($path)) {
            return false;
        }

        return (bool) preg_match('/\.(jpe?g|png|heic|heif)$/i', $path);
    }

    public function downloadFilename(int $orderId, ?string $path): string
    {
        $ext = $path ? pathinfo($path, PATHINFO_EXTENSION) : 'bin';

        return 'payment-proof-order-'.$orderId.'.'.$ext;
    }
}
