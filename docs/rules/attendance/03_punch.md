# 打刻ルール

## 対象画面

- /staff/attendance/punch
- /staff/attendance/punch-list
- resources/views/staff_portal/attendance_punch/index.blade.php
- resources/views/staff_portal/admin/attendance/punch_list.blade.php

## 役割

打刻は、本人が当日の実働時間を登録する勤怠機能。
事務所メニューから入る場合でも、業務ルールは勤怠側に置く。

## 基本方針

- 打刻は mx_time_cards の当日行を更新する。
- 対象はログイン中のスタッフの当日分だけ。
- 二重打刻は保存前に止める。
- 当日の勤怠データがない場合は保存しない。
- 打刻は変更実績ではなく、実働時刻として扱う。
- 打刻漏れの修正は、月間勤怠の備考や管理側の確認で扱う。

## 打刻種別

- 始業: actual_start
- 退出: actual_leave
- 入出: actual_break_out
- 終業: actual_end

## 打刻一覧

- 打刻一覧は対象日の mx_time_cards を表示する。
- 店舗名は mx_stores を使える場合は表示名にする。
- 表示専用で、ここで勤怠計算をしない。

## 変更前に確認すること

- 勤怠確定済みの打刻を更新する必要があるか。
- 当日行がない場合に新規作成する仕様にするか。
- スタッフ本人と管理者の修正権限を変えるか。
