-- Hội thảo: mỗi dòng là một kỳ hội thảo theo năm (data-model.md › conferences).
-- Ngày tổ chức KHÔNG lưu ở đây mà ở important_dates (special_role = 'conference_day').

CREATE TABLE conferences (
    id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    name NVARCHAR(255) NOT NULL,
    theme NVARCHAR(255) NULL,
    year INT NOT NULL,
    venue NVARCHAR(500) NULL,
    conference_language NVARCHAR(100) NULL,
    public_language VARCHAR(5) NOT NULL CONSTRAINT df_conferences_public_language DEFAULT 'en',
    logo_media_id INT NULL,
    favicon_media_id INT NULL,
    contact_email NVARCHAR(255) NULL,
    contact_phone NVARCHAR(50) NULL,
    footer_text NVARCHAR(1000) NULL,
    copyright_text NVARCHAR(255) NULL,
    submission_url NVARCHAR(1000) NULL,
    facebook_url NVARCHAR(1000) NULL,
    linkedin_url NVARCHAR(1000) NULL,
    x_url NVARCHAR(1000) NULL,
    youtube_url NVARCHAR(1000) NULL,
    home_intro_html NVARCHAR(MAX) NULL,
    program_file_media_id INT NULL,
    is_current BIT NOT NULL CONSTRAINT df_conferences_is_current DEFAULT 0,
    has_been_current BIT NOT NULL CONSTRAINT df_conferences_has_been_current DEFAULT 0,
    created_at DATETIME2 NOT NULL CONSTRAINT df_conferences_created_at DEFAULT SYSUTCDATETIME(),
    updated_at DATETIME2 NOT NULL CONSTRAINT df_conferences_updated_at DEFAULT SYSUTCDATETIME(),
    CONSTRAINT uq_conferences_year UNIQUE (year)
);

-- Đúng một hội thảo được là "hiện hành"
CREATE UNIQUE INDEX uq_conferences_one_current ON conferences (is_current) WHERE is_current = 1;
