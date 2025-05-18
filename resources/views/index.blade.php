@extends('layouts.app')

@php
	$footerClass = 'padding_top';
@endphp

@section('title', 'Free Kindle Covers - Your Next Premium Book For Free')

@section('content')
	
	@include('partials.banner')
	
	@include('partials.browse_by_genres')
	
	@include('partials.new_arrivals')
	
	@include('partials.testimonial')
	
	@include('partials.subscribe')

@endsection
