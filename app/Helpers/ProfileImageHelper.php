<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

class ProfileImageHelper
{
    public static function store(UploadedFile $file, User $user): string
    {
        $directory = 'images/users/'.$user->id;
        $destinationPath = public_path($directory);

        if (! File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, 0775, true);
        }

        if ($user->image !== null && $user->image !== '') {
            $oldImagePath = public_path($user->image);

            if (File::exists($oldImagePath)) {
                File::delete($oldImagePath);
            }
        }

        $filename = $file->getClientOriginalName();
        $file->move($destinationPath, $filename);

        return $directory.'/'.$filename;
    }
}
