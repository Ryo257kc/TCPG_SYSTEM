<?php

namespace App\Services\Admin\V2;

use Illuminate\Support\Facades\DB;

class AdminTrustedDeviceStore
{
    private function connection()
    {
        abort_unless(config('admin_two_factor.trusted_devices_approved'), 503);

        return DB::connection(config('admin_two_factor.connection'));
    }

    private function query()
    {
        return $this->connection()->table(config('admin_two_factor.trusted_devices_table'));
    }

    public function create(array $device): void
    {
        $this->query()->insert($device);
    }

    public function rotate(string $id, string $staffId, callable $verify): ?array
    {
        return $this->connection()->transaction(function () use ($id, $staffId, $verify): ?array {
            $query = $this->query()->where('id', $id)->where('staff_id', $staffId);
            $row = $query->lockForUpdate()->first();
            if ($row === null || ($changes = $verify((array) $row)) === null) {
                return null;
            }
            $query->update($changes + ['updated_at' => now()]);

            return array_merge((array) $row, $changes);
        });
    }

    public function revokeAll(string $staffId): void
    {
        $this->query()->where('staff_id', $staffId)->delete();
    }
}
