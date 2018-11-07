<?php

namespace App\Http\Controllers;

use App\Download;
use Validator;
use Illuminate\Validation\Rule;
use App\Jobs\DownloadFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class DownloadController extends Controller
{

    public function index()
    {
        $downloads = Download::orderBy('created_at', 'DESC')->paginate(5);
        return view('downloads', ['downloads' => $downloads]);
    }

    /**
     * Handles form submission after uploader form submits
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $data = $request->json()->all();

        $rules = [
            'api_token'            => 'required|alpha_num|min:32|max:32',
            'source.url'        => 'required|url',
            'source.mediakey'   => ['required','alpha_num', 'min:32', 'max:32'],
            'target.*.label'    => 'required',
            'target.*.size'     => ['required', 'regex:/^(\d+)x(\d+)/'],
            'target.*.vbr'      => 'required|integer',
            'target.*.abr'      => 'required|integer',
            'target.*.format'   => ['required', Rule::in(['mp4','m4v'])]

        ];

        $validator = Validator::make($data, $rules);

        if ($validator->passes())
        {
            $request->offsetUnset('api_token');
            $download = Download::create([
                'uid'       => Auth::guard('api')->user()->id,
                'payload'   => $request->json()->all()
            ]);

            DownloadFile::dispatch($download)->onQueue('download');

            return response()->json([
                'message' => 'File is queued for download'
            ])->setStatusCode(200);
        }
        else
        {
            return response()->json([
                'message' => $validator->errors()->all()
            ])->setStatusCode(400);
        }
    }

    public function downloadJobs()
    {
        $downloadJobs = array();
        $payloads = DB::table('jobs')->where('queue','download')->pluck('payload');

        foreach($payloads as $payload)
        {
            $jsonpayload = json_decode($payload);

            if(isset($jsonpayload->data->command))
            {
                $downloadJobs[] = unserialize($jsonpayload->data->command);
            }
        }

        return view('downloadJobs', ['downloadJobs' => $downloadJobs]);
    }

    public function jobs()
    {
        $payload = DB::table('jobs')->where('queue','download')->value('payload');

        $jsonpayload = json_decode($payload);

        if(isset($jsonpayload->data->command))
        {
            $response = unserialize($jsonpayload->data->command);
            return response()->json($response,200);
        }
        else return response()->json(array('message' => 'not found'),401);
    }
}
