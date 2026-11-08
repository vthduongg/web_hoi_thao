-- Hạng nhà tài trợ và nhà tài trợ / đối tác (data-model.md › sponsor_tiers, sponsors).

CREATE TABLE sponsor_tiers (
    id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    conference_id INT NOT NULL CONSTRAINT fk_sponsor_tiers_conference REFERENCES conferences (id),
    name NVARCHAR(100) NOT NULL,
    sort_order INT NOT NULL CONSTRAINT df_sponsor_tiers_sort_order DEFAULT 0,
    created_at DATETIME2 NOT NULL CONSTRAINT df_sponsor_tiers_created_at DEFAULT SYSUTCDATETIME(),
    updated_at DATETIME2 NOT NULL CONSTRAINT df_sponsor_tiers_updated_at DEFAULT SYSUTCDATETIME(),
    deleted_at DATETIME2 NULL,
    deleted_by INT NULL
);

CREATE TABLE sponsors (
    id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    conference_id INT NOT NULL CONSTRAINT fk_sponsors_conference REFERENCES conferences (id),
    sponsor_tier_id INT NOT NULL CONSTRAINT fk_sponsors_tier REFERENCES sponsor_tiers (id),
    name NVARCHAR(255) NOT NULL,
    logo_media_id INT NOT NULL CONSTRAINT fk_sponsors_logo REFERENCES media_files (id),
    website_url NVARCHAR(1000) NULL,
    sort_order INT NOT NULL CONSTRAINT df_sponsors_sort_order DEFAULT 0,
    is_visible BIT NOT NULL CONSTRAINT df_sponsors_is_visible DEFAULT 1,
    created_at DATETIME2 NOT NULL CONSTRAINT df_sponsors_created_at DEFAULT SYSUTCDATETIME(),
    updated_at DATETIME2 NOT NULL CONSTRAINT df_sponsors_updated_at DEFAULT SYSUTCDATETIME(),
    deleted_at DATETIME2 NULL,
    deleted_by INT NULL
);
