# 年末調整 依存関係

## 主な依存

年末調整
├ YearEndAdjustmentV2Controller
├ mx_nen_tyo（計算結果＋申請管理、2026年8月にstaff_year_end_applicationsを統合）
├ mx_hoken
├ mx_fuyo
├ mx_staffs
├ index.blade.php
└ show.blade.php

## 触る順番

1. ルール確認
2. View
3. Controller
4. DB保存処理
5. 計算処理
6. PDF・添付処理

## 注意

計算処理と申請画面を同時に大きく変えない。
PDF座標や添付保存先の変更は、画面入力や計算ロジックとは分けて確認する。