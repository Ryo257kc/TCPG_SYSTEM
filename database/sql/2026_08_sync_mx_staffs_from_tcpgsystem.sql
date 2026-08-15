SET NOCOUNT ON;
GO

/*
目的:
- TCPGSYSTEM.dbo.staff を正として
- TCPGSYSTEM_DEV.dbo.mx_staffs を全件同期する

方針:
- staff_id で突き合わせ、既存行は UPDATE
- 無い行だけ INSERT
- 削除はしない（devにいて本番にいない人はそのまま残す）
- 新DB独自列は触らない（本番にもコード上にも対応が無い、または既にLaravel側が
  正になっているため、同期すると上書きしてしまう）
  - is_admin, mail: Laravel専用（ログイン権限・マイページのメール登録）
  - x_addressee_no, x_submission: 住民税の宛名番号・提出先。元はmx_staffsに
    単一値で持っていたが、mx_resident（年度ごとの履歴）へ移設済み。
    x_submissionは元は本番staff.submissionと同期していたが、参照コードが
    無くなったため同期対象から除外しx_退避した（2026-08-15）
  - is_trial_balance_excluded等は無し（mx_staffsには存在しない）
  - 権限8列（oushin_staff, is_accounting_user, is_payment_check_user,
    is_visit_management_user, is_view_only_user, is_store_management_user,
    is_daily_report_user, front_staff）はStaffV2Service::permissionColumns()経由で
    Laravel側の権限編集画面から直接更新される現役の列。★同期対象に含めると
    Laravel側で設定した権限変更を本番の古い値で上書きしてしまう★
    2026-08-15に一度これを誤って同期してしまい、バックアップから復旧した実績あり。
    今後この列を同期対象に含めてはいけない。

列の対応（本番の日本語列名 → devの英語列名）:
- 表示名 → display_name_ja
- 経理 → is_accounting_user（★同期対象外、上記参照）
- 入金確認 → is_payment_check_user（★同期対象外）
- 往診管理 → is_visit_management_user（★同期対象外）
- 閲覧権限 → is_view_only_user（★同期対象外）
- 店舗管理 → is_store_management_user（★同期対象外）
- 日報 → is_daily_report_user（★同期対象外）
- 振込用途 → transfer_purpose
- 振込先 / 支店 → bank_name_1 / bank_branch_1
- 振込先2 / 支店2 → bank_name_2 / bank_branch_2
- 期間の定め有無 / 期間の定め → has_fixed_term / fixed_term_detail
- 勤務時間1 / 勤務時間2 → work_schedule_1 / work_schedule_2
- 変更履歴 → change_history
- No → 使われなくなった旧採番列。同期対象外（devにaddressee_no等との対応は無い）

注意:
- 読み取り元: TCPGSYSTEM（本番、読み取り専用アカウントで接続すること）
- 書き込み先: TCPGSYSTEM_DEV
- 実行前に必ず dbo.mx_staffs のバックアップを取ること
  （例: SELECT * INTO dbo.mx_staffs_backup_YYYYMMDD FROM dbo.mx_staffs;）
*/

