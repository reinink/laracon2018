<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $casts = [
        'birth_date' => 'date',
        'last_interaction_date' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function interactions()
    {
        return $this->hasMany(Interaction::class);
    }

    public function scopeWithLastInteractionDate($query)
    {
        $query->addSubSelect('last_interaction_date', Interaction::select('created_at')
            ->whereRaw('customer_id = customers.id')
            ->latest()
        );
    }

    public function scopeOrderByName($query)
    {
        $query->orderBy('last_name')->orderBy('first_name');
    }
}
