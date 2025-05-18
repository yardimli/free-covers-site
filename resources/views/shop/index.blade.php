@extends('layouts.app')

@section('title', 'Browse Covers - Free Kindle Covers')

@php
	$footerClass = '';
@endphp

@push('styles')
@endpush

@section('content')
	
	@include('partials.shop_breadcrumb')
	@include('partials.shop_area')

@endsection
