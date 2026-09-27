<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stockage des fichiers sensibles (preuves de paiement, photos d'atelier).
 *
 * Les fichiers sont enregistrés hors de public/ sur un disque privé.
 * L'accès se fait uniquement via un controller qui vérifie les droits.
 * Les noms de fichiers sont générés aléatoirement : le nom d'origine
 * n'est jamais utilisé sur le disque (protection contre le path traversal
 * et la divulgation d'information).
 */
class FileStorageService
{
    /**
     * @return array{disk: string, path: string, original_name: string, mime: string, size: int}
     */
    public function storePrivate(UploadedFile $file, string $directory, ?string $disk = null): array
    {
        $disk ??= config('coutureflow.proof.disk');
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');

        $directory = trim($directory, '/');
        $filename = Str::uuid()->toString().'-'.Str::random(8).'.'.$extension;
        $path = $directory.'/'.$filename;

        Storage::disk($disk)->putFileAs($directory, $file, $filename);

        return [
            'disk' => $disk,
            'path' => $path,
            'original_name' => Str::limit(basename((string) $file->getClientOriginalName()), 120, ''),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
        ];
    }

    public function delete(?string $disk, ?string $path): void
    {
        if (blank($path)) {
            return;
        }

        Storage::disk($disk ?: config('coutureflow.proof.disk'))->delete($path);
    }

    public function exists(string $disk, string $path): bool
    {
        return Storage::disk($disk)->exists($path);
    }
}
