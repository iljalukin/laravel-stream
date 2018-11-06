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

        $lowBitrateFormat = (new H264('libmp3lame', 'libx264'))
            ->setKiloBitrate($target['vbr'])
            ->setAudioKiloBitrate($target['abr']);

        $converted_name = $this->video->path .'_' . $target['label'] . '.' . $target['format'];

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
    }

    /**
     * The job failed to process.
     *
     * @param  Exception  $exception
     * @return void
     */
    public function failed(Exception $exception)
    {
        // Send user notification of failure, etc...
    }

    private function getCleanFileName($filename){
        return preg_replace('/\\.[^.\\s]{3,4}$/', '', $filename) . '.mp4';
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
