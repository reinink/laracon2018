# Laracon Online 2018

## Requirement 1: Sort customers by name (last name, first name)

```php
$customers = Customer::orderBy('last_name')->orderBy('first_name')->paginate();
```

Using scope instead:

```php
$customers = Customer::orderByName()->paginate();
```

```php
public function scopeOrderByName($query)
{
    $query->orderBy('last_name')->orderBy('first_name');
}
```

## Requirement 2: Add company name

```html
<th>Company</th>
    <td>{{ $customer->company->name }}</td>
```

Eager load companies:

```php
$customers = Customer::with('company')->orderByName()->paginate();
```

## Requirement 3: Add last interaction date

Via relationship:

```html
<th>Last Interaction</th>
    <td>{{ $customer->interactions->sortByDesc('created_at')->first()->created_at->diffForHumans() }}</td>
```

```php
$customers = Customer::with('company', 'interactions')->orderByName()->paginate();
```

Via database query:

```php
$customers = Customer::with('company')->orderByName()->paginate();
```

```html
<td>{{ $customer->interactions()->latest()->first()->created_at->diffForHumans() }}</td>
```

Via sub query:

```php
public function scopeWithLastInteractionDate($query)
{
    $subQuery = \DB::table('interactions')
        ->select('created_at')
        ->whereRaw('customer_id = customers.id')
        ->latest()
        ->limit(1);

    return $query->select('customers.*')->selectSub($subQuery, 'last_interaction_date');
}
```

```php
$customers = Customer::with('company')
    ->withLastInteractionDate()
    ->orderByName()
    ->paginate();
```

```html
<td>{{ $customer->last_interaction_date->diffForHumans() }}</td>
```

```php
protected $casts = [
    'birth_date' => 'date',
    'last_interaction_date' => 'datetime',
];
```

Via sub query (improved):

```php
public function scopeWithLastInteractionDate($query)
{
    $query->addSubSelect('last_interaction_date', Interaction::select('created_at')
        ->whereRaw('customer_id = customers.id')
        ->latest()
    );
}
```

```php
use Illuminate\Database\Eloquent\Builder;

Builder::macro('addSubSelect', function ($column, $query) {
    if (is_null($this->getQuery()->columns)) {
        $this->select($this->getQuery()->from.'.*');
    }

    return $this->selectSub($query->limit(1)->getQuery(), $column);
});
```

## Requirement 4: Add last interaction type

```html
<td>
    {{ $customer->last_interaction_date->diffForHumans() }}
    <span class="text-secondary">({{ $customer->last_interaction_type }})</span>
</td>
```

```php
public function scopeWithLastInteractionType($query)
{
    $query->addSubSelect('last_interaction_type', Interaction::select('type')
        ->whereRaw('customer_id = customers.id')
        ->latest()
    );
}
```

```php
$customers = Customer::with('company')
    ->withLastInteractionDate()
    ->withLastInteractionType()
    ->orderByName()
    ->paginate();
```

Sub query relationship approach:

```php
$customers = Customer::with('company')
    ->withLastInteraction()
    ->orderByName()
    ->paginate();
```

```php
public function lastInteraction()
{
    return $this->hasOne(Interaction::class, 'id', 'last_interaction_id');
}

public function scopeWithLastInteraction($query)
{
    $query->addSubSelect('last_interaction_id', Interaction::select('id')
        ->whereRaw('customer_id = customers.id')
        ->latest()
    )->with('lastInteraction');
}
```

```html
<td>
    {{ $customer->lastInteraction->created_at->diffForHumans() }}
    <span class="text-secondary">({{ $customer->lastInteraction->type }})</span>
</td>
```

Remove `last_interaction_date` cast:

```php
protected $casts = [
    'birth_date' => 'date',
];
```

## Requirement 5: Make all columns sortable

```html
<th><a class="{{ request('order', 'name') === 'name' ? 'text-dark' : '' }}" href="{{ route('customers', ['order' => 'name'] + request()->except('page')) }}">Name</a></th>
<th><a class="{{ request('order') === 'company' ? 'text-dark' : '' }}" href="{{ route('customers', ['order' => 'company'] + request()->except('page')) }}">Company</a></th>
<th><a class="{{ request('order') === 'birthday' ? 'text-dark' : '' }}" href="{{ route('customers', ['order' => 'birthday'] + request()->except('page')) }}">Birthday</a></th>
<th><a class="{{ request('order') === 'last_interaction' ? 'text-dark' : '' }}" href="{{ route('customers', ['order' => 'last_interaction'] + request()->except('page')) }}">Last Interaction</a></th>
```

