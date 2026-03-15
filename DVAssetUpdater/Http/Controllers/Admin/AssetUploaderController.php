<?php

namespace Modules\DVAssetUpdater\Http\Controllers\Admin;

use App\Contracts\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\DVAssetUpdater\Models\AssetUpload;

class AssetUploaderController extends Controller
{
    public function index(Request $request)
    {
        $targets = config('dvassetupdater.targets', []);
        $hasTable = Schema::hasTable((new AssetUpload())->getTable());

        $uploads = $hasTable
            ? AssetUpload::query()->latest()->limit(25)->get()
            : collect();

        return view('dvassetupdater::admin.index', compact('targets', 'uploads', 'hasTable'));
    }

    public function store(Request $request): RedirectResponse
    {
        $targets = config('dvassetupdater.targets', []);
        $targetKey = (string) $request->input('target');

        if (!array_key_exists($targetKey, $targets)) {
            return back()->withErrors(['target' => 'Invalid upload target selected.'])->withInput();
        }

        $target = $targets[$targetKey];

        $allowedExt = $target['allowed_extensions'] ?? ['jpg','jpeg','png','webp','gif','pdf','zip'];
        $maxKb = (int) ($target['max_size_kb'] ?? 8192);

        $request->validate([
            'target' => ['required', 'string'],
            'file'   => ['required', 'file', 'max:'.$maxKb, 'mimes:'.implode(',', $allowedExt)],
            'filename' => ['nullable', 'string', 'max:128', 'regex:/^[A-Za-z0-9._-]+$/'],
            'overwrite' => ['nullable', 'boolean'],
        ], [
            'file.mimes' => 'This file type is not allowed for the selected target.',
            'file.max'   => 'File is too large for the selected target.',
            'filename.regex' => 'Filename may only include letters, numbers, dots, underscores, and dashes.',
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $ext = strtolower($file->getClientOriginalExtension());

        $base = $target['base'] ?? 'public';
        $relPath = trim((string) ($target['path'] ?? ''), '/');

        // Build destination absolute path
        $destRoot = match ($base) {
            'storage' => storage_path(),
            default   => public_path(),
        };

        $destAbs = $destRoot . DIRECTORY_SEPARATOR . $relPath;

        // Safety: ensure destination stays under the chosen root
        $rootReal = realpath($destRoot) ?: $destRoot;
        File::ensureDirectoryExists($destAbs);
        $destReal = realpath($destAbs) ?: $destAbs;

        if (strpos($destReal, $rootReal) !== 0) {
            return back()->withErrors(['target' => 'Destination path is not allowed.'])->withInput();
        }

        // Determine stored filename
        $desired = (string) $request->input('filename', '');
        if ($desired !== '') {
            // Force extension to match the uploaded file
            $desired = preg_replace('/\.[^.]+$/', '', $desired);
            $baseName = $desired;
        } else {
            $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        }

        // Sanitize base name
        $baseName = trim($baseName);
        $baseName = Str::of($baseName)->replaceMatches('/[^A-Za-z0-9._-]+/', '-')->trim('-')->toString();
        if ($baseName === '') {
            $baseName = 'upload';
        }

        $naming = $target['naming'] ?? 'original'; // original|unique
        $overwrite = (bool) ($request->boolean('overwrite') && (bool) ($target['overwrite'] ?? false));

        $stored = $baseName.'.'.$ext;
        $candidatePath = $destReal.DIRECTORY_SEPARATOR.$stored;

        if (!$overwrite && File::exists($candidatePath)) {
            if ($naming === 'unique') {
                do {
                    $stored = $baseName.'-'.now()->format('Ymd-His').'-'.Str::lower(Str::random(6)).'.'.$ext;
                    $candidatePath = $destReal.DIRECTORY_SEPARATOR.$stored;
                } while (File::exists($candidatePath));
            } else {
                $counter = 1;
                do {
                    $stored = $baseName.'-'.$counter.'.'.$ext;
                    $candidatePath = $destReal.DIRECTORY_SEPARATOR.$stored;
                    $counter++;
                } while (File::exists($candidatePath));
            }
        }

        try {
            $file->move($destReal, $stored);
        } catch (\Throwable $e) {
            return back()->withErrors(['file' => 'Upload failed: '.$e->getMessage()])->withInput();
        }

        $relativeSaved = trim($relPath.'/'.$stored, '/');

        if (Schema::hasTable((new AssetUpload())->getTable())) {
            $upload = new AssetUpload();
            $upload->user_id = Auth::id();
            $upload->target_key = $targetKey;
            $upload->original_name = $originalName;
            $upload->stored_name = $stored;
            $upload->relative_path = $relativeSaved;
            $upload->mime = $file->getClientMimeType() ?: null;
            $upload->size = (int) File::size($destReal.DIRECTORY_SEPARATOR.$stored);
            $upload->sha1 = sha1_file($destReal.DIRECTORY_SEPARATOR.$stored) ?: null;
            $upload->save();
        }

        return redirect()->route('admin.assetuploader.index')
            ->with('success', 'Uploaded successfully to '.$target['label'].' ('.$relativeSaved.').');
    }
}
