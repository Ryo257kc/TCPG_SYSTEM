<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 証明書プレビュー</title>
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-ui.css') }}">
    <style>
        body {
            margin: 0;
            background: #f3f6fa;
            color: #1f2937;
            font-family: "Yu Gothic", "Meiryo", sans-serif;
        }

        .proof-toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 14px;
            border-bottom: 1px solid #d7dee8;
            background: rgba(255, 255, 255, 0.96);
        }

        .proof-title {
            min-width: 0;
            font-size: 14px;
            font-weight: 700;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .proof-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .proof-button {
            min-width: 82px;
            border: 1px solid #bcc8d8;
            border-radius: 8px;
            padding: 8px 12px;
            background: #fff;
            color: #263445;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }

        .proof-stage {
            min-height: calc(100vh - 58px);
            display: grid;
            place-items: center;
            padding: 18px;
            overflow: auto;
        }

        .proof-image-wrap {
            display: grid;
            place-items: center;
            min-width: 100%;
            min-height: calc(100vh - 100px);
        }

        .proof-image {
            max-width: 92vw;
            max-height: calc(100vh - 110px);
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.18);
            background: #fff;
            transform-origin: center center;
        }

        .proof-pdf {
            width: 100%;
            height: calc(100vh - 86px);
            border: 1px solid #d7dee8;
            background: #fff;
        }

        .proof-message {
            max-width: 620px;
            border: 1px solid #d7dee8;
            border-radius: 8px;
            padding: 18px;
            background: #fff;
            line-height: 1.7;
        }
    </style>
</head>

<body>
    <header class="proof-toolbar">
        <div class="proof-title">証明書プレビュー{{ $fileName !== '' ? '：'.$fileName : '' }}</div>
        <div class="proof-actions">
            @if ($isImage)
            <button type="button" class="proof-button" data-rotate="-90">左回転</button>
            <button type="button" class="proof-button" data-rotate="90">右回転</button>
            <button type="button" class="proof-button" data-reset>戻す</button>
            @endif
            <a class="proof-button" href="{{ $fileUrl }}" target="_blank" rel="noopener">元を開く</a>
            <button type="button" class="proof-button" onclick="window.close()">閉じる</button>
        </div>
    </header>

    <main class="proof-stage">
        @if ($isImage)
        <div class="proof-image-wrap">
            <img id="proof-image" class="proof-image" src="{{ $fileUrl }}" alt="証明書">
        </div>
        @elseif ($isPdf)
        <iframe class="proof-pdf" src="{{ $fileUrl }}" title="証明書PDF"></iframe>
        @else
        <div class="proof-message">
            この形式は画面内プレビューに対応していません。上の「元を開く」から確認してください。
        </div>
        @endif
    </main>

    @if ($isImage)
    <script>
        (() => {
            const image = document.getElementById('proof-image');
            let angle = 0;

            const applyRotation = () => {
                const normalized = Math.abs(angle % 180);
                image.style.transform = `rotate(${angle}deg)`;
                image.style.maxWidth = normalized === 90 ? 'calc(100vh - 110px)' : '92vw';
                image.style.maxHeight = normalized === 90 ? '92vw' : 'calc(100vh - 110px)';
            };

            document.querySelectorAll('[data-rotate]').forEach((button) => {
                button.addEventListener('click', () => {
                    angle += Number(button.dataset.rotate || 0);
                    applyRotation();
                });
            });

            document.querySelector('[data-reset]')?.addEventListener('click', () => {
                angle = 0;
                applyRotation();
            });
        })();
    </script>
    @endif
</body>

</html>