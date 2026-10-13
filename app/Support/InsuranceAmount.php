<?php

namespace App\Support;

/**
 * 社会保険料の金額計算。計算ロジックは正しいものを1つだけ置き、各サービスはここを呼ぶ
 * （2026-10-13、ユーザー指示の必須ルール。あちこちで個別計算させない）。
 *
 * 集約した経緯: 児童手当拠出金(jidou_office)の丸めが4箇所に複製されており、2026-09-17に
 * 社保納付告知書と突き合わせてfloorへ直した際、表示側2箇所だけ修正され保存側2箇所がceilの
 * まま残った。再計算のたびに1円多い値が上書きされ、手で316に直した行が317へ戻る状態だった
 * （2026-10-13、ユーザー報告で発覚）。丸めルールが散っていたのが原因なので、ここへ集約した。
 */
class InsuranceAmount
{
    /**
     * 会社負担分（および保険料総額）の金額。
     *
     * 丸めはfloor。ceilだと実際の納付額より1円多く出るケースがあり、社保納付告知書の実額
     * （健保＋介護＋子ども支援金＝108,390円、厚生年金＝177,144円）と突き合わせてfloorが
     * 正しいことを確認済み（2026-09-17）。
     *
     * $ratePercentは「%表記」で渡すこと。DBの保存形式が違うものは呼ぶ側で%へ揃える
     * （例: 賞与のjidou_shoyoは%×10で保存されているため、呼ぶ側で÷10してから渡す）。
     *
     * @param float $standard 標準報酬月額・標準賞与額などの基礎額
     * @param float $ratePercent 料率（%表記。0.36 なら 0.36%）
     */
    public static function officeShare(float $standard, float $ratePercent): int
    {
        if ($standard <= 0 || $ratePercent <= 0) {
            return 0;
        }

        return (int) floor($standard * ($ratePercent / 100));
    }

    /**
     * 自己負担分（折半後）の金額。
     *
     * 会社負担分と違い、保険料総額・折半のどちらもceilで切り上げる。給与明細の天引き額が
     * この計算で実績と一致しているため、officeShare()をfloorへ直した際もここは変更していない
     * （2026-09-17）。丸めが違うのは意図的なので、officeShare()と混同しないこと。
     *
     * $ratePercentは「%表記」で渡すこと（officeShare()と同じ）。
     */
    public static function employeeShare(float $standard, float $ratePercent): int
    {
        if ($standard <= 0 || $ratePercent <= 0) {
            return 0;
        }

        $total = (int) ceil($standard * ($ratePercent / 100));

        return (int) ceil($total / 2);
    }
}
