<?php

namespace App\Http\Controllers;

use App\User;
use App\Customer;
use Illuminate\Http\Request;

class CustomersController extends Controller
{
    public function index(Request $request)
    {
        $customers = Customer::with('company')
            ->withLastInteraction()
            ->whereFilters($request->only(['search', 'filter']))
            ->orderByField($request->get('order', 'name'))
            ->visibleTo(
                User::where('name', 'Jonathan Reinink')->first()
                // User::where('name', 'Taylor Otwell')->first()
                // User::where('name', 'Ian Landsman')->first()
            )
            ->paginate();

        return view('customers', ['customers' => $customers]);
    }

    public function edit(Customer $customer)
    {
        return view('customer', ['customer' => $customer]);
    }
}
