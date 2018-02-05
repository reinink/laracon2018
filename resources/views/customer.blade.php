@extends('layout', ['title' => $customer->first_name.' '.$customer->last_name])

@section('content')

<h1>{{ $customer->first_name }} {{ $customer->last_name }}</h1>
<h2 class="text-muted font-weight-light">{{ $customer->company->name }}</h2>

<a href="{{ route('customers') }}" class="mt-4 btn btn-primary">Back to customers</a>

@endsection
