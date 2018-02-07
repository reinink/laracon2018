@extends('layout', ['title' => 'Customers'])

@section('content')

<h1>Customers <small class="text-muted font-weight-light">({{ number_format($customers->total()) }} found)</small></h1>

<form class="input-group my-4" action="{{ route('customers') }}" method="get">
    <input type="hidden" name="order" value="{{ request('order') }}">
    <input type="text" class="w-50 form-control" placeholder="Search..." name="search" value="{{ request('search') }}">
    <div class="input-group-append">
        <button class="btn btn-primary" type="submit">Search</button>
    </div>
</form>

<table class="table my-4">
    <tr>
        <th><a class="{{ request('order', 'name') === 'name' ? 'text-dark' : '' }}" href="{{ route('customers', ['order' => 'name'] + request()->except('page')) }}">Name</a></th>
        <th><a class="{{ request('order') === 'company' ? 'text-dark' : '' }}" href="{{ route('customers', ['order' => 'company'] + request()->except('page')) }}">Company</a></th>
        <th><a class="{{ request('order') === 'birthday' ? 'text-dark' : '' }}" href="{{ route('customers', ['order' => 'birthday'] + request()->except('page')) }}">Birthday</a></th>
        <th><a class="{{ request('order') === 'last_interaction' ? 'text-dark' : '' }}" href="{{ route('customers', ['order' => 'last_interaction'] + request()->except('page')) }}">Last Interaction</a></th>
    </tr>
    @foreach ($customers as $customer)
        <tr>
            <td><a href="{{ route('customers.edit', $customer) }}">{{ $customer->last_name }}, {{ $customer->first_name }}</a></td>
            <td>{{ $customer->company->name }}</td>
            <td>{{ $customer->birth_date->format('F j') }}</td>
            <td>
                {{ $customer->lastInteraction->created_at->diffForHumans() }}
                <span class="text-secondary">({{ $customer->lastInteraction->type }})</span>
            </td>
        </tr>
    @endforeach
</table>

{{ $customers->appends(request()->all())->links() }}

@endsection
