@extends('layouts.app')

@section('title', 'Free Kindle Civers - Your Next Favorite Book')

@section('content')
	
	@include('partials.banner')
	
	@include('partials.browse_by_genres')
	
	@include('partials.new_arrivals')
	
	@include('partials.testimonial')
	
	@include('partials.subscribe')

@endsection
