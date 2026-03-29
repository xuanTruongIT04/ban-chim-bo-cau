<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Order\Repositories\CartRepositoryInterface;
use Illuminate\Console\Command;

final class PruneExpiredCartsCommand extends Command
{
    protected $signature = 'cart:prune-expired';

    protected $description = 'Xoa cac gio hang da het han';

    public function __construct(
        private readonly CartRepositoryInterface $carts,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $count = $this->carts->deleteExpired();

        $this->info("Da xoa {$count} gio hang het han.");

        return self::SUCCESS;
    }
}
