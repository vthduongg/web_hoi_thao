<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Response;
use App\Repositories\CrudRepository;
use App\Services\SortOrderService;
use InvalidArgumentException;

/**
 * Bộ thao tác dùng chung cho dữ liệu có cấu trúc (FR-030 – FR-035a): danh sách, thêm, sửa, Hiện/Ẩn,
 * sắp xếp, xoá vào thùng rác.
 *
 * Lớp con chỉ cần KHAI BÁO: bảng, đường dẫn, phạm vi dữ liệu và danh sách trường (fields()).
 * Biểu mẫu và danh sách được vẽ tự động từ khai báo đó (templates/admin/crud/).
 *
 * Mỗi trường là một mảng:
 *   ['name' => 'full_name', 'label' => 'Họ tên', 'type' => 'text', 'rules' => 'required|max:255', 'hint' => '...']
 * Kiểu (type): text, textarea, rich (trình soạn thảo), date, time, email, tel, url, select (kèm 'options'),
 * media (chọn ảnh từ thư viện), file (chọn tệp bất kỳ từ thư viện), pages (chọn nhiều trang — không lưu vào bảng).
 */
abstract class CrudController extends AdminController
{
    /** Tên bảng trong CSDL. */
    abstract protected function table(): string;

    /** Đường dẫn gốc của nhóm, ví dụ "/admin/dates". */
    abstract protected function basePath(): string;

    /** @return array{list: string, create: string, edit: string} tiêu đề các trang */
    abstract protected function titles(): array;

    /** @return list<array<string, mixed>> danh sách trường của biểu mẫu */
    abstract protected function fields(): array;

    /**
     * Phạm vi dữ liệu (để không đọc/sửa nhầm dữ liệu của hội thảo/ban khác). Gọi notFound() nếu cha không hợp lệ.
     *
     * @return array{where: string, params: list<mixed>, insert: array<string, mixed>}
     */
    abstract protected function scope(): array;

    // ----- Các điểm mở rộng (lớp con ghi đè khi cần) -----

    /** Bảng có cột is_visible (công tắc Hiện/Ẩn)? */
    protected function hasVisibility(): bool
    {
        return true;
    }

    /** Tên hiển thị của một dòng (danh sách, nhật ký). Mặc định: giá trị trường đầu tiên. */
    protected function labelOf(array $row): string
    {
        return (string) ($row[$this->fields()[0]['name']] ?? '');
    }

    /** @return array<string, string> các cột phụ trong danh sách: tiêu đề cột => giá trị */
    protected function extraCells(array $row): array
    {
        return [];
    }

    /** @return list<array{0: string, 1: string}> liên kết phụ trên mỗi dòng: [chữ, đường dẫn] */
    protected function rowLinks(array $row): array
    {
        return [];
    }

    /** @return array<string, string> lỗi kiểm tra riêng của nhóm (ngoài quy tắc của từng trường) */
    protected function validateExtra(array $data, ?int $id): array
    {
        return [];
    }

    /** Đổi dữ liệu biểu mẫu thành cột của bảng. Mặc định: mọi trường (trừ kiểu pages), chuỗi rỗng → NULL. */
    protected function toColumns(array $data): array
    {
        $columns = [];
        foreach ($this->fields() as $field) {
            if ($field['type'] !== 'pages') {
                $value = $data[$field['name']];
                $columns[$field['name']] = $value === '' ? null : $value;
            }
        }

        return $columns;
    }

    /** Việc cần làm thêm sau khi lưu (ví dụ gắn ban vào trang). */
    protected function afterSave(int $id, array $data): void
    {
    }

    /** Giá trị ban đầu của biểu mẫu khi sửa. */
    protected function formValuesFromRow(array $row): array
    {
        $values = [];
        foreach ($this->fields() as $field) {
            $values[$field['name']] = $row[$field['name']] ?? '';
        }

        return $values;
    }

    /** Trang quay về sau khi lưu / xoá. */
    protected function redirectAfterSave(): string
    {
        return $this->basePath();
    }

    /** Hội thảo ghi vào nhật ký. */
    protected function conferenceIdForLog(): ?int
    {
        return $this->editingConference()?->id;
    }

    // ----- Các thao tác (route gọi tới) -----

    public function index(string ...$route): Response
    {
        return $this->render('admin/crud/index', [
            'pageTitle' => $this->titles()['list'],
            'basePath' => $this->basePath(),
            'items' => array_map(fn (array $row): array => $this->listItem($row), $this->repository()->all()),
            'hasVisibility' => $this->hasVisibility(),
            'headerLinks' => $this->headerLinks(),
            'extraScripts' => ['js/sortable-list.js'],
        ]);
    }

    public function create(string ...$route): Response
    {
        $values = [];
        foreach ($this->fields() as $field) {
            $values[$field['name']] = $field['default'] ?? ($field['type'] === 'pages' ? [] : '');
        }

        return $this->form(null, $values);
    }

    public function store(string ...$route): Response
    {
        $data = $this->formData();
        $errors = $this->validateForm($data, null);
        if ($errors !== []) {
            return $this->form(null, $data, $errors);
        }

        $id = $this->repository()->insert($this->toColumns($data) + $this->scope()['insert']);
        $this->afterSave($id, $data);
        $this->log('create', $this->table(), $id, $this->labelOf($data), $this->conferenceIdForLog());
        $this->services->flash->success(t('common.saved'));

        return $this->redirect($this->redirectAfterSave());
    }

