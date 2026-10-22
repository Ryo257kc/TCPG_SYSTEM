<?php

return [
    // 今回はローカル検証のみ。本番環境では有効にならない。
    'enabled' => in_array(env('APP_ENV'), ['local', 'testing'], true)
        && (bool) env('ADMIN_TWO_FACTOR_ENABLED', false),
    // 専用テーブルのみ接続承認済み。ローカルの明示設定がある場合だけ接続する。
    'database_approved' => in_array(env('APP_ENV'), ['local', 'testing'], true)
        && (bool) env('ADMIN_TWO_FACTOR_DATABASE_APPROVED', false),
    'connection' => 'sqlsrv',
    'table' => 'dbo.admin_two_factor_credentials',
    'issuer' => 'TCPG SYSTEM (Local)',
    'pending_seconds' => 600,
    'max_attempts' => 5,
    'decay_seconds' => 60,
    // 専用テーブルは作成承認済み。ローカルの明示設定がある場合のみ使用する。
    'trusted_devices_approved' => in_array(env('APP_ENV'), ['local', 'testing'], true)
        && (bool) env('ADMIN_TWO_FACTOR_TRUSTED_DEVICES_APPROVED', false),
    'trusted_devices_table' => 'dbo.admin_two_factor_trusted_devices',
    'trusted_cookie' => 'tcpg_admin_2fa_trusted_local',
    'trusted_days' => 30,
];
