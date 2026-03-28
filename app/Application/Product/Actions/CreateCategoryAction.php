<?php

declare(strict_types=1);

namespace App\Application\Product\Actions;

use App\Domain\Product\Entities\Category;
use App\Domain\Product\Exceptions\CategoryDepthExceededException;
use App\Domain\Product\Exceptions\CategoryNotFoundException;
use App\Domain\Product\Repositories\CategoryRepositoryInterface;

final class CreateCategoryAction
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categories,
    ) {}

    public function handle(
        string $name,
        string $slug,
        ?int $parentId,
        ?string $description,
        int $sortOrder,
        bool $isActive,
    ): Category {
        if ($parentId !== null) {
            $parent = $this->categories->findById($parentId);

            if ($parent === null) {
                throw new CategoryNotFoundException($parentId);
            }

            // Parent already has a parent — would create depth 3+
            if ($parent->parentId !== null) {
                throw new CategoryDepthExceededException();
            }
        }

        return $this->categories->create($name, $slug, $parentId, $description, $sortOrder, $isActive);
    }
}
