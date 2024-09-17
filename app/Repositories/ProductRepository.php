<?php

namespace App\Repositories;

use App\Http\Resources\ProductResource;
use App\Models\Product;

class ProductRepository
{
    protected $model;

    public function __construct(Product $product)
    {
        $this->model = $product;
    }

    public function all($filters, $sort, $limit)
    {
        $query = $this->model->query();
        foreach ($filters as $filter) {

            if ($filter['operator'] == 'between' && $filter['column'] == 'created_at') {

                if (count(explode('_', $filter['value'])) != 2) {
                    continue;
                }

                $date = explode('_', $filter['value']);

                $fromDate = new \DateTimeImmutable($date[0]);
                $toDate = new \DateTimeImmutable($date[1]);

                $query->whereBetween(
                    $filter['column'],
                    [
                        $fromDate->getTimestamp(),
                        $toDate->getTimestamp()
                    ]
                );
            } else {
                $query->where(
                    $filter['column'],
                    $filter['value'],
                    $filter['operator']
                );
            }
        }

        if (!empty($sort)) {
            $query->orderBy($sort['column'], $sort['value']);
        } else {
            $query->orderBy('id', 'desc');
        }
        $collections = $query->paginate($limit);

        return ProductResource::collection($collections);
    }

    public function find($id)
    {
        return $this->model->find($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update($id, array $data)
    {
        $product = $this->find($id);
        if ($product) {
            $product->update($data);
            return $product;
        }
        return null;
    }

    public function delete($id)
    {
        $product = $this->find($id);
        if ($product) {
            return $product->delete();
        }
        return false;
    }
}
