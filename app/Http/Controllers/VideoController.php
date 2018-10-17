<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVideoRequest;
use App\Jobs\ConvertVideoForStreaming;
use App\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        ]);

        ConvertVideoForStreaming::dispatch($video);

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

    public function finished()
    {
        $videos = DB::table('videos')->where('converted_for_streaming_at','<>', 'NULL')->pluck('title', 'path');

        return response()->json($videos,200);
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
}
