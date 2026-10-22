<?php

namespace Tests\Support;

use App\Services\Admin\V2\AdminTwoFactorCredentialStore;

/** テスト専用。SQL Server・既存DBには接続しない。 */
class InMemoryAdminTwoFactorCredentialStore extends AdminTwoFactorCredentialStore
{
    public array $credentials = [];

    public function databaseName(): string
    {
        return 'IN_MEMORY_TEST_ONLY';
    }

    public function find(string $staffId): ?array
    {
        return $this->credentials[$staffId] ?? null;
    }

    public function enroll(string $staffId, array $credential, ?string $expectedVersion = null): bool
    {
        if (isset($this->credentials[$staffId])) {
            $existing = $this->credentials[$staffId];
            if ($existing['secret'] !== '' || $expectedVersion === null || !hash_equals($existing['auth_version'], $expectedVersion)) {
                return false;
            }
            $credential['created_at'] = $existing['created_at'];
        } elseif ($expectedVersion !== null) {
            return false;
        }
        $this->credentials[$staffId] = ['staff_id' => $staffId] + $credential;

        return true;
    }

    public function consume(string $staffId, callable $verify): ?array
    {
        $row = $this->find($staffId);
        if ($row === null || ($changes = $verify($row)) === null) {
            return null;
        }

        return $this->credentials[$staffId] = array_merge($row, $changes);
    }
}
