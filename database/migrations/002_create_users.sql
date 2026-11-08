-- Tài khoản quản trị và token đặt lại mật khẩu (data-model.md › users, password_reset_tokens).

CREATE TABLE users (
    id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    email NVARCHAR(255) NOT NULL,
    full_name NVARCHAR(255) NOT NULL,
    password_hash NVARCHAR(255) NOT NULL,
    role VARCHAR(10) NOT NULL CONSTRAINT ck_users_role CHECK (role IN ('admin', 'editor')),
    is_active BIT NOT NULL CONSTRAINT df_users_is_active DEFAULT 1,
    failed_login_count INT NOT NULL CONSTRAINT df_users_failed_login_count DEFAULT 0,
    locked_until DATETIME2 NULL,
    session_epoch INT NOT NULL CONSTRAINT df_users_session_epoch DEFAULT 0,
    last_login_at DATETIME2 NULL,
    created_at DATETIME2 NOT NULL CONSTRAINT df_users_created_at DEFAULT SYSUTCDATETIME(),
    updated_at DATETIME2 NOT NULL CONSTRAINT df_users_updated_at DEFAULT SYSUTCDATETIME(),
    CONSTRAINT uq_users_email UNIQUE (email)
);

CREATE TABLE password_reset_tokens (
    id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    user_id INT NOT NULL CONSTRAINT fk_password_reset_tokens_user REFERENCES users (id) ON DELETE CASCADE,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME2 NOT NULL,
    used_at DATETIME2 NULL,
    created_at DATETIME2 NOT NULL CONSTRAINT df_password_reset_tokens_created_at DEFAULT SYSUTCDATETIME(),
    updated_at DATETIME2 NOT NULL CONSTRAINT df_password_reset_tokens_updated_at DEFAULT SYSUTCDATETIME(),
    CONSTRAINT uq_password_reset_tokens_hash UNIQUE (token_hash)
);
