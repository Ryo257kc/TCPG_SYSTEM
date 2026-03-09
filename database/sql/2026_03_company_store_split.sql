/*
  Company/Store normalization from dbo.T_豕穂ｺｺ蠎苓・
  - Non-destructive: keeps original table as-is.
  - Creates dbo.M_莨夂､ｾ and dbo.M_蠎苓・ if not exists.
  - Upserts data from T_豕穂ｺｺ蠎苓・.
*/

SET NOCOUNT ON;

BEGIN TRY
    BEGIN TRAN;

    IF OBJECT_ID(N'dbo.M_莨夂､ｾ', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.M_莨夂､ｾ (
            company_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            company_name NVARCHAR(120) NOT NULL,
            company_address NVARCHAR(255) NULL,
            ceo_title NVARCHAR(100) NULL,
            ceo_name_kana NVARCHAR(120) NULL,
            ceo_name NVARCHAR(120) NULL,
            tel NVARCHAR(50) NULL,
            fax NVARCHAR(50) NULL,
            office_number NVARCHAR(50) NULL,
            health_office_code NVARCHAR(50) NULL,
            pension_office_code NVARCHAR(50) NULL,
            corporate_number NVARCHAR(50) NULL,
            insurer_number NVARCHAR(50) NULL,
            insurer_name NVARCHAR(120) NULL,
            insurer_address NVARCHAR(255) NULL,
            employment_office_number NVARCHAR(50) NULL,
            labor_insurance_number NVARCHAR(50) NULL,
            labor_install_category NVARCHAR(100) NULL,
            labor_install_date DATE NULL,
            labor_office_category NVARCHAR(100) NULL,
            labor_industry_category NVARCHAR(100) NULL,
            founded_date DATE NULL,
            created_at DATETIME2 NOT NULL CONSTRAINT DF_M_莨夂､ｾ_created_at DEFAULT SYSDATETIME(),
            updated_at DATETIME2 NOT NULL CONSTRAINT DF_M_莨夂､ｾ_updated_at DEFAULT SYSDATETIME()
        );

        CREATE UNIQUE INDEX UX_M_莨夂､ｾ_Natural
            ON dbo.M_莨夂､ｾ (company_name, ISNULL(company_address, N''), ISNULL(office_number, N''), ISNULL(health_office_code, N''));
    END;

    IF OBJECT_ID(N'dbo.M_蠎苓・', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.M_蠎苓・ (
            store_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            company_id INT NOT NULL,
            store_code NVARCHAR(20) NOT NULL,
            store_name NVARCHAR(150) NOT NULL,
            business_type NVARCHAR(100) NULL,
            visit_area NVARCHAR(100) NULL,
            store_short_name NVARCHAR(100) NULL,
            postal_code NVARCHAR(20) NULL,
            address_kana NVARCHAR(255) NULL,
            store_address NVARCHAR(255) NULL,
            tel NVARCHAR(50) NULL,
            fax NVARCHAR(50) NULL,
            is_closed BIT NOT NULL CONSTRAINT DF_M_蠎苓・_is_closed DEFAULT 0,
            legacy_no INT NULL,
            created_at DATETIME2 NOT NULL CONSTRAINT DF_M_蠎苓・_created_at DEFAULT SYSDATETIME(),
            updated_at DATETIME2 NOT NULL CONSTRAINT DF_M_蠎苓・_updated_at DEFAULT SYSDATETIME(),
            CONSTRAINT FK_M_蠎苓・_M_莨夂､ｾ FOREIGN KEY (company_id) REFERENCES dbo.M_莨夂､ｾ(company_id)
        );

        CREATE UNIQUE INDEX UX_M_蠎苓・_store_code ON dbo.M_蠎苓・ (store_code);
    END;

    ;WITH src AS (
        SELECT
            LTRIM(RTRIM(ISNULL([遉ｾ蜷江, N''))) AS company_name,
            NULLIF(LTRIM(RTRIM(ISNULL([莨夂､ｾ菴乗園], N''))), N'') AS company_address,
            NULLIF(LTRIM(RTRIM(ISNULL([蠖ｹ閨ｷ蜷江, N''))), N'') AS ceo_title,
            NULLIF(LTRIM(RTRIM(ISNULL([莉｣陦ｨ閠・錐縺ｵ繧翫′縺ｪ], N''))), N'') AS ceo_name_kana,
            NULLIF(LTRIM(RTRIM(ISNULL([莉｣陦ｨ閠・錐], N''))), N'') AS ceo_name,
            NULLIF(LTRIM(RTRIM(ISNULL([髮ｻ隧ｱ逡ｪ蜿ｷ], N''))), N'') AS tel,
            NULLIF(LTRIM(RTRIM(ISNULL([FAX], N''))), N'') AS fax,
            NULLIF(LTRIM(RTRIM(ISNULL([莠区･ｭ謇逡ｪ蜿ｷ], N''))), N'') AS office_number,
            NULLIF(LTRIM(RTRIM(ISNULL([莠区･ｭ謇謨ｴ逅・分蜿ｷ蛛･菫拆, N''))), N'') AS health_office_code,
            NULLIF(LTRIM(RTRIM(ISNULL([莠区･ｭ謇謨ｴ逅・分蜿ｷ蜴壼ｹｴ], N''))), N'') AS pension_office_code,
            NULLIF(LTRIM(RTRIM(ISNULL([豕穂ｺｺ繝槭う繝翫Φ繝舌・], N''))), N'') AS corporate_number,
            NULLIF(LTRIM(RTRIM(ISNULL([菫晞匱閠・分蜿ｷ], N''))), N'') AS insurer_number,
            NULLIF(LTRIM(RTRIM(ISNULL([菫晞匱閠・錐遘ｰ], N''))), N'') AS insurer_name,
            NULLIF(LTRIM(RTRIM(ISNULL([菫晞匱閠・園蝨ｨ蝨ｰ], N''))), N'') AS insurer_address,
            NULLIF(LTRIM(RTRIM(ISNULL([髮・畑菫晞匱莠区･ｭ謇逡ｪ蜿ｷ], N''))), N'') AS employment_office_number,
            NULLIF(LTRIM(RTRIM(ISNULL([蜉ｴ蜒堺ｿ晞匱逡ｪ蜿ｷ], N''))), N'') AS labor_insurance_number,
            NULLIF(LTRIM(RTRIM(ISNULL([蜉ｴ蜒崎ｨｭ鄂ｮ蛹ｺ蛻・, N''))), N'') AS labor_install_category,
            TRY_CONVERT(DATE, [蜉ｴ蜒崎ｨｭ鄂ｮ蟷ｴ譛域律]) AS labor_install_date,
            NULLIF(LTRIM(RTRIM(ISNULL([蜉ｴ蜒堺ｺ区･ｭ謇蛹ｺ蛻・, N''))), N'') AS labor_office_category,
            NULLIF(LTRIM(RTRIM(ISNULL([蜉ｴ蜒咲肇讌ｭ蛻・｡枉, N''))), N'') AS labor_industry_category,
            TRY_CONVERT(DATE, [險ｭ遶区律]) AS founded_date
        FROM dbo.[T_豕穂ｺｺ蠎苓・]
        WHERE LTRIM(RTRIM(ISNULL([遉ｾ蜷江, N''))) <> N''
    ), dedup AS (
        SELECT DISTINCT * FROM src
    )
    MERGE dbo.M_莨夂､ｾ AS tgt
    USING dedup AS s
      ON tgt.company_name = s.company_name
     AND ISNULL(tgt.company_address, N'') = ISNULL(s.company_address, N'')
     AND ISNULL(tgt.office_number, N'') = ISNULL(s.office_number, N'')
     AND ISNULL(tgt.health_office_code, N'') = ISNULL(s.health_office_code, N'')
    WHEN MATCHED THEN
      UPDATE SET
        ceo_title = s.ceo_title,
        ceo_name_kana = s.ceo_name_kana,
        ceo_name = s.ceo_name,
        tel = s.tel,
        fax = s.fax,
        pension_office_code = s.pension_office_code,
        corporate_number = s.corporate_number,
        insurer_number = s.insurer_number,
        insurer_name = s.insurer_name,
        insurer_address = s.insurer_address,
        employment_office_number = s.employment_office_number,
        labor_insurance_number = s.labor_insurance_number,
        labor_install_category = s.labor_install_category,
        labor_install_date = s.labor_install_date,
        labor_office_category = s.labor_office_category,
        labor_industry_category = s.labor_industry_category,
        founded_date = s.founded_date,
        updated_at = SYSDATETIME()
    WHEN NOT MATCHED THEN
      INSERT (
        company_name, company_address, ceo_title, ceo_name_kana, ceo_name,
        tel, fax, office_number, health_office_code, pension_office_code,
        corporate_number, insurer_number, insurer_name, insurer_address,
        employment_office_number, labor_insurance_number,
        labor_install_category, labor_install_date, labor_office_category,
        labor_industry_category, founded_date
      )
      VALUES (
        s.company_name, s.company_address, s.ceo_title, s.ceo_name_kana, s.ceo_name,
        s.tel, s.fax, s.office_number, s.health_office_code, s.pension_office_code,
        s.corporate_number, s.insurer_number, s.insurer_name, s.insurer_address,
        s.employment_office_number, s.labor_insurance_number,
        s.labor_install_category, s.labor_install_date, s.labor_office_category,
        s.labor_industry_category, s.founded_date
      );

    ;WITH store_src AS (
        SELECT
            LTRIM(RTRIM(ISNULL(t.[豕穂ｺｺ蠎苓・No], N''))) AS store_code,
            NULLIF(LTRIM(RTRIM(ISNULL(t.[豕穂ｺｺ蠎苓・蜷江, N''))), N'') AS store_name,
            NULLIF(LTRIM(RTRIM(ISNULL(t.[讌ｭ遞ｮ], N''))), N'') AS business_type,
            NULLIF(LTRIM(RTRIM(ISNULL(t.[蠕險ｺ繧ｨ繝ｪ繧｢], N''))), N'') AS visit_area,
            NULLIF(LTRIM(RTRIM(ISNULL(t.[蠎苓・逡･遘ｰ], N''))), N'') AS store_short_name,
            NULLIF(LTRIM(RTRIM(ISNULL(t.[驛ｵ萓ｿ逡ｪ蜿ｷ], N''))), N'') AS postal_code,
            NULLIF(LTRIM(RTRIM(ISNULL(t.[菴乗園縺ｵ繧翫′縺ｪ], N''))), N'') AS address_kana,
            NULLIF(LTRIM(RTRIM(ISNULL(t.[菴乗園], N''))), N'') AS store_address,
            NULLIF(LTRIM(RTRIM(ISNULL(t.[髮ｻ隧ｱ逡ｪ蜿ｷ], N''))), N'') AS tel,
            NULLIF(LTRIM(RTRIM(ISNULL(t.[FAX], N''))), N'') AS fax,
            CASE WHEN TRY_CONVERT(INT, ISNULL(t.[髢牙ｺ余, 0)) = 1 THEN CAST(1 AS BIT) ELSE CAST(0 AS BIT) END AS is_closed,
            TRY_CONVERT(INT, t.[No]) AS legacy_no,
            c.company_id
        FROM dbo.[T_豕穂ｺｺ蠎苓・] t
        INNER JOIN dbo.M_莨夂､ｾ c
            ON c.company_name = LTRIM(RTRIM(ISNULL(t.[遉ｾ蜷江, N'')))
           AND ISNULL(c.company_address, N'') = ISNULL(NULLIF(LTRIM(RTRIM(ISNULL(t.[莨夂､ｾ菴乗園], N''))), N''), N'')
           AND ISNULL(c.office_number, N'') = ISNULL(NULLIF(LTRIM(RTRIM(ISNULL(t.[莠区･ｭ謇逡ｪ蜿ｷ], N''))), N''), N'')
           AND ISNULL(c.health_office_code, N'') = ISNULL(NULLIF(LTRIM(RTRIM(ISNULL(t.[莠区･ｭ謇謨ｴ逅・分蜿ｷ蛛･菫拆, N''))), N''), N'')
        WHERE LTRIM(RTRIM(ISNULL(t.[豕穂ｺｺ蠎苓・No], N''))) <> N''
    )
    MERGE dbo.M_蠎苓・ AS tgt
    USING store_src AS s
      ON tgt.store_code = s.store_code
    WHEN MATCHED THEN
      UPDATE SET
        company_id = s.company_id,
        store_name = ISNULL(s.store_name, tgt.store_name),
        business_type = s.business_type,
        visit_area = s.visit_area,
        store_short_name = s.store_short_name,
        postal_code = s.postal_code,
        address_kana = s.address_kana,
        store_address = s.store_address,
        tel = s.tel,
        fax = s.fax,
        is_closed = s.is_closed,
        legacy_no = s.legacy_no,
        updated_at = SYSDATETIME()
    WHEN NOT MATCHED THEN
      INSERT (
        company_id, store_code, store_name, business_type, visit_area,
        store_short_name, postal_code, address_kana, store_address,
        tel, fax, is_closed, legacy_no
      )
      VALUES (
        s.company_id, s.store_code, ISNULL(s.store_name, s.store_code), s.business_type, s.visit_area,
        s.store_short_name, s.postal_code, s.address_kana, s.store_address,
        s.tel, s.fax, s.is_closed, s.legacy_no
      );

    COMMIT;
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0 ROLLBACK;
    THROW;
END CATCH;

-- Validation
SELECT COUNT(*) AS company_count FROM dbo.M_莨夂､ｾ;
SELECT COUNT(*) AS store_count FROM dbo.M_蠎苓・;