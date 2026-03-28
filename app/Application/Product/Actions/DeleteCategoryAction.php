<?php

declare(strict_types=1);

namespace App\Application\Product\Actions;

use App\Domain\Product\Exceptions\CategoryNotFoundException;
use App\Domain\Product\Repositories\CategoryRepositoryInterface;

final class DeleteCategoryAction
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categories,
    ) {}

    public function handle(int $id): void
    {
        $category = $this->categories->findById($id);

        if ($category === null) {
            throw new CategoryNotFoundException($id);
        }

        if ($this->categories->hasProducts($id)) {
            throw new \DomainException('Không thể xóa danh mục đang có sản phẩm.');
        }

        if ($this->categories->hasChildren($id)) {
            throw new \DomainException('Không thể xóa danh mục đang có danh mục con.');
        }

        $this->categories->delete($id);
    }
}
