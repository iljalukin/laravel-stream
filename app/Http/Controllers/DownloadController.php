<?php

namespace App\Http\Controllers;

use App\Download;
use App\Jobs\DownloadFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DownloadController extends Controller
{

    /**
     * Handles form submission after uploader form submits
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $download = Download::create([
            'url' => $request->json()->get('url'),
        ]);

        DownloadFile::dispatch($download)->onQueue('download');

        return response()->json([
            'message' => 'File is queued for download',
            'code' => '200'
        ]);
    }

    public function jobs()
    {
        return DB::table('jobs')->where('queue','download')->count();
    }
}
