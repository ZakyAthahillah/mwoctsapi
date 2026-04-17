<?php

namespace App\Helpers;

use App\Models\Machine;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MachineImageHelper
{
    public static function resolveImageUrls(Request $request, Machine $machine): array
    {
        $payload = [
            'image' => self::resolveSingleImagePath($request, $machine, 'image'),
            'image_side' => self::resolveSingleImagePath($request, $machine, 'image_side'),
        ];

        return array_filter($payload, static fn ($value) => $value !== null);
    }

    public static function resolveSingleImagePath(Request $request, Machine $machine, string $field): ?string
    {
        if ($request->hasFile($field)) {
            /** @var UploadedFile $file */
            $file = $request->file($field);
            $directory = public_path('images/machines/'.$machine->id);

            File::ensureDirectoryExists($directory);

            $extension = strtolower((string) $file->getClientOriginalExtension());
            $safeExtension = $extension !== '' ? $extension : 'bin';
            $fileName = $field.'-'.Str::uuid().'.'.$safeExtension;
            $file->move($directory, $fileName);

            return 'images/machines/'.$machine->id.'/'.$fileName;
        }

        if ($request->exists($field)) {
            $value = $request->input($field);

            if (! is_string($value) || trim($value) === '') {
                return null;
            }

            return self::normalizeImagePath($machine, trim($value));
        }

        return null;
    }

    public static function normalizeImagePath(Machine $machine, string $value): string
    {
        $trimmedValue = trim($value);
        $fileName = basename(str_replace('\\', '/', $trimmedValue));

        return 'images/machines/'.$machine->id.'/'.$fileName;
    }
}
