-- Người liên hệ (data-model.md › contact_persons).

CREATE TABLE contact_persons (
    id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    conference_id INT NOT NULL CONSTRAINT fk_contact_persons_conference REFERENCES conferences (id),
    full_name NVARCHAR(255) NOT NULL,
    affiliation NVARCHAR(500) NULL,
    email NVARCHAR(255) NULL,
    phone NVARCHAR(50) NULL,
    sort_order INT NOT NULL CONSTRAINT df_contact_persons_sort_order DEFAULT 0,
    is_visible BIT NOT NULL CONSTRAINT df_contact_persons_is_visible DEFAULT 1,
    created_at DATETIME2 NOT NULL CONSTRAINT df_contact_persons_created_at DEFAULT SYSUTCDATETIME(),
    updated_at DATETIME2 NOT NULL CONSTRAINT df_contact_persons_updated_at DEFAULT SYSUTCDATETIME(),
    deleted_at DATETIME2 NULL,
    deleted_by INT NULL
);
