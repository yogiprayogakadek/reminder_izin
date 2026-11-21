<?php

namespace App\Http\Controllers;

use App\Models\PermitFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PermitFileController extends Controller
{
    public function __invoke(Request $request, PermitFile $permitFile)
    {
        abort_unless(auth()->check(), 403);

        if (! Storage::disk('local')->exists($permitFile->path)) {
            abort(404, 'File tidak ditemukan.');
        }

        return Storage::disk('local')->download($permitFile->path, $permitFile->original_name);
    }
}
