<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $casts = [
        'birth_date' => 'date',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function interactions()
    {
        return $this->hasMany(Interaction::class);
    }

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

    public function scopeOrderByName($query)
    {
        $query->orderBy('last_name')->orderBy('first_name');
    }

    public function scopeOrderByCompany($query)
    {
        $query->orderBySub(Company::select('name')->whereRaw('customers.company_id = companies.id'));
    }

    public function scopeOrderByBirthday($query)
    {
        $query->orderbyRaw("to_char(birth_date, 'MMDD')");
    }

    public function scopeOrderByLastInteractionDate($query)
    {
        $query->orderBySubDesc(Interaction::select('created_at')->whereRaw('customers.id = interactions.customer_id')->latest());
    }

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
}
