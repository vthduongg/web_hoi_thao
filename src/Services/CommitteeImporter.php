<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\CommitteeRepository;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

/**
 * Nhập danh sách thành viên ban từ tệp .xlsx hoặc .csv (contracts/import-format.md).
 *
 * Nguyên tắc "tất cả hoặc không": có bất kỳ lỗi nào thì không thêm dòng nào.
 */
final class CommitteeImporter
{
    public const MAX_BYTES = 2 * 1024 * 1024;
    public const MAX_ROWS = 500;

    /** Cột trong CSDL => tên cột chấp nhận trong tệp (viết thường) */
    private const HEADERS = [
        'academic_title' => ['học hàm học vị', 'academic title'],
        'full_name' => ['họ tên', 'full name'],
        'position_affiliation' => ['chức vụ đơn vị', 'position and affiliation'],
        'committee_role' => ['vai trò', 'role'],
    ];

    /** Độ dài tối đa của từng cột */
    private const MAX_LENGTH = ['academic_title' => 100, 'full_name' => 255, 'position_affiliation' => 500, 'committee_role' => 100];

    /** Tên cột (tiếng Việt) dùng trong thông báo lỗi */
    private const LABELS = ['academic_title' => 'Học hàm học vị', 'full_name' => 'Họ tên', 'position_affiliation' => 'Chức vụ đơn vị', 'committee_role' => 'Vai trò'];

    public function __construct(private readonly Database $db)
    {
    }

    /**
     * Đọc và kiểm tra tệp, chưa ghi CSDL.
     *
     * @return array{rows: list<array<string, string>>, errors: list<string>}
     */
    public static function parse(string $path, string $originalName): array
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, ['xlsx', 'csv'], true)) {
            return ['rows' => [], 'errors' => [t('import.wrong_type')]];
        }
        if ((int) filesize($path) > self::MAX_BYTES) {
            return ['rows' => [], 'errors' => [t('import.too_large')]];
        }

        $lines = $extension === 'xlsx' ? self::readXlsx($path) : self::readCsv($path);
        $header = array_shift($lines) ?? ['cells' => []];
        $columns = self::mapHeader($header['cells']);
        if (!isset($columns['full_name'])) {
            return ['rows' => [], 'errors' => [t('import.missing_column', ['column' => self::LABELS['full_name']])]];
        }

        $lines = array_values(array_filter($lines, static fn (array $line): bool => implode('', $line['cells']) !== ''));
        if (count($lines) > self::MAX_ROWS) {
            return ['rows' => [], 'errors' => [t('import.too_many_rows', ['max' => self::MAX_ROWS])]];
        }

        return self::buildRows($lines, $columns);
    }

    /**
     * Đọc tệp rồi thêm toàn bộ thành viên vào cuối danh sách của ban. Trả về danh sách lỗi (rỗng = thành công).
     *
     * @return list<string>
     */
    public function import(int $committeeId, string $path, string $originalName): array
    {
        $result = self::parse($path, $originalName);
        if ($result['errors'] !== []) {
            return $result['errors'];
        }

        $this->db->transaction(fn () => (new CommitteeRepository($this->db))->appendMembers($committeeId, $result['rows']));

        return [];
    }

    /**
     * @param list<array{line: int, cells: list<string>}> $lines
     * @param array<string, int> $columns cột CSDL => vị trí trong tệp
     * @return array{rows: list<array<string, string>>, errors: list<string>}
     */
    private static function buildRows(array $lines, array $columns): array
    {
        $rows = [];
        $errors = [];
        foreach ($lines as $line) {
            $row = [];
            foreach (array_keys(self::HEADERS) as $column) {
                $row[$column] = isset($columns[$column]) ? trim($line['cells'][$columns[$column]] ?? '') : '';
            }
            $error = self::rowError($row, $line['line']);
            if ($error === null) {
                $rows[] = $row;
            } else {
                $errors[] = $error;
            }
        }

        return ['rows' => $errors === [] ? $rows : [], 'errors' => $errors];
    }

    private static function rowError(array $row, int $line): ?string
    {
        if ($row['full_name'] === '') {
            return t('import.row_missing', ['line' => $line, 'column' => self::LABELS['full_name']]);
        }
        foreach (self::MAX_LENGTH as $column => $max) {
            if (mb_strlen($row[$column]) > $max) {
                return t('import.row_too_long', ['line' => $line, 'column' => self::LABELS[$column], 'max' => $max]);
            }
        }

        return null;
    }

    /** @return array<string, int> cột CSDL => vị trí cột trong tệp */
    private static function mapHeader(array $cells): array
    {
        $columns = [];
        foreach ($cells as $position => $cell) {
            $name = mb_strtolower(trim($cell));
            foreach (self::HEADERS as $column => $accepted) {
                if (in_array($name, $accepted, true)) {
                    $columns[$column] = $position;
                }
            }
        }

        return $columns;
    }

    /** @return list<array{line: int, cells: list<string>}> */
    private static function readCsv(string $path): array
    {
        $content = (string) file_get_contents($path);
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3); // bỏ BOM của UTF-8
        }
        $firstLine = strtok($content, "\n") ?: '';
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

        $lines = [];
        foreach (preg_split('/\r\n|\n|\r/', rtrim($content, "\r\n")) as $index => $text) {
            $lines[] = ['line' => $index + 1, 'cells' => str_getcsv($text, $delimiter, '"', '')];
        }

        return $lines;
    }

    /** Đọc sheet đầu tiên của tệp Excel. @return list<array{line: int, cells: list<string>}> */
    private static function readXlsx(string $path): array
    {
        $reader = new XlsxReader();
        $reader->open($path);
        $lines = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $number => $row) {
                $lines[] = ['line' => (int) $number, 'cells' => array_map(static fn (mixed $cell): string => (string) $cell, $row->toArray())];
            }
            break;
        }
        $reader->close();

        return $lines;
    }
}