BEGIN TRY
    BEGIN TRANSACTION;

    UPDATE tgt
    SET
        tgt.staff_name = src.staff_name,
        tgt.staff_name_furi = src.staff_name_furi,
        tgt.[password] = src.[password],
        tgt.display_name_ja = src.[表示名],
        tgt.front_staff = src.front_staff,
        tgt.post_num = src.post_num,
        tgt.[address] = src.[address],
        tgt.address_furi = src.address_furi,
        tgt.head_house = src.head_house,
        tgt.relationship = src.relationship,
        tgt.spouse = src.spouse,
        tgt.home_tel = src.home_tel,
        tgt.mobile_tel = src.mobile_tel,
        tgt.staff_division = src.staff_division,
        tgt.section = src.section,
        tgt.birthday = src.birthday,
        tgt.nyu_date = src.nyu_date,
        tgt.tai_date = src.tai_date,
        tgt.employment = src.employment,
        tgt.sex = src.sex,
        tgt.my_number = src.my_number,
        tgt.syaho_seiri_num = src.syaho_seiri_num,
        tgt.syaho_num = src.syaho_num,
        tgt.koyou_num = src.koyou_num,
        tgt.kiso_nenkin_num = src.kiso_nenkin_num,
        tgt.payee = src.payee,
        tgt.transfer_purpose = src.[振込用途],
        tgt.bank_name_1 = src.[振込先],
        tgt.bank_branch_1 = src.[支店],
        tgt.account_type = src.account_type,
        tgt.account_num = src.account_num,
        tgt.bank_name_2 = src.[振込先2],
        tgt.bank_branch_2 = src.[支店2],
        tgt.account_type2 = src.account_type2,
        tgt.account_num2 = src.account_num2,
        tgt.memo = src.memo,
        tgt.koyou = src.koyou,
        tgt.koyou_date = src.koyou_date,
        tgt.syaho = src.syaho,
        tgt.syaho_date = src.syaho_date,
        tgt.yukyu = src.yukyu,
        tgt.yukyu_month = src.yukyu_month,
        tgt.car_km = src.car_km,
        tgt.traffic_day = src.traffic_day,
        tgt.traffic_day_tuika = src.traffic_day_tuika,
        tgt.working_time = src.working_time,
        tgt.tax_amount = src.tax_amount,
        tgt.trial = src.trial,
        tgt.weekly_working_time = src.weekly_working_time,
        tgt.year_working_time = src.year_working_time,
        tgt.percentage_1 = src.percentage_1,
        tgt.percentage_2 = src.percentage_2,
        tgt.business_content = src.business_content,
        tgt.has_fixed_term = src.[期間の定め有無],
        tgt.fixed_term_detail = src.[期間の定め],
        tgt.work_schedule_1 = src.[勤務時間1],
        tgt.work_schedule_2 = src.[勤務時間2],
        tgt.change_history = src.[変更履歴]
    FROM TCPGSYSTEM_DEV.dbo.mx_staffs AS tgt
    INNER JOIN TCPGSYSTEM.dbo.staff AS src
        ON tgt.staff_id = CAST(src.staff_id AS nvarchar(50));
    DECLARE @updated int = @@ROWCOUNT;

    INSERT INTO TCPGSYSTEM_DEV.dbo.mx_staffs (
        staff_id, staff_name, staff_name_furi, [password], display_name_ja,
        front_staff, post_num, [address], address_furi,
        head_house, relationship, spouse, home_tel, mobile_tel, staff_division, section, birthday,
        nyu_date, tai_date, employment, sex, my_number, syaho_seiri_num, syaho_num, koyou_num,
        kiso_nenkin_num, payee, transfer_purpose, bank_name_1, bank_branch_1, account_type, account_num,
        bank_name_2, bank_branch_2, account_type2, account_num2, memo, koyou, koyou_date, syaho,
        syaho_date, yukyu, yukyu_month, car_km, traffic_day, traffic_day_tuika, working_time,
        tax_amount, trial, weekly_working_time, year_working_time,
        percentage_1, percentage_2, business_content, has_fixed_term, fixed_term_detail,
        work_schedule_1, work_schedule_2, change_history
    )
    SELECT
        CAST(src.staff_id AS nvarchar(50)), src.staff_name, src.staff_name_furi, src.[password],
        src.[表示名], src.front_staff, src.post_num, src.[address], src.address_furi,
        src.head_house, src.relationship, src.spouse, src.home_tel, src.mobile_tel, src.staff_division,
        src.section, src.birthday, src.nyu_date, src.tai_date, src.employment, src.sex, src.my_number,
        src.syaho_seiri_num, src.syaho_num, src.koyou_num, src.kiso_nenkin_num, src.payee,
        src.[振込用途], src.[振込先], src.[支店], src.account_type, src.account_num, src.[振込先2],
        src.[支店2], src.account_type2, src.account_num2, src.memo, src.koyou, src.koyou_date,
        src.syaho, src.syaho_date, src.yukyu, src.yukyu_month, src.car_km, src.traffic_day,
        src.traffic_day_tuika, src.working_time, src.tax_amount, src.trial,
        src.weekly_working_time, src.year_working_time, src.percentage_1, src.percentage_2,
        src.business_content, src.[期間の定め有無], src.[期間の定め], src.[勤務時間1], src.[勤務時間2],
        src.[変更履歴]
    FROM TCPGSYSTEM.dbo.staff AS src
    WHERE NOT EXISTS (
        SELECT 1 FROM TCPGSYSTEM_DEV.dbo.mx_staffs AS tgt
        WHERE tgt.staff_id = CAST(src.staff_id AS nvarchar(50))
    );
    DECLARE @added int = @@ROWCOUNT;

    COMMIT TRANSACTION;
    SELECT @updated AS updated_count, @added AS added_count;
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION;
    THROW;
END CATCH;
GO
