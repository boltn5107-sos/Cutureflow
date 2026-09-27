<?php

namespace App\Services;

use App\Enums\ModeleCategorie;
use App\Http\Requests\ModeleRequest;
use App\Models\Modele;
use App\Models\ModelePhoto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ModelePhotoService
{
    public function __construct(private readonly FileStorageService $files) {}

    /**
     * Enregistre les photos d'un modèle. Les fichiers sont privés et servis
     * via un controller autorisé.
     */
    public function store(Modele $modele, array $photos, int $positionDepart = 0): void
    {
        $directory = config('coutureflow.media.model_directory').'/'.$modele->atelier_id;

        foreach (array_values($photos) as $index => $photo) {
            if (! $photo instanceof UploadedFile || ! $photo->isValid()) {
                continue;
            }

            $stored = $this->files->storePrivate($photo, $directory);

            $modele->photos()->create([
                'disk' => $stored['disk'],
                'path' => $stored['path'],
                'original_name' => $stored['original_name'],
                'mime' => $stored['mime'],
                'position' => $positionDepart + $index,
            ]);
        }
    }

    public function delete(ModelePhoto $photo): void
    {
        $photo->deleteFile();
        $photo->delete();
    }

    public function deleteAll(Modele $modele): void
    {
        DB::transaction(function () use ($modele) {
            foreach ($modele->photos as $photo) {
                $photo->deleteFile();
            }

            $modele->photos()->delete();
        });
    }

    public function reorder(Modele $modele, array $ordre): void
    {
        foreach ($ordre as $photoId => $position) {
            ModelePhoto::where('id', $photoId)
                ->whereHas('modele', fn ($q) => $q->where('atelier_id', $modele->atelier_id))
                ->update(['position' => (int) $position]);
        }
    }

    public function stream(ModelePhoto $photo): StreamedResponse
    {
        $disk = Storage::disk($photo->disk);

        abort_unless($disk->exists($photo->path), 404);

        return $disk->response(
            $photo->path,
            basename($photo->path),
            [
                'Cache-Control' => 'private, max-age=604800',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }
}
