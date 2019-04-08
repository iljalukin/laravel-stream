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

class ConvertVideo implements ShouldQueue
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


        $lowBitrateFormat = (new H264('aac', 'h264_vaapi'))
            ->setKiloBitrate($target['vbr'])
            ->setAudioKiloBitrate($target['abr']);

        $lowBitrateFormat->on('progress', function ($video, $format, $percentage)
        {
            if(($percentage % 5) == 0)
            {
                echo "$percentage% transcoded\n";
            }
        });

        $converted_name = $this->video->path . '_' . $target['created_at'] . $separator . $target['label'] . '.' . $target['format'];

        $converted_path = $converted_path = storage_path('app/public/converted/' . $converted_name);

        $ffmpeg = FFMpeg\FFMpeg::create(array(
            'ffmpeg.binaries'  => '/usr/bin/ffmpeg',
            'ffprobe.binaries' => '/usr/bin/ffprobe',
            'timeout'          => 3600, // The timeout for the underlying process
            'ffmpeg.threads'   => 12,   // The number of threads that FFMpeg should use
        ));

        $video = $ffmpeg->open(storage_path('app/public/uploaded/' . $this->video->path));

        $video->filters()->custom('scale_vaapi='.$this->dimension->getWidth() . ':' . $this->dimension->getHeight())->synchronize();

        $video->save($lowBitrateFormat, $converted_path);

        // update the database so we know the conversion is done!
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
                'medium' => [
                    'label' => $target['label'],
                    'url' =>  route('getFile', $converted_name)
                ],
                'properties' => [
                    'source-width' => $source_format->get('width'),
                    'source-height' => $source_format->get('width'),
                    'duration' => $target_format->get('duration'),
                    'filesize' => $target_format->get('filesize'),
                    'width' => $target_format->get('width'),
                    'height' => $target_format->get('height')
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
