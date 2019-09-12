<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVideoRequest;
use App\Jobs\ConvertVideo;
use App\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class VideoController extends Controller
{

    /**
     * Return video blade view and pass videos to it.
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $videos = Video::orderBy('created_at', 'DESC')->paginate(5);
        return view('videos', ['videos' => $videos]);
    }

    /**
     * Return uploader form view for uploading videos
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function uploader(){
        return view('uploader');
    }

    public function videoJobs()
    {
        $jobs = array();
        $payloads = DB::table('jobs')->where('queue','video')->pluck('payload');

        foreach($payloads as $payload)
        {
            $jsonpayload = json_decode($payload);

            if(isset($jsonpayload->data->command))
            {
                $jobs[] = unserialize($jsonpayload->data->command);
            }
        }

        return view('videoJobs', ['videoJobs' => $jobs]);
    }

    /**
     * Handles form submission after uploader form submits
     * @param StoreVideoRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreVideoRequest $request)
    {
        $path = str_random(16) . '.' . $request->video->getClientOriginalExtension();
        $request->video->storeAs('public/uploaded', $path);

        $video = Video::create([
            'uid'           => Auth::id(),
            'disk'          => 'uploaded',
            'mediakey'      => $request->video->getClientOriginalName(),
            'path'          => $path,
            'title'         => $request->title,
            'source'        => ['url' => '', 'mediakey' => '', 'created_at' => time()],
            'target'        => [ 'label' => '1080p', 'size' => '1920x1080', 'vbr' => 4000, 'abr' => 256, 'format' => 'mp4', 'created_at' => time()]
        ]);

        ConvertVideo::dispatch($video);

        return redirect('/')
            ->with(
                'message',
                'Your video will be available shortly after we process it'
            );
    }

    public function jobs()
    {
        $payload = DB::table('jobs')->where('queue','video')->value('payload');

        $jsonpayload = json_decode($payload);

        if(isset($jsonpayload->data->command))
        {
            $response = unserialize($jsonpayload->data->command);
            return response()->json($response,200);
        }
        else return response()->json(array('message' => 'not found'),401);
    }

    public function finished(Request $request)
    {
        $data = $request->json()->all();

        $rules = [
            'api_token' => 'required|alpha_num|min:32|max:32',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->passes())
        {
            $videos = collect(DB::table('videos')
                ->where('uid', '=', Auth::guard('api')->user()->id)
                ->where('converted_at','<>', 'NULL')
                ->pluck( 'stream_path'))
                ->map(function ($item, $key) use($request) { return route('getFile', $item); });

            return response()->json($videos->all(),200);
        }

        return response()->json([
            'message' => $validator->errors()->all()
        ])->setStatusCode(400);
    }

    public function getFile($filename)
    {
        $file = storage_path('app/public/converted/'. $filename);
        if(file_exists($file))
        {
            return response()->download($file, null, [], null);
        }

        return response()->json("File not found", 404);

    }

    public function status(Request $request)
    {
        $data = $request->json()->all();
        $rules = [
            'mediakey' => 'required'
        ];

        $video = Video::where('path', $request->json()->get('mediakey'));

        $validator = Validator::make($data, $rules);

        if ($validator->passes())
        {
            $video->update(['processed' => true]);

            return response()->json([
                'message' => 'Media set as processed'
            ])->setStatusCode(200);
        }

        return response()->json([
            'message' => $validator->errors()->all()
        ])->setStatusCode(400);
    }

    public function videos(Request $request)
    {
        $data = $request->json()->all();
        $rules = [
            'api_token' => 'required|min:32|max:32'
        ];

        $video = Video::where('path', $request->json()->get('mediakey'));

        $validator = Validator::make($data, $rules);

        if ($validator->passes())
        {
            $video->update(['processed' => true]);

            return response()->json([
                'message' => 'Media set as processed'
            ])->setStatusCode(200);
        }

        return response()->json([
            'message' => $validator->errors()->all()
        ])->setStatusCode(400);
    }
}
