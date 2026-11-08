-- Ban / hội đồng, thành viên, và việc gắn ban vào trang (data-model.md › committees ...).

CREATE TABLE committees (
    id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    conference_id INT NOT NULL CONSTRAINT fk_committees_conference REFERENCES conferences (id),
    name NVARCHAR(255) NOT NULL,
    sort_order INT NOT NULL CONSTRAINT df_committees_sort_order DEFAULT 0,
    is_visible BIT NOT NULL CONSTRAINT df_committees_is_visible DEFAULT 1,
    created_at DATETIME2 NOT NULL CONSTRAINT df_committees_created_at DEFAULT SYSUTCDATETIME(),
    updated_at DATETIME2 NOT NULL CONSTRAINT df_committees_updated_at DEFAULT SYSUTCDATETIME(),
    deleted_at DATETIME2 NULL,
    deleted_by INT NULL
);

CREATE TABLE page_committees (
    page_id INT NOT NULL CONSTRAINT fk_page_committees_page REFERENCES pages (id) ON DELETE CASCADE,
    committee_id INT NOT NULL CONSTRAINT fk_page_committees_committee REFERENCES committees (id) ON DELETE CASCADE,
    sort_order INT NOT NULL CONSTRAINT df_page_committees_sort_order DEFAULT 0,
    created_at DATETIME2 NOT NULL CONSTRAINT df_page_committees_created_at DEFAULT SYSUTCDATETIME(),
    updated_at DATETIME2 NOT NULL CONSTRAINT df_page_committees_updated_at DEFAULT SYSUTCDATETIME(),
    CONSTRAINT pk_page_committees PRIMARY KEY (page_id, committee_id)
);

CREATE TABLE committee_members (
    id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    committee_id INT NOT NULL CONSTRAINT fk_committee_members_committee REFERENCES committees (id),
    academic_title NVARCHAR(100) NULL,
    full_name NVARCHAR(255) NOT NULL,
    position_affiliation NVARCHAR(500) NULL,
    committee_role NVARCHAR(100) NULL,
    photo_media_id INT NULL CONSTRAINT fk_committee_members_photo REFERENCES media_files (id),
    sort_order INT NOT NULL CONSTRAINT df_committee_members_sort_order DEFAULT 0,
    is_visible BIT NOT NULL CONSTRAINT df_committee_members_is_visible DEFAULT 1,
    created_at DATETIME2 NOT NULL CONSTRAINT df_committee_members_created_at DEFAULT SYSUTCDATETIME(),
    updated_at DATETIME2 NOT NULL CONSTRAINT df_committee_members_updated_at DEFAULT SYSUTCDATETIME(),
    deleted_at DATETIME2 NULL,
    deleted_by INT NULL
);
