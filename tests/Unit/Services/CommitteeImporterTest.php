<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Core\Lang;
use App\Services\CommitteeImporter;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use PHPUnit\Framework\TestCase;

/** Đọc và kiểm tra tệp nhập thành viên ban (contracts/import-format.md) — phần không cần CSDL. */
final class CommitteeImporterTest extends TestCase
{
    /** @var list<string> */
    private array $files = [];

    protected function setUp(): void
    {
        Lang::setCurrent(Lang::load('vi', dirname(__DIR__, 3) . '/lang'));
    }

    protected function tearDown(): void
    {
        array_map('unlink', array_filter($this->files, 'is_file'));
    }

    private function csv(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'imp') . '.csv';
        file_put_contents($path, $content);

        return $this->files[] = $path;
    }

    /** @param list<list<string>> $rows */
    private function xlsx(array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'imp') . '.xlsx';
        $writer = new Writer();
        $writer->openToFile($path);
        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }
        $writer->close();

        return $this->files[] = $path;
    }

    public function test_reads_csv_with_vietnamese_headers_and_bom(): void
    {
        $path = $this->csv("\xEF\xBB\xBFHọc hàm học vị,Họ tên,Chức vụ đơn vị,Vai trò\nPGS.TS.,Nguyễn Văn An,Hiệu trưởng,Trưởng ban\n");

        $result = CommitteeImporter::parse($path, 'ds.csv');

        self::assertSame([], $result['errors']);
        self::assertSame([[
            'academic_title' => 'PGS.TS.',
            'full_name' => 'Nguyễn Văn An',
            'position_affiliation' => 'Hiệu trưởng',
            'committee_role' => 'Trưởng ban',
        ]], $result['rows']);
    }

    public function test_reads_semicolon_csv_with_english_headers_in_any_case(): void
    {
        $path = $this->csv("FULL NAME;academic title;Role\nJohn Smith;Prof.;Member\n");

        $result = CommitteeImporter::parse($path, 'list.csv');

        self::assertSame('John Smith', $result['rows'][0]['full_name']);
        self::assertSame('Prof.', $result['rows'][0]['academic_title']);
        self::assertSame('', $result['rows'][0]['position_affiliation']);
    }

    public function test_reads_first_sheet_of_xlsx_and_skips_blank_rows(): void
    {
        $path = $this->xlsx([
            ['Học hàm học vị', 'Họ tên', 'Chức vụ đơn vị', 'Vai trò'],
            ['TS.', 'Trần Thị Bình', 'Trưởng khoa', 'Uỷ viên'],
            ['', '', '', ''],
            ['ThS.', 'Lê Văn Cường', '', 'Thư ký'],
        ]);

        $result = CommitteeImporter::parse($path, 'ds.xlsx');

        self::assertSame([], $result['errors']);
        self::assertSame(['Trần Thị Bình', 'Lê Văn Cường'], array_column($result['rows'], 'full_name'));
    }

    public function test_downloadable_templates_are_valid_import_files(): void
    {
        $dir = dirname(__DIR__, 3) . '/public/assets/templates';

        foreach (['committee-import-template.xlsx', 'committee-import-template.csv'] as $template) {
            $result = CommitteeImporter::parse("$dir/$template", $template);
            self::assertSame([], $result['errors'], $template);
            self::assertCount(2, $result['rows'], $template);
        }
    }

    public function test_missing_required_column(): void
    {
        $result = CommitteeImporter::parse($this->csv("Học hàm học vị,Vai trò\nTS.,Uỷ viên\n"), 'ds.csv');

        self::assertSame(['Tệp thiếu cột: Họ tên'], $result['errors']);
    }

    public function test_reports_each_invalid_row_with_file_line_number(): void
    {
        $lines = "Họ tên,Học hàm học vị\n";
        for ($i = 2; $i <= 6; $i++) {
            $lines .= "Người $i,TS.\n";
        }
        $lines .= ",TS.\n"; // dòng 7 thiếu họ tên
        $lines .= str_repeat('a', 256) . ",TS.\n"; // dòng 8 họ tên quá dài
        $lines .= 'Người 9,' . str_repeat('b', 101) . "\n"; // dòng 9 học hàm quá dài

        $result = CommitteeImporter::parse($this->csv($lines), 'ds.csv');

        self::assertSame([
            'Dòng 7: thiếu Họ tên',
            'Dòng 8: Họ tên tối đa 255 ký tự',
            'Dòng 9: Học hàm học vị tối đa 100 ký tự',
        ], $result['errors']);
    }

    public function test_rejects_more_than_500_rows(): void
    {
        $result = CommitteeImporter::parse($this->csv("Họ tên\n" . str_repeat("Tên\n", 501)), 'ds.csv');

        self::assertSame(['Tệp có quá 500 dòng dữ liệu.'], $result['errors']);
    }

    public function test_rejects_file_over_2_mb_and_other_types(): void
    {
        $big = $this->csv("Họ tên\n" . str_repeat('x', 2 * 1024 * 1024));
        self::assertSame(['Tệp vượt quá 2 MB.'], CommitteeImporter::parse($big, 'ds.csv')['errors']);

        self::assertSame(['Chỉ nhận tệp .xlsx hoặc .csv.'], CommitteeImporter::parse($this->csv('x'), 'ds.pdf')['errors']);
    }
}
