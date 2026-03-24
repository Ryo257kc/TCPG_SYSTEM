<section class="panel table-panel">
    <div class="table-head">
        <div>
            <h2>現在の集計ルール</h2>
            <p>いま画面に出している数値が、どの条件で集計されているかをそのまま表示しています。</p>
        </div>
    </div>

    <div class="formula-grid">
        <article class="formula-card">
            <h3>区分条件</h3>
            <ul class="formula-list">
                <li>(1) 常用: 雇用保険あり かつ 兼務役員ではない</li>
                <li>(2) 兼務役員: `staff_division = 兼務役員`</li>
                <li>(3) 臨時: 雇用保険なし かつ 業務委託ではない</li>
                <li>(5) 常用: (1) と同じ母集団</li>
                <li>(6) 兼務役員: 兼務役員 かつ 雇用保険あり</li>
                <li>完全な役員 / 業務委託: 集計対象外</li>
            </ul>
        </article>

        <article class="formula-card">
            <h3>金額・人数</h3>
            <ul class="formula-list">
                <li>金額の元: `mx_kyuyo_shou.rouho_target_sum`</li>
                <li>左側: 労災 / 一般拠出金の対象賃金</li>
                <li>右側: 雇用保険対象の賃金</li>
                <li>賞与行: 金額のみ集計、人数はカウントしない</li>
                <li>表の年合計人数: 表示中の通常月人数を単純合算</li>
                <li>転記欄人数: service 側の通常月人数集計</li>
            </ul>
        </article>
    </div>
</section>
