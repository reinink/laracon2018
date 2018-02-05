<?php

namespace App\Http\Controllers;

use App\Customer;
use Illuminate\Http\Request;

class CustomersController extends Controller
{
    public function index(Request $request)
    {
        $customers = Customer::paginate();

        return view('customers', ['customers' => $customers]);
    }

    public function edit(Customer $customer)
    {
        return view('customer', ['customer' => $customer]);
    }
}
