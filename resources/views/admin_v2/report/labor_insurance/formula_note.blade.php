<section class="panel table-panel">
    <div class="table-head">
        <div>
            <h2>現在の計算ルール</h2>
            <p>いま画面に表示している数値が、どの条件で集計されているかをそのまま確認できます。</p>
        </div>
    </div>

    <div class="formula-grid">
        <article class="formula-card">
            <h3>人数の集計</h3>
            <ul class="formula-list">
                <li>(1) 常用: 霟用保険加入あり かつ 兼務役員ではない</li>
                <li>(2) 役員: <code>staff_division = 兼務役員</code></li>
                <li>(3) 臨時: 霟用保険人数ではなく、在籍者ベースではない</li>
                <li>(5) 常用: (1) と同じ集計条件</li>
                <li>(6) 役員: 兼務役員 かつ 霟用保険加入あり</li>
                <li>未設定の霟用区分 / 在籍情報は確認対象外</li>
            </ul>
        </article>

        <article class="formula-card">
            <h3>賃金と人数</h3>
            <ul class="formula-list">
                <li>賃金元: <code>mx_kyuyo_shou.rouho_target_sum</code></li>
                <li>左側: 労働保険 / 一般拠出金対象の賃金合計</li>
                <li>右側: 霟用保険対象の賃金合計</li>
                <li>賞与月も賃金集計のみ対象で、人数はカウントしません</li>
                <li>月の合計人数: 表示中の各月人数をそのまま合算</li>
                <li>提出用人数: service 側の合計人数集計</li>
            </ul>
        </article>
    </div>
</section>
