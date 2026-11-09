<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\DogFact;
use Illuminate\Pagination\LengthAwarePaginator;

class DogFactRepository
{
    public function getAllWithPagination(array $options = [], array $relations = []): ?LengthAwarePaginator
    {
        $pageSize = $options['pageSize'] ?? config('pagination.default_size');

        return DogFact::with($relations)->paginate($pageSize);
    }

    public function getById(int $id, array $relations = []): ?DogFact
    {
        return DogFact::with($relations)->find($id);
    }

    public function store(array $options): ?DogFact
    {
        $dogFact = new DogFact();

        return $this->setFieldsAndPersist($dogFact, $options);
    }

    public function update(DogFact $dogFact, array $options): ?DogFact
    {
        return $this->setFieldsAndPersist($dogFact, $options);
    }

    protected function setFieldsAndPersist(DogFact $dogFact, array $options): ?DogFact
    {
        $dogFact->fact = $options['fact'];

        if ($dogFact->save()) {
            return $dogFact;
        }

        return null;
    }

    public function destroy(DogFact $dogFact): bool
    {
        return $dogFact->delete();
    }
}
