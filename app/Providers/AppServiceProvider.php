<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\AbstractPaginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        AbstractPaginator::defaultView('pagination::bootstrap-4');

        Builder::macro('addSubSelect', function ($column, $query) {
            if (is_null($this->getQuery()->columns)) {
                $this->select($this->getQuery()->from.'.*');
            }

            return $this->selectSub($query->limit(1)->getQuery(), $column);
        });

        Builder::macro('orderBySub', function ($query, $direction = 'asc') {
            return $this->orderByRaw("({$query->limit(1)->toSql()}) {$direction}");
        });

        Builder::macro('orderBySubDesc', function ($query) {
            return $this->orderBySub($query, 'desc');
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
