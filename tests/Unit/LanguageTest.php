<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Nguyên tắc III của hiến pháp: giao diện công khai dùng ngôn ngữ hội thảo (lang/en.php),
 * giao diện quản trị dùng tiếng Việt (lang/vi.php). Mọi chữ hiển thị cố định phải lấy từ file lang,
 * không viết thẳng trong template.
 */
final class LanguageTest extends TestCase
{
    /** Ngoại lệ duy nhất đã ghi trong contracts/ui-theme.md. */
    private const EXCEPTIONS = ['templates/public/partials/preview-banner.php'];

    private static function root(): string
    {
        return dirname(__DIR__, 2);
    }

    /** @return array<string, string> */
    private static function lang(string $code): array
    {
        return require self::root() . "/lang/$code.php";
    }

    /** @return array<string, string> đường dẫn tương đối => nội dung */
    private static function templates(string $area): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(self::root() . "/templates/$area", \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $relative = str_replace('\\', '/', substr($file->getPathname(), strlen(self::root()) + 1));
                $files[$relative] = (string) file_get_contents($file->getPathname());
            }
        }
        ksort($files);

        return $files;
    }

    /** @return list<string> "tệp:dòng khoá" của khoá t('…') không có trong file lang */
    private static function missingKeys(string $area, array $lang): array
    {
        $missing = [];
        foreach (self::templates($area) as $path => $source) {
            preg_match_all("/\\bt\\(\\s*'([a-z0-9_.]+)'\\s*[,)]/", $source, $matches, PREG_OFFSET_CAPTURE);
            foreach ($matches[1] as [$key, $offset]) {
                if (!array_key_exists($key, $lang)) {
                    $missing[] = $path . ':' . (substr_count(substr($source, 0, $offset), "\n") + 1) . " $key";
                }
            }
        }

        return $missing;
    }

    public function test_public_template_keys_exist_in_english_file(): void
    {
        self::assertSame([], self::missingKeys('public', self::lang('en')));
    }

    public function test_admin_template_keys_exist_in_vietnamese_file(): void
    {
        self::assertSame([], self::missingKeys('admin', self::lang('vi')));
    }

    public function test_language_files_have_no_empty_values(): void
    {
        foreach (['en', 'vi'] as $code) {
            $empty = array_keys(array_filter(self::lang($code), static fn ($value): bool => !is_string($value) || trim($value) === ''));
            self::assertSame([], $empty, "lang/$code.php có khoá rỗng");
        }
    }

    /**
     * Thay mọi khối PHP và chú thích HTML bằng khoảng trắng (giữ nguyên số dòng),
     * phần còn lại chỉ là HTML viết tay trong template.
     */
    private static function htmlOnly(string $source): string
    {
        $blank = static fn (array $m): string => preg_replace('/[^\n]/', ' ', $m[0]);
        $source = (string) preg_replace_callback('/<\?(php|=).*?(\?>|$)/s', $blank, $source);

        return (string) preg_replace_callback('/<!--.*?-->/s', $blank, $source);
    }

    /** @return list<string> "tệp:dòng: chữ" của chữ viết thẳng trong template */
    private static function hardcodedText(): array
    {
        $found = [];
        foreach (self::templates('public') + self::templates('admin') as $path => $source) {
            if (in_array($path, self::EXCEPTIONS, true)) {
                continue;
            }
            $html = self::htmlOnly($source);
            $patterns = [
                '/>([^<>]*\p{L}{2,}[^<>]*)</u',
                '/\s(?:alt|title|placeholder|aria-label)="([^"]*\p{L}{2,}[^"]*)"/u',
            ];
            foreach ($patterns as $pattern) {
                preg_match_all($pattern, $html, $matches, PREG_OFFSET_CAPTURE);
                foreach ($matches[1] as [$text, $offset]) {
                    $found[] = $path . ':' . (substr_count(substr($html, 0, $offset), "\n") + 1) . ': ' . trim($text);
                }
            }
        }

        return $found;
    }

    public function test_templates_have_no_hardcoded_visible_text(): void
    {
        self::assertSame([], self::hardcodedText(), 'Chữ hiển thị phải lấy qua t(...) / e(...) từ file lang');
    }

    public function test_checker_detects_hardcoded_text(): void
    {
        $html = self::htmlOnly("<p>\n<?= e(t('a')) ?></p>\n<p>Hello world</p>\n<img alt=\"Logo\">\n<!-- ghi chú -->");

        self::assertStringNotContainsString("t('a')", $html);
        self::assertMatchesRegularExpression('/>Hello world</', $html);
        self::assertMatchesRegularExpression('/alt="Logo"/', $html);
        self::assertStringNotContainsString('ghi chú', $html);
        self::assertSame(substr_count("<p>\n<?= e(t('a')) ?></p>\n<p>Hello world</p>\n<img alt=\"Logo\">\n<!-- ghi chú -->", "\n"), substr_count($html, "\n"));
    }
}
