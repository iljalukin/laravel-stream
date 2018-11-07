<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVideoRequest;
use App\Jobs\ConvertVideo;
use App\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Validator;

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
            'disk'          => 'uploaded',
            'original_name' => $request->video->getClientOriginalName(),
            'path'          => $path,
            'title'         => $request->title,
            'target'        => ['1080p', 4000, 256, 'mp4']
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
            $videos = DB::table('videos')
                ->where('uid', '=', Auth::guard('api')->user()->id)
                ->where('converted_at','<>', 'NULL')
                ->pluck('title', 'stream_path');

            return response()->json($videos,200);
        }
        else
        {
            return response()->json([
                'message' => $validator->errors()->all()
            ])->setStatusCode(400);
        }


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
        else
        {
            return response()->json([
                'message' => $validator->errors()->all()
            ])->setStatusCode(400);
        }
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
        else
        {
            return response()->json([
                'message' => $validator->errors()->all()
            ])->setStatusCode(400);
        }
    }
}
