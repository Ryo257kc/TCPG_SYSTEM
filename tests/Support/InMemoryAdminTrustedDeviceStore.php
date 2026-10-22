<?php

namespace Tests\Support;

use App\Services\Admin\V2\AdminTrustedDeviceStore;

class InMemoryAdminTrustedDeviceStore extends AdminTrustedDeviceStore
{
    public array $devices = [];

    public function create(array $device): void
    {
        $this->devices[$device['id']] = $device;
    }

    public function rotate(string $id, string $staffId, callable $verify): ?array
    {
        $row = $this->devices[$id] ?? null;
        if ($row === null || $row['staff_id'] !== $staffId || ($changes = $verify($row)) === null) {
            return null;
        }

        return $this->devices[$id] = array_merge($row, $changes);
    }

    public function revokeAll(string $staffId): void
    {
        $this->devices = array_filter($this->devices, fn($device) => $device['staff_id'] !== $staffId);
    }
}
