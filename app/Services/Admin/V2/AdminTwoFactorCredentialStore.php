<?php

namespace App\Services\Admin\V2;

use Illuminate\Support\Facades\DB;

class AdminTwoFactorCredentialStore
{
    private function connection()
    {
        abort_unless(config('admin_two_factor.database_approved'), 503, '二段階認証の保存先はまだ有効化されていません。');

        return DB::connection(config('admin_two_factor.connection'));
    }

    private function query()
    {
        return $this->connection()->table(config('admin_two_factor.table'));
    }

    public function find(string $staffId): ?array
    {
        $row = $this->query()->where('staff_id', $staffId)->first();

        return $row === null ? null : (array) $row;
    }

    public function databaseName(): string
    {
        return (string) $this->connection()->selectOne('SELECT DB_NAME() AS database_name')->database_name;
    }

    /** 登録済みの秘密鍵は初回登録処理で上書きしない。 */
    public function enroll(string $staffId, array $credential, ?string $expectedVersion = null): bool
    {
        return $this->connection()->transaction(function () use ($staffId, $credential, $expectedVersion): bool {
            $query = $this->query()->where('staff_id', $staffId);
            $existing = $query->lockForUpdate()->first();
            if ($existing !== null) {
                if ($existing->secret !== '' || $expectedVersion === null
                    || !hash_equals($existing->auth_version, $expectedVersion)) {
                    return false;
                }
                unset($credential['created_at']);
                $query->update($credential);

                return true;
            }
            if ($expectedVersion !== null) {
                return false;
            }

            $this->query()->insert(['staff_id' => $staffId] + $credential);

            return true;
        });
    }

    /** 行を残して世代を変更するため、認証途中の古いセッションも復活できない。 */
    public function reset(string $staffId, string $expectedVersion): bool
    {
        return $this->consume($staffId, static function (array $row) use ($expectedVersion): ?array {
            if (!hash_equals($row['auth_version'], $expectedVersion)) {
                return null;
            }

            return [
                'secret' => '',
                'recovery_code_hashes' => '[]',
                'last_used_step' => 0,
                'auth_version' => bin2hex(random_bytes(32)),
            ];
        }) !== null;
    }

    /** 検証と消費を同じロック内で行い、TOTP・リカバリーコードの再利用を防ぐ。 */
    public function consume(string $staffId, callable $verify): ?array
    {
        return $this->connection()->transaction(function () use ($staffId, $verify): ?array {
            $query = $this->query()->where('staff_id', $staffId);
            $row = $query->lockForUpdate()->first();
            if ($row === null) {
                return null;
            }

            $changes = $verify((array) $row);
            if ($changes === null) {
                return null;
            }

            $query->update($changes + ['updated_at' => now()]);

            return array_merge((array) $row, $changes);
        });
    }
}
