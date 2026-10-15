<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class CompanySignature
{
    private const DISK = 'local';

    private const DIRECTORY = 'firmas';

    private const EXTENSIONS = ['png', 'jpg', 'jpeg'];

    public static function path(): ?string
    {
        foreach (self::EXTENSIONS as $extension) {
            $path = self::DIRECTORY.'/gerente-general.'.$extension;

            if (Storage::disk(self::DISK)->exists($path)) {
                return $path;
            }
        }

        return null;
    }

    public static function exists(): bool
    {
        return self::path() !== null;
    }

    public static function base64(): ?string
    {
        $path = self::path();

        if ($path === null) {
            return null;
        }

        $mime = str_ends_with($path, '.png') ? 'image/png' : 'image/jpeg';

        return 'data:'.$mime.';base64,'.base64_encode(Storage::disk(self::DISK)->get($path));
    }

    public static function store(\Illuminate\Http\UploadedFile $file): void
    {
        $limpia = SignatureCleaner::clean($file->get());

        self::delete();

        Storage::disk(self::DISK)->put(self::DIRECTORY.'/gerente-general.png', $limpia);
    }

    public static function delete(): void
    {
        foreach (self::EXTENSIONS as $extension) {
            Storage::disk(self::DISK)->delete(self::DIRECTORY.'/gerente-general.'.$extension);
        }
    }
}
