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
}
