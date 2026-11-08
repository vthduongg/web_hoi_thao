-- Trang nội dung, lịch sử phiên bản, thẻ, chuyển hướng slug cũ (data-model.md › pages ...).

CREATE TABLE pages (
    id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    conference_id INT NOT NULL CONSTRAINT fk_pages_conference REFERENCES conferences (id),
    title NVARCHAR(255) NOT NULL,
    slug NVARCHAR(150) NOT NULL,
    body_html NVARCHAR(MAX) NULL,
    module VARCHAR(30) NOT NULL CONSTRAINT df_pages_module DEFAULT 'none'
        CONSTRAINT ck_pages_module CHECK (module IN ('none', 'important_dates', 'committees', 'keynote_speakers', 'program', 'sponsors', 'contacts')),
    seo_title NVARCHAR(255) NULL,
    seo_description NVARCHAR(500) NULL,
    featured_media_id INT NULL CONSTRAINT fk_pages_featured_media REFERENCES media_files (id),
    status VARCHAR(10) NOT NULL CONSTRAINT df_pages_status DEFAULT 'draft'
        CONSTRAINT ck_pages_status CHECK (status IN ('draft', 'published', 'hidden')),
    published_at DATETIME2 NULL,
    version INT NOT NULL CONSTRAINT df_pages_version DEFAULT 1,
    created_by INT NULL,
    updated_by INT NULL,
    created_at DATETIME2 NOT NULL CONSTRAINT df_pages_created_at DEFAULT SYSUTCDATETIME(),
    updated_at DATETIME2 NOT NULL CONSTRAINT df_pages_updated_at DEFAULT SYSUTCDATETIME(),
    deleted_at DATETIME2 NULL,
    deleted_by INT NULL,
    CONSTRAINT ck_pages_published_at CHECK (status <> 'published' OR published_at IS NOT NULL)
);

-- Slug duy nhất trong một hội thảo (không tính trang đã xoá vào thùng rác)
CREATE UNIQUE INDEX uq_pages_slug ON pages (conference_id, slug) WHERE deleted_at IS NULL;

CREATE TABLE page_revisions (
    id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    page_id INT NOT NULL CONSTRAINT fk_page_revisions_page REFERENCES pages (id) ON DELETE CASCADE,
    revision_no INT NOT NULL,
    title NVARCHAR(255) NOT NULL,
    body_html NVARCHAR(MAX) NULL,
    seo_title NVARCHAR(255) NULL,
    seo_description NVARCHAR(500) NULL,
    featured_media_id INT NULL,
    module VARCHAR(30) NOT NULL,
    created_by INT NULL,
    created_at DATETIME2 NOT NULL CONSTRAINT df_page_revisions_created_at DEFAULT SYSUTCDATETIME(),
    updated_at DATETIME2 NOT NULL CONSTRAINT df_page_revisions_updated_at DEFAULT SYSUTCDATETIME(),
    CONSTRAINT uq_page_revisions_no UNIQUE (page_id, revision_no)
);

CREATE TABLE page_tags (
    page_id INT NOT NULL CONSTRAINT fk_page_tags_page REFERENCES pages (id) ON DELETE CASCADE,
    tag NVARCHAR(50) NOT NULL,
    created_at DATETIME2 NOT NULL CONSTRAINT df_page_tags_created_at DEFAULT SYSUTCDATETIME(),
    updated_at DATETIME2 NOT NULL CONSTRAINT df_page_tags_updated_at DEFAULT SYSUTCDATETIME(),
    CONSTRAINT pk_page_tags PRIMARY KEY (page_id, tag)
);

CREATE TABLE slug_redirects (
    id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    conference_id INT NOT NULL CONSTRAINT fk_slug_redirects_conference REFERENCES conferences (id),
    old_slug NVARCHAR(150) NOT NULL,
    page_id INT NOT NULL CONSTRAINT fk_slug_redirects_page REFERENCES pages (id) ON DELETE CASCADE,
    created_at DATETIME2 NOT NULL CONSTRAINT df_slug_redirects_created_at DEFAULT SYSUTCDATETIME(),
    updated_at DATETIME2 NOT NULL CONSTRAINT df_slug_redirects_updated_at DEFAULT SYSUTCDATETIME(),
    CONSTRAINT uq_slug_redirects UNIQUE (conference_id, old_slug)
);
