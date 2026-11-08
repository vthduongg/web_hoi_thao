-- Menu website, tối đa 2 cấp (data-model.md › menu_items). Giới hạn 2 cấp kiểm tra ở MenuRepository.

CREATE TABLE menu_items (
    id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    conference_id INT NOT NULL CONSTRAINT fk_menu_items_conference REFERENCES conferences (id),
    parent_id INT NULL CONSTRAINT fk_menu_items_parent REFERENCES menu_items (id),
    label NVARCHAR(100) NOT NULL,
    page_id INT NULL CONSTRAINT fk_menu_items_page REFERENCES pages (id),
    external_url NVARCHAR(1000) NULL,
    sort_order INT NOT NULL CONSTRAINT df_menu_items_sort_order DEFAULT 0,
    created_at DATETIME2 NOT NULL CONSTRAINT df_menu_items_created_at DEFAULT SYSUTCDATETIME(),
    updated_at DATETIME2 NOT NULL CONSTRAINT df_menu_items_updated_at DEFAULT SYSUTCDATETIME(),
    deleted_at DATETIME2 NULL,
    deleted_by INT NULL,
    -- Không đồng thời trỏ tới trang trong site và link ngoài
    CONSTRAINT ck_menu_items_target CHECK (page_id IS NULL OR external_url IS NULL)
);
