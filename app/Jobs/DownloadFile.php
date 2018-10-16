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

        $path = str_random(16) . '.' . "mp4";

        $guzzle = new Client();
        $response = $guzzle->get($this->download->url);
        Storage::disk('uploaded')->put($path, $response->getBody());

        $this->download->update(['processed' => true]);

        $video = Video::create([
            'disk'          => 'uploaded',
            'original_name' => basename($this->download->url),
            'path'          => $path,
            'title'         => basename($this->download->url),
        ]);

        ConvertVideoForStreaming::dispatch($video)->onQueue('video');
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
}
