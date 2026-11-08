-- Ảnh slider và khối thông tin nổi bật của trang chủ (data-model.md › slides, highlights).

CREATE TABLE slides (
    id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    conference_id INT NOT NULL CONSTRAINT fk_slides_conference REFERENCES conferences (id),
    media_id INT NOT NULL CONSTRAINT fk_slides_media REFERENCES media_files (id),
    title NVARCHAR(255) NULL,
    alt_text NVARCHAR(500) NOT NULL,
    link_url NVARCHAR(1000) NULL,
    sort_order INT NOT NULL CONSTRAINT df_slides_sort_order DEFAULT 0,
    is_visible BIT NOT NULL CONSTRAINT df_slides_is_visible DEFAULT 1,
    created_at DATETIME2 NOT NULL CONSTRAINT df_slides_created_at DEFAULT SYSUTCDATETIME(),
    updated_at DATETIME2 NOT NULL CONSTRAINT df_slides_updated_at DEFAULT SYSUTCDATETIME(),
    deleted_at DATETIME2 NULL,
    deleted_by INT NULL
);

CREATE TABLE highlights (
    id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    conference_id INT NOT NULL CONSTRAINT fk_highlights_conference REFERENCES conferences (id),
    title NVARCHAR(255) NOT NULL,
    body_text NVARCHAR(500) NULL,
    link_url NVARCHAR(1000) NULL,
    sort_order INT NOT NULL CONSTRAINT df_highlights_sort_order DEFAULT 0,
    is_visible BIT NOT NULL CONSTRAINT df_highlights_is_visible DEFAULT 1,
    created_at DATETIME2 NOT NULL CONSTRAINT df_highlights_created_at DEFAULT SYSUTCDATETIME(),
    updated_at DATETIME2 NOT NULL CONSTRAINT df_highlights_updated_at DEFAULT SYSUTCDATETIME(),
    deleted_at DATETIME2 NULL,
    deleted_by INT NULL
);
