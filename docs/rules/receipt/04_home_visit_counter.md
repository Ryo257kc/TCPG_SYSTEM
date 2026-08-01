# 往診窓口

## 画面

- /staff/office/receipt/home-visit-counter

## Controller

- App\\Http\\Controllers\\StaffPortal\\office\\HomeVisitCounterController

## ルール

- 往診窓口は mx_insurance_claim_details の insurer_number = 99999999 を扱う。
- 患者名検索は過去分も確認できるようにする。ソートは新しい日付を上にする。
- 保存時に施術月が勝手に変わらないこと。
- 現金回収額の合計を画面で確認できること。
- 月次済みの月は編集、削除不可。