```php
$customers = Customer::with('company')
    ->withLastInteraction()
    ->orderByField($request->get('order', 'name'))
    ->paginate();
```

```php
public function scopeOrderByField($query, $field)
{
    if ($field === 'name') {
        $query->orderByName();
    } elseif ($field === 'company') {
        $query->orderByCompany();
    } elseif ($field === 'birthday') {
        $query->orderByBirthday();
    } elseif ($field === 'last_interaction') {
        $query->orderByLastInteractionDate();
    }
}
```

Company, one approach:

```php
public function scopeOrderByCompany($query)
{
    $query->join('companies', 'companies.id', '=', 'customers.company_id')->orderBy('companies.name');
}
```

Company, another approach:

```php
public function scopeOrderByCompany($query)
{
    $query->orderBySub(Company::select('name')->whereRaw('customers.company_id = companies.id'));
}
```

```php
Builder::macro('orderBySub', function ($query, $direction = 'asc') {
    return $this->orderByRaw("({$query->limit(1)->toSql()}) {$direction}");
});

Builder::macro('orderBySubDesc', function ($query) {
    return $this->orderBySub($query, 'desc');
});
```

Birthday:

```php
public function scopeOrderByBirthday($query)
{
    $query->orderbyRaw("to_char(birth_date, 'MMDD')");
}
```

Last interaction date:

```php
public function scopeOrderByLastInteractionDate($query)
{
    $query->orderBySubDesc(Interaction::select('created_at')->whereRaw('customers.id = interactions.customer_id')->latest());
}
```

## Requirement 6: Add text based search

```html
<form class="input-group my-4" action="{{ route('customers') }}" method="get">
    <input type="hidden" name="order" value="{{ request('order') }}">
    <input type="text" class="w-50 form-control" placeholder="Search..." name="search" value="{{ request('search') }}">
    <div class="input-group-append">
        <button class="btn btn-primary" type="submit">Search</button>
    </div>
</form>
```

```php
$customers = Customer::with('company')
    ->withLastInteraction()
    ->whereSearch($request->get('search'))
    ->orderByField($request->get('order', 'name'))
    ->paginate();
```

```php
public function scopeWhereSearch($query, $search)
{
    foreach (explode(' ', $search) as $term) {
        $query->where(function ($query) use ($term) {
            $query->where('first_name', 'ilike', '%'.$term.'%')
               ->orWhere('last_name', 'ilike', '%'.$term.'%')
               ->orWhereHas('company', function ($query) use ($term) {
                   $query->where('name', 'ilike', '%'.$term.'%');
               });
        });
    }
}
```

## Requirement 7: Add filter for customers with birthday's this week

```html
<select name="filter" class="custom-select">
    <option value="" selected>Filters...</option>
    <option value="birthday_this_week" {{ request('filter') === 'birthday_this_week' ? 'selected' : '' }}>Birthday this week</option>
</select>
```

```php
$customers = Customer::with('company')
    ->withLastInteraction()
    ->whereFilters($request->only(['search', 'filter']))
    ->orderByField($request->get('order', 'name'))
    ->paginate();
```

```php
public function scopeWhereFilters($query, array $filters)
{
    $filters = collect($filters);

    $query->when($filters->get('search'), function ($query, $search) {
        $query->whereSearch($search);
    })->when($filters->get('filter') === 'birthday_this_week', function ($query, $filter) {
        $query->whereBirthdayThisWeek();
    });
}
```

```php
use Illuminate\Support\Carbon;

public function scopeWhereBirthdayThisWeek($query)
{
    $start = Carbon::now()->startOfWeek();
    $end = Carbon::now()->endOfWeek();

    $dates = collect(new \DatePeriod($start, new \DateInterval('P1D'), $end))->map(function ($date) {
        return $date->format('md');
    });

    return $query->whereNotNull('birth_date')->whereIn(\DB::raw("to_char(birth_date, 'MMDD')"), $dates);
}
```

## Requirement 8: Limit results to the current user's access

```php
public function scopeVisibleTo($query, User $user)
{
    if ($user->is_admin) {
        return $query;
    }

    return $query->where('sales_rep_id', $user->id);
}
```

```php
use App\User;

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
```
