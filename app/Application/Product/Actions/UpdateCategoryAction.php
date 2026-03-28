<?php

declare(strict_types=1);

namespace App\Application\Product\Actions;

use App\Domain\Product\Entities\Category;
use App\Domain\Product\Exceptions\CategoryDepthExceededException;
use App\Domain\Product\Exceptions\CategoryNotFoundException;
use App\Domain\Product\Repositories\CategoryRepositoryInterface;

final class UpdateCategoryAction
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categories,
    ) {}

    public function handle(
        int $id,
        string $name,
        string $slug,
        ?int $parentId,
        ?string $description,
        int $sortOrder,
        bool $isActive,
    ): Category {
        $existing = $this->categories->findById($id);

        if ($existing === null) {
            throw new CategoryNotFoundException($id);
        }

        if ($parentId !== null) {
            // Prevent self-reference
            if ($parentId === $id) {
                throw new CategoryDepthExceededException();
            }

            $parent = $this->categories->findById($parentId);

            if ($parent === null) {
                throw new CategoryNotFoundException($parentId);
            }

            // Parent already has a parent — would create depth 3+
            if ($parent->parentId !== null) {
                throw new CategoryDepthExceededException();
            }
        }

        return $this->categories->update($id, $name, $slug, $parentId, $description, $sortOrder, $isActive);
    }
}
