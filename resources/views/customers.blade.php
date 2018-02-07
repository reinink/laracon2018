@extends('layout', ['title' => 'Customers'])

@section('content')

<h1>Customers <small class="text-muted font-weight-light">({{ number_format($customers->total()) }} found)</small></h1>

<table class="table my-4">
    <tr>
        <th>Name</th>
        <th>Company</th>
        <th>Birthday</th>
        <th>Last Interaction</th>
    </tr>
    @foreach ($customers as $customer)
        <tr>
            <td><a href="{{ route('customers.edit', $customer) }}">{{ $customer->last_name }}, {{ $customer->first_name }}</a></td>
            <td>{{ $customer->company->name }}</td>
            <td>{{ $customer->birth_date->format('F j') }}</td>
            <td>{{ $customer->interactions->sortByDesc('created_at')->first()->created_at->diffForHumans() }}</td>
        </tr>
    @endforeach
</table>

{{ $customers->appends(request()->all())->links() }}

@endsection
