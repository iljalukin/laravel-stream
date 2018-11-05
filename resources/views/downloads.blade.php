@extends('layouts.app')

@section('content')
    <div class="col-xs-12 col-sm-12 col-md-8 col-lg-8 mr-auto ml-auto mt-5">
        <h3 class="text-center">
            Downloads
        </h3>
        {{ $downloads->links() }}
        @foreach($downloads as $download)
            <div class="row mt-5">
                {{ $download->url }}
                @if($download->processed)

                @else
                    <div class="alert alert-info w-100">
                           Video is currently being processed and will be available shortly
                    </div>
                @endif
            </div>
        @endforeach
    </div>
    {{ $downloads->links() }}
@endSection
