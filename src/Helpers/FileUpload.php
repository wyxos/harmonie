<?php

namespace Wyxos\Harmonie\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUpload
{
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public static function handle(Request $request)
    {
        return (new static($request))->upload();
    }

    public function upload()
    {
        $this->request->validate([
            'file' => 'required|file',
            'filename' => 'required|string',
            'chunkIndex' => 'required',
            'isLastChunk' => 'required'
        ]);

        $chunk = $this->request->file('file');
        $filename = $this->request->input('filename');
        $chunkIndex = $this->request->input('chunkIndex');
        $isLastChunk = filter_var($this->request->input('isLastChunk'), FILTER_VALIDATE_BOOLEAN);

        $tempPath = Storage::path('temp/' . $filename);

        if (!File::isDirectory($tempPath)) {
            File::makeDirectory($tempPath, 0755, true);
        }

        $chunk->storeAs('temp/' . $filename, $filename . '_chunk_' . $chunkIndex);

        if ($isLastChunk) {
            $path = $this->mergeChunks($tempPath, $filename);
        }

        return [
            'path' => $path ?? null,
            'filename' => $filename,
            'complete' => $isLastChunk
        ];
    }

    private function mergeChunks($tempPath, $filename): string
    {
        Storage::makeDirectory('uploads');

        $extension = '.' . pathinfo($filename, PATHINFO_EXTENSION);

        $hash = Str::random(40);

        $randomFilename = $hash . $extension;

        $relativePath = 'uploads/' . $randomFilename;

        $outputPath = Storage::path($relativePath);

        $chunks = File::files($tempPath);

        usort($chunks, function ($a, $b) {
            $aChunkIndex = intval(preg_replace('/[^0-9]/', '', $a->getFilename()));
            $bChunkIndex = intval(preg_replace('/[^0-9]/', '', $b->getFilename()));
            return $aChunkIndex <=> $bChunkIndex;
        });

        $outputFile = fopen($outputPath, 'wb');

        foreach ($chunks as $chunk) {
            $chunkBasename = File::basename($chunk);

            if (preg_match('/^' . preg_quote($filename, '/') . "_chunk_\d+$/", $chunkBasename)) {
                fwrite($outputFile, File::get($chunk));
                File::delete($chunk); // Delete the chunk after merging it
            }
        }

        fclose($outputFile);

        return $relativePath;
    }
}
