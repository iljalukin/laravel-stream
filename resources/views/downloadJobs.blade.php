@extends('layouts.app')

@section('content')
    <div class="col-xs-12 col-sm-12 col-md-8 col-lg-8 mr-auto ml-auto mt-5">
        <h3 class="text-center">
            DownloadJobs
        </h3>

        @foreach($downloadJobs as $downloadJob)
            <div class="row mt-5">
                <div class="video" >
                    {{ $downloadJob->download->attributes['id'] }}  {{ $downloadJob->download->attributes['url'] }}  {{ $downloadJob->download->attributes['processed'] }}
                </div>
            </div>
        @endforeach
    </div>
@endSection
