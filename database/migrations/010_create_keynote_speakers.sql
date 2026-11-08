-- Diễn giả chính (data-model.md › keynote_speakers).

CREATE TABLE keynote_speakers (
    id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    conference_id INT NOT NULL CONSTRAINT fk_keynote_speakers_conference REFERENCES conferences (id),
    photo_media_id INT NULL CONSTRAINT fk_keynote_speakers_photo REFERENCES media_files (id),
    academic_title NVARCHAR(100) NULL,
    full_name NVARCHAR(255) NOT NULL,
    affiliation NVARCHAR(500) NULL,
    country NVARCHAR(100) NULL,
    talk_title NVARCHAR(500) NULL,
    bio_html NVARCHAR(MAX) NULL,
    sort_order INT NOT NULL CONSTRAINT df_keynote_speakers_sort_order DEFAULT 0,
    is_visible BIT NOT NULL CONSTRAINT df_keynote_speakers_is_visible DEFAULT 1,
    created_at DATETIME2 NOT NULL CONSTRAINT df_keynote_speakers_created_at DEFAULT SYSUTCDATETIME(),
    updated_at DATETIME2 NOT NULL CONSTRAINT df_keynote_speakers_updated_at DEFAULT SYSUTCDATETIME(),
    deleted_at DATETIME2 NULL,
    deleted_by INT NULL
);
