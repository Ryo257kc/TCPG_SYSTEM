SET NOCOUNT ON;

/*
前提:
- 正本は mx_companies.company_id
- mx_syaho.office_no / mx_rouho.office_no / mx_mayor.office_name に入っている旧値を
  company_id の値へ一括変換する
- 例: 099/100 を 1/2 へ寄せる

使い方:
1. 下の @CompanyKeyMap を実データに合わせて埋める
2. 確認SQLで現在値を見る
3. UPDATE を実行する
*/

DECLARE @CompanyKeyMap TABLE (
    old_value NVARCHAR(50) NOT NULL,
    new_company_id NVARCHAR(50) NOT NULL
);

INSERT INTO @CompanyKeyMap (old_value, new_company_id)
VALUES
    (N'099', N'1'),
    (N'100', N'2');

/* ===== 事前確認 ===== */
SELECT N'mx_syaho' AS table_name, LTRIM(RTRIM(CAST(office_no AS NVARCHAR(50)))) AS current_value, COUNT(*) AS row_count
FROM dbo.mx_syaho
GROUP BY LTRIM(RTRIM(CAST(office_no AS NVARCHAR(50))))
ORDER BY current_value;

SELECT N'mx_rouho' AS table_name, LTRIM(RTRIM(CAST(office_no AS NVARCHAR(50)))) AS current_value, COUNT(*) AS row_count
FROM dbo.mx_rouho
GROUP BY LTRIM(RTRIM(CAST(office_no AS NVARCHAR(50))))
ORDER BY current_value;

SELECT N'mx_mayor' AS table_name, LTRIM(RTRIM(CAST(office_no AS NVARCHAR(50)))) AS current_value, COUNT(*) AS row_count
FROM dbo.mx_mayor
GROUP BY LTRIM(RTRIM(CAST(office_no AS NVARCHAR(50))))
ORDER BY current_value;

/* ===== 一括変換 ===== */
UPDATE t
   SET office_no = m.new_company_id
FROM dbo.mx_syaho t
INNER JOIN @CompanyKeyMap m
    ON LTRIM(RTRIM(CAST(t.office_no AS NVARCHAR(50)))) = m.old_value;

UPDATE t
   SET office_no = m.new_company_id
FROM dbo.mx_rouho t
INNER JOIN @CompanyKeyMap m
    ON LTRIM(RTRIM(CAST(t.office_no AS NVARCHAR(50)))) = m.old_value;

UPDATE t
   SET office_no = m.new_company_id
FROM dbo.mx_mayor t
INNER JOIN @CompanyKeyMap m
    ON LTRIM(RTRIM(CAST(t.office_no AS NVARCHAR(50)))) = m.old_value;

/* ===== 変換後確認 ===== */
SELECT N'mx_syaho' AS table_name, LTRIM(RTRIM(CAST(office_no AS NVARCHAR(50)))) AS current_value, COUNT(*) AS row_count
FROM dbo.mx_syaho
GROUP BY LTRIM(RTRIM(CAST(office_no AS NVARCHAR(50))))
ORDER BY current_value;

SELECT N'mx_rouho' AS table_name, LTRIM(RTRIM(CAST(office_no AS NVARCHAR(50)))) AS current_value, COUNT(*) AS row_count
FROM dbo.mx_rouho
GROUP BY LTRIM(RTRIM(CAST(office_no AS NVARCHAR(50))))
ORDER BY current_value;

SELECT N'mx_mayor' AS table_name, LTRIM(RTRIM(CAST(office_no AS NVARCHAR(50)))) AS current_value, COUNT(*) AS row_count
FROM dbo.mx_mayor
GROUP BY LTRIM(RTRIM(CAST(office_no AS NVARCHAR(50))))
ORDER BY current_value;
