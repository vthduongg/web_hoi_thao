-- Thư viện tệp, dùng chung mọi hội thảo (data-model.md › media_files). Không nhận SVG.

CREATE TABLE media_files (
    id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    original_name NVARCHAR(255) NOT NULL,
    stored_path NVARCHAR(500) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    size_bytes INT NOT NULL,
    kind VARCHAR(10) NOT NULL CONSTRAINT ck_media_files_kind CHECK (kind IN ('image', 'document')),
    width INT NULL,
    height INT NULL,
    thumb_small_path NVARCHAR(500) NULL,
    thumb_large_path NVARCHAR(500) NULL,
    alt_text NVARCHAR(500) NULL,
    uploaded_by INT NULL,
    created_at DATETIME2 NOT NULL CONSTRAINT df_media_files_created_at DEFAULT SYSUTCDATETIME(),
    updated_at DATETIME2 NOT NULL CONSTRAINT df_media_files_updated_at DEFAULT SYSUTCDATETIME(),
    deleted_at DATETIME2 NULL,
    deleted_by INT NULL
);

ALTER TABLE conferences ADD
    CONSTRAINT fk_conferences_logo FOREIGN KEY (logo_media_id) REFERENCES media_files (id),
    CONSTRAINT fk_conferences_favicon FOREIGN KEY (favicon_media_id) REFERENCES media_files (id),
    CONSTRAINT fk_conferences_program_file FOREIGN KEY (program_file_media_id) REFERENCES media_files (id);
