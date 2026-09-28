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
        self::delete();

        $extension = strtolower($file->getClientOriginalExtension());
        $file->storeAs(self::DIRECTORY, 'gerente-general.'.$extension, self::DISK);
    }

    public static function delete(): void
    {
        foreach (self::EXTENSIONS as $extension) {
            Storage::disk(self::DISK)->delete(self::DIRECTORY.'/gerente-general.'.$extension);
        }
    }
}
