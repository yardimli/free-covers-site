@extends('layouts.app')

@php $footerClass = ''; // Default footer class, adjust if padding is needed @endphp

@section('title', 'About Us - Free Kindle Covers')

@section('content')
	@include('partials.about_breadcrumb')
	@include('partials.about_mission')
{{--	@include('partials.about_popular_authors')--}}
	@include('partials.about_testimonial_three')
	{{-- You might want to include the subscribe partial here as well if it's common --}}
	{{-- @include('partials.subscribe') --}}
@endsection