    public function edit(string $id, string ...$route): Response
    {
        $row = $this->findRow($id);

        return $this->form((int) $row['id'], $this->formValuesFromRow($row));
    }

    public function update(string $id, string ...$route): Response
    {
        $row = $this->findRow($id);
        $data = $this->formData();
        $errors = $this->validateForm($data, (int) $row['id']);
        if ($errors !== []) {
            return $this->form((int) $row['id'], $data, $errors);
        }

        $this->repository()->update((int) $row['id'], $this->toColumns($data));
        $this->afterSave((int) $row['id'], $data);
        $this->log('update', $this->table(), (int) $row['id'], $this->labelOf($data), $this->conferenceIdForLog());
        $this->services->flash->success(t('common.saved'));

        return $this->redirect($this->redirectAfterSave());
    }

    public function toggleVisibility(string $id, string ...$route): Response
    {
        $row = $this->findRow($id);
        $this->repository()->toggleVisibility((int) $row['id']);
        $this->log('update', $this->table(), (int) $row['id'], $this->labelOf($row), $this->conferenceIdForLog());

        return $this->redirect($this->basePath());
    }

    /** Nhận thứ tự mới từ sortable-list.js (ids[]), trả JSON. */
    public function reorder(string ...$route): Response
    {
        $scope = $this->scope();
        try {
            (new SortOrderService($this->services->db))->apply($this->table(), $scope['where'], $scope['params'], $this->request->inputArray('ids'));
        } catch (InvalidArgumentException) {
            return Response::json(['error' => t('crud.reorder_invalid')], 422);
        }

        return Response::json(['ok' => true]);
    }

    public function delete(string $id, string ...$route): Response
    {
        $row = $this->findRow($id);
        $this->repository()->softDelete((int) $row['id'], $this->currentUser()->id);
        $this->log('delete', $this->table(), (int) $row['id'], $this->labelOf($row), $this->conferenceIdForLog());
        $this->services->flash->success(t('common.deleted'));

        return $this->redirect($this->basePath());
    }

    // ----- Phần dùng chung bên trong -----

    protected function repository(): CrudRepository
    {
        $scope = $this->scope();

        return new CrudRepository($this->services->db, $this->table(), $scope['where'], $scope['params']);
    }

    /** @return array<string, mixed> dòng thuộc phạm vi, hoặc 404 */
    protected function findRow(string $id): array
    {
        return $this->repository()->find((int) $id) ?? $this->notFound();
    }

    /** @return list<array{0: string, 1: string}> nút phía trên danh sách: [chữ, đường dẫn] */
    protected function headerLinks(): array
    {
        return [];
    }

    /** Dữ liệu từ biểu mẫu theo khai báo trường. */
    private function formData(): array
    {
        $data = [];
        foreach ($this->fields() as $field) {
            $name = $field['name'];
            $data[$name] = match ($field['type']) {
                'pages' => array_map('intval', array_filter($this->request->inputArray($name), 'ctype_digit')),
                'rich' => (string) ($this->request->post[$name] ?? ''),
                'media', 'file' => ctype_digit($this->request->input($name)) ? (int) $this->request->input($name) : '',
                default => $this->request->input($name),
            };
        }

        return $data;
    }

    private function validateForm(array $data, ?int $id): array
    {
        $rules = [];
        foreach ($this->fields() as $field) {
            $rules[$field['name']] = $field['rules'] ?? '';
        }
        $errors = $this->validate($data, array_filter($rules));

        return $errors + $this->validateExtra($data, $id);
    }

    private function listItem(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'label' => $this->labelOf($row),
            'cells' => $this->extraCells($row),
            'visible' => $this->hasVisibility() ? (bool) $row['is_visible'] : null,
            'links' => $this->rowLinks($row),
        ];
    }

    private function form(?int $id, array $values, array $errors = []): Response
    {
        $hasRich = in_array('rich', array_column($this->fields(), 'type'), true);

        return $this->render('admin/crud/form', [
            'pageTitle' => $id === null ? $this->titles()['create'] : $this->titles()['edit'],
            'action' => $id === null ? $this->basePath() . '/new' : $this->basePath() . '/' . $id,
            'cancelUrl' => $this->redirectAfterSave(),
            'fields' => $this->fields(),
            'values' => $values,
            'errors' => $errors,
            'mediaPreviews' => $this->mediaPreviews($values),
            'extraScripts' => $hasRich ? ['vendor/tinymce/tinymce.min.js', 'js/editor.js', 'js/media-picker.js'] : ['js/media-picker.js'],
        ], $errors === [] ? 200 : 422);
    }

    /** @return array<string, ?\App\Models\MediaFile> tệp đang chọn của các trường media/file */
    private function mediaPreviews(array $values): array
    {
        $media = new \App\Repositories\MediaRepository($this->services->db);
        $previews = [];
        foreach ($this->fields() as $field) {
            if (in_array($field['type'], ['media', 'file'], true)) {
                $previews[$field['name']] = $media->find(is_numeric($values[$field['name']] ?? null) ? (int) $values[$field['name']] : null);
            }
        }

        return $previews;
    }
}
