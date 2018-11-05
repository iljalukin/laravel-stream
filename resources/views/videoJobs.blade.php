@extends('layouts.app')

@section('content')
    <div class="col-xs-12 col-sm-12 col-md-8 col-lg-8 mr-auto ml-auto mt-5">
        <h3 class="text-center">
            VideoJobs
        </h3>

        @foreach($videoJobs as $videoJob)
            <div class="row mt-5">

                {{ $videoJob->video->attributes['id'] }}  {{ $videoJob->video->attributes['title'] }}  {{ $videoJob->video->attributes['path'] }}  {{ $videoJob->video->attributes['processed'] }}  {{ $videoJob->video->attributes['created_at'] }}

            </div>
        @endforeach
    </div>
@endSection
