-- Mốc thời gian quan trọng — nguồn dữ liệu ngày duy nhất của website (FR-002, data-model.md › important_dates).

CREATE TABLE important_dates (
    id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    conference_id INT NOT NULL CONSTRAINT fk_important_dates_conference REFERENCES conferences (id),
    label NVARCHAR(255) NOT NULL,
    status VARCHAR(10) NOT NULL CONSTRAINT ck_important_dates_status CHECK (status IN ('normal', 'extended', 'tba')),
    date_start DATE NULL,
    date_end DATE NULL,
    original_date DATE NULL,
    special_role VARCHAR(30) NULL
        CONSTRAINT ck_important_dates_role CHECK (special_role IN ('abstract_deadline', 'full_paper_deadline', 'conference_day')),
    sort_order INT NOT NULL CONSTRAINT df_important_dates_sort_order DEFAULT 0,
    is_visible BIT NOT NULL CONSTRAINT df_important_dates_is_visible DEFAULT 1,
    created_at DATETIME2 NOT NULL CONSTRAINT df_important_dates_created_at DEFAULT SYSUTCDATETIME(),
    updated_at DATETIME2 NOT NULL CONSTRAINT df_important_dates_updated_at DEFAULT SYSUTCDATETIME(),
    deleted_at DATETIME2 NULL,
    deleted_by INT NULL,
    -- Bắt buộc có ngày, trừ khi "sẽ thông báo sau"
    CONSTRAINT ck_important_dates_start CHECK (status = 'tba' OR date_start IS NOT NULL),
    -- Ngày kết thúc không trước ngày bắt đầu
    CONSTRAINT ck_important_dates_end CHECK (date_end IS NULL OR date_end >= date_start),
    -- Gia hạn thì phải có ngày cũ
    CONSTRAINT ck_important_dates_extended CHECK (status <> 'extended' OR original_date IS NOT NULL)
);

-- Mỗi vai trò đặc biệt chỉ có một mốc trong một hội thảo
CREATE UNIQUE INDEX uq_important_dates_role ON important_dates (conference_id, special_role)
    WHERE special_role IS NOT NULL AND deleted_at IS NULL;
