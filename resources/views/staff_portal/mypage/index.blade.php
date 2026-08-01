<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - MyPage</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/data_table.css') }}">

    <style>
        .mypage-form {
            display: grid;
            gap: 14px;
            padding: 14px;
        }

        .mypage-field {
            display: grid;
            grid-template-columns: 150px minmax(0, 1fr) auto auto auto;
            gap: 8px;
            align-items: center;
        }

        .mypage-field-label {
            color: var(--sub);
            font-weight: 700;
            font-size: 13px;
        }

        .mypage-field-value {
            display: flex;
            align-items: center;
            gap: 8px;
            min-height: 32px;
            padding: 0 4px;
            color: #5f320f;
        }

        .mypage-password-plain {
            display: none;
        }

        .mypage-password-visible .mypage-password-mask {
            display: none;
        }

        .mypage-password-visible .mypage-password-plain {
            display: inline;
        }

        .mypage-edit-toggle {
            display: none;
        }

        .mypage-edit-button {
            width: auto;
            min-width: 64px;
            padding: 7px 10px;
        }

        .mypage-edit-save-button {
            display: none;
        }

        .mypage-field-editor {
            display: none !important;
            grid-column: 2 / -1;
        }

        .mypage-field-editor input {
            width: 100%;
            max-width: 360px;
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 8px 10px;
            background: rgba(255, 255, 255, 0.9);
            color: #5f320f;
        }

        .mypage-field-editor input+input {
            margin-top: 8px;
        }


        .mypage-edit-toggle:checked~.mypage-field-editor {
            display: block !important;
        }

        .mypage-edit-toggle:checked~.mypage-edit-open-button {
            display: none;
        }

        .mypage-edit-toggle:checked~.mypage-edit-save-button {
            display: inline-flex;
        }

        @media (max-width: 768px) {
            .mypage-form {
                overflow-x: visible;
            }

            .mypage-field {
                display: grid;
                grid-template-columns: 1fr;
                gap: 8px;
                align-items: stretch;
            }

            .mypage-field-label,
            .mypage-field-value,
            .mypage-field-editor {
                width: 100%;
                min-width: 0;
            }

            .mypage-edit-button {
                width: 100%;
                text-align: center;
                box-sizing: border-box;
            }

            .mypage-field-editor input {
                width: 100%;
                box-sizing: border-box;
                font-size: 16px;
            }
        }
    </style>

</head>

<body>
    <main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

        <section class="panel content-panel staff-viewport-panel">
            <div class="content-head">
                <h2 class="content-title">MyPage</h2>
            </div>

            @if (session('statusMessage'))
            <div class="status">{{ session('statusMessage') }}</div>
            @endif
            @if (session('errorMessage'))
            <div class="error">{{ session('errorMessage') }}</div>
            @endif

            <p class="notice center">変更する項目の編集ボタンを押して、変更後に保存してください。</p>

            <div class="table-wrap width_edit mypage-form">
                <!-- <div class="mypage-form width_edit"> -->
                <form method="post" action="{{ route('mypage.update') }}" class="mypage-field">
                    @csrf
                    <input class="mypage-edit-toggle" type="checkbox" id="mypage-edit-mail">
                    <span class="mypage-field-label">{{ $fieldLabels['mail'] ?? 'メールアドレス' }}</span>
                    <span class="mypage-field-value">{{ $selectedRow['mail'] ?? '' }}</span>
                    <label class="btn mypage-edit-button mypage-edit-open-button" for="mypage-edit-mail">編集</label>
                    <button type="submit" name="_action" value="save_mail" class="btn mypage-edit-button mypage-edit-save-button">保存</button>
                    <div class="mypage-field-editor">
                        <input type="text" name="mail" value="{{ $selectedRow['mail'] ?? '' }}">
                    </div>
                </form>

                <form method="post" action="{{ route('mypage.update') }}" class="mypage-field">
                    @csrf
                    <input class="mypage-edit-toggle" type="checkbox" id="mypage-edit-password">
                    <span class="mypage-field-label">{{ $fieldLabels['password'] ?? 'パスワード' }}</span>
                    <span class="mypage-field-value" data-password-view>
                        <span class="mypage-password-mask">********</span>
                        <span class="mypage-password-plain">{{ $selectedRow['password'] ?? '' }}</span>
                    </span>
                    <button type="button" class="btn mypage-edit-button" data-password-toggle>表示</button>
                    <label class="btn mypage-edit-button mypage-edit-open-button" for="mypage-edit-password">編集</label>
                    <button type="submit" name="_action" value="save_password" class="btn mypage-edit-button mypage-edit-save-button">保存</button>
                    <div class="mypage-field-editor">
                        <input type="password" name="password" value="{{ $selectedRow['password'] ?? '' }}" autocomplete="new-password" placeholder="新しいパスワード">
                        <input type="password" name="password_confirm" value="{{ $selectedRow['password'] ?? '' }}" autocomplete="new-password" placeholder="確認用">
                    </div>
                </form>
                <!-- </div> -->
            </div>

            <div class="back-row">
                <a href="{{ route('dashboard') }}" class="btn btn_back">戻る</a>
            </div>
        </section>
    </main>
    <script>
        (function() {
            var toggle = document.querySelector('[data-password-toggle]');
            var view = document.querySelector('[data-password-view]');
            if (!toggle || !view) return;

            toggle.addEventListener('click', function() {
                var visible = view.classList.toggle('mypage-password-visible');
                toggle.textContent = visible ? '非表示' : '表示';
            });
        })();
    </script>
</body>

</html>
