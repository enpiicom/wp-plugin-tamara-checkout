@php
	try {
		$http_code = $exception->getStatusCode();
	} catch (\Exception $e) {
		$http_code = 500;
	}

	$errors = [
		'401' => __( 'Unauthorized', 'enpii' ),
		'403' => __( 'Forbidden', 'enpii' ),
		'404' => __( 'Not Found', 'enpii' ),
		'419' => __( 'Page Expired', 'enpii' ),
		'429' => __( 'Too Many Requests', 'enpii' ),
		'500' => __( 'Server Error', 'enpii' ),
		'503' => __( 'Service Unavailable', 'enpii' ),
	];
	$error_message = !empty($errors[$http_code]) ? $errors[$http_code] : __('Error');
@endphp

@extends('enpii-base::errors/layout-minimal-error')

@section('title', sprintf(__('WP App Error %s', 'enpii' ), $http_code))
@section('code', $http_code)
@section('message', $error_message)
