<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\CatFact;
use Illuminate\Pagination\LengthAwarePaginator;

class CatFactRepository
{
    public function getAllWithPagination(array $options = [], array $relations = []): ?LengthAwarePaginator
    {
        $pageSize = $options['pageSize'] ?? config('pagination.default_size');

        return CatFact::with($relations)->orderBy('id', 'asc')->paginate($pageSize);
    }

    public function getById(int $id, array $relations = []): ?CatFact
    {
        return CatFact::with($relations)->find($id);
    }

    public function store(array $options): ?CatFact
    {
        $catFact = new CatFact;

        return $this->setFieldsAndPersist($catFact, $options);
    }

    public function update(CatFact $catFact, array $options): ?CatFact
    {
        return $this->setFieldsAndPersist($catFact, $options);
    }

    protected function setFieldsAndPersist(CatFact $catFact, array $options): ?CatFact
    {
        $catFact->fact = $options['fact'];

        if ($catFact->save()) {
            return $catFact;
        }

        return null;
    }

    public function destroy(CatFact $catFact): bool
    {
        return $catFact->delete();
    }
}
