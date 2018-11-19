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
        $separator = '_';

        if(isset($target['default']) && $target['default'] == true)
        {
            $target['label'] = '';
            $separator = '';
        }


        $lowBitrateFormat = (new H264('aac', 'libx264'))
            ->setKiloBitrate($target['vbr'])
            ->setAudioKiloBitrate($target['abr']);

        $converted_name = $this->video->path . '_' . $target['created_at'] . $separator . $target['label'] . '.' . $target['format'];

        // open the uploaded video from the right disk...
        FFMpeg::fromDisk($this->video->disk)
            ->open($this->video->path)

            // add the 'resize' filter...
            ->addFilter(function ($filters) {
                $filters->resize($this->dimension);
            })

            // call the 'export' method...
            ->export()

            // tell the MediaExporter to which disk and in which format we want to export...
            ->toDisk('converted')
            ->inFormat($lowBitrateFormat)

            // call the 'save' method with a filename...
            ->save($converted_name);

        // update the database so we know the convertion is done!
        $this->video->update([
            'converted_at' => Carbon::now(),
            'processed' => true,
            'stream_path' => $converted_name
        ]);

        $ffprobe = FFMpeg\FFProbe::create();

        $source_format = $ffprobe
            ->streams(storage_path('app/public/uploaded/' . $this->video->path)) // extracts streams informations
            ->videos()
            ->first();

        $target_format = $ffprobe
            ->streams(storage_path('app/public/converted/' . $converted_name)) // extracts streams informations
            ->videos()
            ->first();

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
        echo $exception;
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
