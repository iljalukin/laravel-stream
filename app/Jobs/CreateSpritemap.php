<?php

namespace App\Jobs;

use FFMpeg;
use App\Models\Video;
use App\Format\Video\H264;
use Carbon\Carbon;
use FFMpeg\Coordinate\Dimension;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateSpritemap implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $video;

    private $dimension;
    /**
     * Create a new job instance.
     *
     * @param Video $video
     */
    public function __construct(Video $video)
    {
        $this->video = $video;
        $target = $this->video->target;

        $size = explode('x', $target['size']);
        $this->dimension = new Dimension($size[0], $size[1]);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // create a video format...
        $target = $this->video->target;

        $converted_name = $this->video->path . '_' . $target['created_at'] . '_sprites.jpg';

        $converted_path = storage_path('app/public/converted/' . $converted_name);

        //TODO replace this with php-ffmpeg
        shell_exec('ffmpeg -nostats -loglevel 0 -i ' . storage_path('app/public/uploaded/' . $this->video->path). ' -y -vf "scale=142:80,fps=1,tile=10x10:margin=2:padding=2" ' . $converted_path);


       $this->video->update([
            'converted_at' => Carbon::now(),
            'processed' => true,
            'stream_path' => $converted_name
        ]);

        $guzzle = new Client();

        //TODO replace hardcoded values
        $url = 'http://localhost/transcoderwebservice/callback';

        $api_token = DB::table('users')->where('id', $this->video->uid)->pluck('api_token')->first();

        $response = $guzzle->post($url, [
            RequestOptions::JSON => [
                'api_token' => $api_token,
                'mediakey' => $this->video->mediakey,
                'spritemap' => [
                    'count' => 100,
                    'url' =>  route('getFile', $converted_name)
                ]
            ]
        ]);
    }

    /**
     * The job failed to process.
     *
     * @param  Exception  $exception
     * @return void
     */
    public function failed($exception)
    {
        // Send user notification of failure, etc...
        dd($exception);
    }

    /**
     * Return all the jobs.
     *
     * @return array
     */
    public function jobs()
    {
        return $this->onQueue();
    }
}
