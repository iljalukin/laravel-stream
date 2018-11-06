<?php

namespace App\Jobs;
use App\Download;
use App\Video;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Illuminate\Http\File;

class DownloadFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $download;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Download $download)
    {
        $this->download = $download;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //TODO: Download queued file



        $payload = $this->download->payload;
        $path = $payload['source']['mediakey'];

        $guzzle = new Client();
        $response = $guzzle->get($payload['source']['url']);
        Storage::disk('uploaded')->put($path, $response->getBody());

        $this->download->update(['processed' => true]);


        $filename = basename($payload['source']['url']);

        foreach($payload['target'] as $target)
        {
            $video = Video::create([
                'disk'          => 'uploaded',
                'original_name' => $filename,
                'path'          => $path,
                'title'         => $filename,
                'target'        => $target
            ]);

            ConvertVideo::dispatch($video)->onQueue('video');
        }

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
    }
}
