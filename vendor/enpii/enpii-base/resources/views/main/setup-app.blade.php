@extends('enpii-base::layouts/simple')

@section('content')
	<h1><?php echo 'Setup WP App'; ?></h1>
	<p>{!! $message !!}</p>
@endsection

@if( ! empty($return_url) )
<script type="text/javascript">
	window.setTimeout(function(){
		window.location.href = '{!! esc_attr($return_url) !!}';
	}, 3000);
</script>
@endif
