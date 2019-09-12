<?php

namespace App\Jobs;

use FFMpeg;
use App\Video;
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

class CreateThumbnail implements ShouldQueue
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

        $converted_name = $this->video->path . '_' . $target['created_at'] . '.jpg';

        $converted_path = storage_path('app/public/converted/' . $converted_name);

        //$ffmpeg = FFMpeg\FFMpeg::create()->open($this->video->path)->frame(FMpeg\Coordinate\TimeCode::fromSeconds(42))->save();

        // open the uploaded video from the right disk...
        FFMpeg::fromDisk($this->video->disk)
            ->open($this->video->path)
            ->frame(FFMpeg\Coordinate\TimeCode::fromSeconds(2))
            ->save($converted_path);

        // update the database so we know the convertion is done!
        $this->video->update([
            'converted_at' => Carbon::now(),
            'processed' => true,
            'stream_path' => $converted_name
        ]);

        $guzzle = new Client();

        //TODO replace hardcoded values
        $url = 'http://172.17.0.1/transcoderwebservice/callback';

        $api_token = DB::table('users')->where('id', $this->video->uid)->pluck('api_token')->first();

        $response = $guzzle->post($url, [
            RequestOptions::JSON => [
                'api_token' => $api_token,
                'mediakey' => $this->video->mediakey,
                'thumbnail' => [
                    'url' =>  route('getFile', $converted_name)
                ]
            ],
            'timeout' => 5
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
