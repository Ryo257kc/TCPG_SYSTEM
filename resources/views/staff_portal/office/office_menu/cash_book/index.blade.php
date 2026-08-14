<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 現金出納帳</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/data_table.css') }}">

</head>

<body>
    <main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

        <section class="panel content-panel staff-viewport-panel">
            <div class="content-head">
                <h2 class="content-title">現金出納帳</h2>
            </div>

            @if (session('errorMessage'))
            <div class="error">{{ session('errorMessage') }}</div>
            @endif
            @if (session('statusMessage'))
            <div class="status">{{ session('statusMessage') }}</div>
            @endif

            <form method="get" class="toolbar">
                <div class="toolbar-group">
                    <label for="cash-book-target-month">対象月</label>
                    <input id="cash-book-target-month" name="target_month" type="month" value="{{ $target_month ?? now()->format('Y-m') }}">

                    <select id="cash-book-vault-name" name="vault_name">
                        <option value="">-会社選択-</option>
                        @foreach (($vaultOptions ?? []) as $vaultOption)
                        <option value="{{ $vaultOption }}" @selected(($vault_name ?? '' )===$vaultOption)>{{ $vaultOption }}</option>
                        @endforeach
                    </select>

                    <button type="submit" class="btn">表示</button>
                </div>

                @if ($cashBookMonthlyClosed ?? false)
                <div class="notice">月次処理済 {{ $cashMonthlyClosingProcessedAt }}</div>
                @else
                @if (($vault_name ?? '') !== '')
                <button type="button" class="btn" data-cash-book-new>新規追加</button>
                @endif
                <button type="submit" class="btn apply" form="cash-book-monthly-close-form" onclick="return confirm('{{ $target_month  }}月分の月次締め処理をします。該当月の編集はできなくなります。');">小口月次確定</button>
                @endif

                @if($is_admin)
                <button type="submit" class="btn master" form="cash-book-monthly-unlock-form" onclick="return confirm('{{ $target_month }}月分の小口月次確定を解除します。該当月の編集ができるようになります。');">小口月次解除</button>
                @endif
            </form>
            <form id="cash-book-monthly-close-form" method="post" action="{{ route('office.office_menu.cash_book.monthly_close') }}">
                @csrf
                <input type="hidden" name="target_month" value="{{ $target_month ?? now()->format('Y-m') }}">
                <input type="hidden" name="vault_name" value="{{ $vault_name ?? '' }}">
            </form>
            <form id="cash-book-monthly-unlock-form" method="post" action="{{ route('office.office_menu.cash_book.monthly_unlock') }}">
                @csrf
                <input type="hidden" name="target_month" value="{{ $target_month ?? now()->format('Y-m') }}">
                <input type="hidden" name="vault_name" value="{{ $vault_name ?? '' }}">
            </form>


            @if (($vault_name ?? '') === '')
            <div class="empty">会社を選んでください。</div>
            @else
            <div class="table-wrap staff-viewport-list-wrap">
                <table class="data-table f_size14">
                    <thead>
                        <tr>
                            <th>発生日</th>
                            <th>管理番号</th>
                            <th>入金</th>
                            <th>摘要</th>
                            <th>出金</th>
                            <th>摘要</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr data-cash-book-new-row hidden>
                            <td>
                                <form id="cash-book-new-save" method="post" action="{{ route('office.office_menu.cash_book.save') }}">
                                    @csrf
                                    <input type="hidden" name="journal_entry_id" value="">
                                    <input type="hidden" name="target_month" value="{{ $target_month ?? now()->format('Y-m') }}">
                                    <input type="hidden" name="vault_name" value="{{ $vault_name ?? '' }}">
                                </form>
                                <input type="date" name="occurred_at" value="{{ ($target_month ?? now()->format('Y-m')) . '-01' }}" form="cash-book-new-save" class="inline-input" style="display:inline-block; width:100%;">
                            </td>
                            <td><input type="text" name="management_number" value="" form="cash-book-new-save" class="inline-input" style="display:inline-block; width:100%;"></td>
                            <td><input type="text" name="deposit_amount" value="" form="cash-book-new-save" class="inline-input text-right" style="display:inline-block; width:100%;"></td>
                            <td><input type="text" name="debit_memo_tag" value="" form="cash-book-new-save" class="inline-input" style="display:inline-block; width:100%;"></td>
                            <td><input type="text" name="withdrawal_amount" value="" form="cash-book-new-save" class="inline-input text-right" style="display:inline-block; width:100%;"></td>
                            <td><input type="text" name="credit_memo_tag" value="" form="cash-book-new-save" class="inline-input" style="display:inline-block; width:100%;"></td>
                            <td>
                                <button type="submit" class="btn_small" form="cash-book-new-save">保存</button>
                                <button type="button" class="btn_small" data-cash-book-new-cancel>取消</button>
                            </td>
                        </tr>
                        @forelse (($cashBookRows ?? []) as $row)
                        <tr data-cash-book-row>
                            <td>{{ $row['occurred_at'] }}</td>
                            <td>{{ $row['management_number'] }}</td>
                            <td class="text-right">{{ $row['deposit_amount'] }}</td>
                            <td>{{ $row['debit_memo_tag'] }}</td>
                            <td class="text-right">{{ $row['withdrawal_amount'] }}</td>
                            <td>{{ $row['credit_memo_tag'] }}</td>
                            <td>
                                @if (!($cashBookMonthlyClosed ?? false))
                                <button type="button" class="btn_small" data-cash-book-edit>編集</button>
                                @endif
                            </td>
                        </tr>
                        @if (!($cashBookMonthlyClosed ?? false))
                        <tr data-cash-book-edit-row hidden>
                            <td>
                                <form id="cash-book-save-{{ $row['journal_entry_id'] }}" method="post" action="{{ route('office.office_menu.cash_book.save') }}">
                                    @csrf
                                    <input type="hidden" name="journal_entry_id" value="{{ $row['journal_entry_id'] }}">
                                    <input type="hidden" name="target_month" value="{{ $target_month ?? now()->format('Y-m') }}">
                                    <input type="hidden" name="vault_name" value="{{ $vault_name ?? '' }}">

                                </form>
                                <input type="date" name="occurred_at" value="{{ $row['occurred_at_raw'] }}" form="cash-book-save-{{ $row['journal_entry_id'] }}" class="inline-input" style="display:inline-block; width:100%;">

                            </td>
                            <td><input type="text" name="management_number" value="{{ $row['management_number'] }}" form="cash-book-save-{{ $row['journal_entry_id'] }}" class="inline-input" style="display:inline-block; width:100%;"></td>
                            <td><input type="text" name="deposit_amount" value="{{ $row['deposit_amount'] }}" form="cash-book-save-{{ $row['journal_entry_id'] }}" class="inline-input text-right" style="display:inline-block; width:100%;"></td>
                            <td><input type="text" name="debit_memo_tag" value="{{ $row['debit_memo_tag'] }}" form="cash-book-save-{{ $row['journal_entry_id'] }}" class="inline-input" style="display:inline-block; width:100%;"></td>
                            <td><input type="text" name="withdrawal_amount" value="{{ $row['withdrawal_amount'] }}" form="cash-book-save-{{ $row['journal_entry_id'] }}" class="inline-input text-right" style="display:inline-block; width:100%;"></td>
                            <td><input type="text" name="credit_memo_tag" value="{{ $row['credit_memo_tag'] }}" form="cash-book-save-{{ $row['journal_entry_id'] }}" class="inline-input" style="display:inline-block; width:100%;"></td>
                            <td>
                                <button type="submit" class="btn_small" form="cash-book-save-{{ $row['journal_entry_id'] }}">保存</button>
                                <button type="button" class="btn_small" data-cash-book-cancel>取消</button>
                                <button type="submit" class="btn_small" form="cash-book-save-{{ $row['journal_entry_id'] }}" formaction="{{ route('office.office_menu.cash_book.delete') }}" data-confirm-message="削除しますか？">削除</button>
                            </td>
                        </tr>
                        @endif
                        @empty
                        <tr>
                            <td colspan="7">対象データがありません。</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="toolbar">
                <div class="toolbar-group">
                    <span>前月残高</span>
                    <strong>{{ $cashBookSummary['previous_balance'] ?? '0' }}</strong>
                </div>
                <div class="toolbar-group">
                    <span>入金</span>
                    <strong>{{ $cashBookSummary['deposit_amount_total'] ?? '0' }}</strong>
                </div>
                <div class="toolbar-group">
                    <span>出金</span>
                    <strong>{{ $cashBookSummary['withdrawal_amount_total'] ?? '0' }}</strong>
                </div>
                <div class="toolbar-group">
                    <span>当月残高</span>
                    <strong>{{ $cashBookSummary['current_balance'] ?? '0' }}</strong>
                </div>
            </div>
            @endif

            <div>
                <a href="{{ route('office.office_menu') }}" class="btn btn_back">戻る</a>
            </div>
        </section>
    </main>

    <script>
        document.querySelectorAll('[data-cash-book-edit]').forEach((button) => {
            button.addEventListener('click', () => {
                const row = button.closest('[data-cash-book-row]');
                const editRow = row?.nextElementSibling;
                if (editRow?.hasAttribute('data-cash-book-edit-row')) {
                    editRow.hidden = false;
                    row.hidden = true;
                }
            });
        });

        document.querySelectorAll('[data-cash-book-cancel]').forEach((button) => {
            button.addEventListener('click', () => {
                const editRow = button.closest('[data-cash-book-edit-row]');
                const row = editRow?.previousElementSibling;
                if (row?.hasAttribute('data-cash-book-row')) {
                    row.hidden = false;
                    editRow.hidden = true;
                }
            });
        });

        document.querySelectorAll('[data-cash-book-new]').forEach((button) => {
            button.addEventListener('click', () => {
                const newRow = document.querySelector('[data-cash-book-new-row]');
                if (newRow) {
                    newRow.hidden = false;
                }
            });
        });

        document.querySelectorAll('[data-cash-book-new-cancel]').forEach((button) => {
            button.addEventListener('click', () => {
                const newRow = button.closest('[data-cash-book-new-row]');
                if (newRow) {
                    newRow.hidden = true;
                }
            });
        });

        document.querySelectorAll('[data-confirm-message]').forEach((button) => {
            button.addEventListener('click', (event) => {
                if (!confirm(button.dataset.confirmMessage || '実行しますか？')) {
                    event.preventDefault();
                }
            });
        });
    </script>
</body>

</html>
