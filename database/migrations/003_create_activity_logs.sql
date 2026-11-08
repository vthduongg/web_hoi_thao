-- Nhật ký hoạt động: chỉ thêm, không sửa, không xoá (data-model.md › activity_logs).

CREATE TABLE activity_logs (
    id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(30) NOT NULL,
    entity_type VARCHAR(30) NULL,
    entity_id INT NULL,
    entity_label NVARCHAR(255) NULL,
    conference_id INT NULL,
    ip_address VARCHAR(45) NULL,
    created_at DATETIME2 NOT NULL CONSTRAINT df_activity_logs_created_at DEFAULT SYSUTCDATETIME(),
    updated_at DATETIME2 NOT NULL CONSTRAINT df_activity_logs_updated_at DEFAULT SYSUTCDATETIME()
);

CREATE INDEX ix_activity_logs_created_at ON activity_logs (created_at);
CREATE INDEX ix_activity_logs_user_id ON activity_logs (user_id);
