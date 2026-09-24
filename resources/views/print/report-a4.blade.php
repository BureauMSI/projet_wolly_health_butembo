@extends('layouts.print')
@section('title', $title)
@section('print_css')
    @include('print._magolio-styles')
    @if(!empty($printLandscape))
        @include('print._tree-styles')
    @endif
    <style>
        @if(!empty($printLandscape))
            body { width: 277mm; padding: 8mm; }
            @page { size: A4 landscape; margin: 10mm; }
        @else
            body { width: 190mm; padding: 10mm; }
            @page { size: A4; margin: 12mm; }
        @endif
    </style>
@endsection
@section('content')
    <div class="rapport-officiel">
        @include('print._letterhead')
        @include('print.reports.'.$type)
        @include('print._signatures')
    </div>
@endsection
