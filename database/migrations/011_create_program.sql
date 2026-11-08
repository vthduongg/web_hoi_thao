-- Chương trình hội thảo: ngày → phiên → mục (data-model.md › program_days ...).

CREATE TABLE program_days (
    id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    conference_id INT NOT NULL CONSTRAINT fk_program_days_conference REFERENCES conferences (id),
    day_date DATE NOT NULL,
    title NVARCHAR(255) NULL,
    sort_order INT NOT NULL CONSTRAINT df_program_days_sort_order DEFAULT 0,
    created_at DATETIME2 NOT NULL CONSTRAINT df_program_days_created_at DEFAULT SYSUTCDATETIME(),
    updated_at DATETIME2 NOT NULL CONSTRAINT df_program_days_updated_at DEFAULT SYSUTCDATETIME(),
    deleted_at DATETIME2 NULL,
    deleted_by INT NULL
);

CREATE TABLE program_sessions (
    id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    program_day_id INT NOT NULL CONSTRAINT fk_program_sessions_day REFERENCES program_days (id),
    title NVARCHAR(255) NOT NULL,
    room NVARCHAR(100) NULL,
    sort_order INT NOT NULL CONSTRAINT df_program_sessions_sort_order DEFAULT 0,
    created_at DATETIME2 NOT NULL CONSTRAINT df_program_sessions_created_at DEFAULT SYSUTCDATETIME(),
    updated_at DATETIME2 NOT NULL CONSTRAINT df_program_sessions_updated_at DEFAULT SYSUTCDATETIME(),
    deleted_at DATETIME2 NULL,
    deleted_by INT NULL
);

CREATE TABLE program_items (
    id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    program_session_id INT NOT NULL CONSTRAINT fk_program_items_session REFERENCES program_sessions (id),
    start_time TIME(0) NOT NULL,
    end_time TIME(0) NOT NULL,
    content NVARCHAR(1000) NOT NULL,
    presenter NVARCHAR(255) NULL,
    room NVARCHAR(100) NULL,
    sort_order INT NOT NULL CONSTRAINT df_program_items_sort_order DEFAULT 0,
    is_visible BIT NOT NULL CONSTRAINT df_program_items_is_visible DEFAULT 1,
    created_at DATETIME2 NOT NULL CONSTRAINT df_program_items_created_at DEFAULT SYSUTCDATETIME(),
    updated_at DATETIME2 NOT NULL CONSTRAINT df_program_items_updated_at DEFAULT SYSUTCDATETIME(),
    deleted_at DATETIME2 NULL,
    deleted_by INT NULL,
    CONSTRAINT ck_program_items_time CHECK (end_time > start_time)
);
