<?php

declare(strict_types=1);

namespace App\Actions\Provider;

use App\Models\ProviderConnection;

class DeleteProviderConnectionAction
{
    public function execute(ProviderConnection $connection): void
    {
        $connection->delete();
    }
}
