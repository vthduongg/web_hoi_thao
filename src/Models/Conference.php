<?php

declare(strict_types=1);

namespace App\Models;

/** Một kỳ hội thảo (bảng conferences). Chỉ chứa dữ liệu, không truy vấn CSDL. */
final class Conference
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?string $theme,
        public readonly int $year,
        public readonly ?string $venue,
        public readonly ?string $conferenceLanguage,
        public readonly string $publicLanguage,
        public readonly ?int $logoMediaId,
        public readonly ?int $faviconMediaId,
        public readonly ?string $contactEmail,
        public readonly ?string $contactPhone,
        public readonly ?string $footerText,
        public readonly ?string $copyrightText,
        public readonly ?string $submissionUrl,
        public readonly ?string $facebookUrl,
        public readonly ?string $linkedinUrl,
        public readonly ?string $xUrl,
        public readonly ?string $youtubeUrl,
        public readonly ?string $homeIntroHtml,
        public readonly ?int $programFileMediaId,
        public readonly bool $isCurrent,
        public readonly bool $hasBeenCurrent,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            name: $row['name'],
            theme: $row['theme'],
            year: (int) $row['year'],
            venue: $row['venue'],
            conferenceLanguage: $row['conference_language'],
            publicLanguage: $row['public_language'],
            logoMediaId: $row['logo_media_id'],
            faviconMediaId: $row['favicon_media_id'],
            contactEmail: $row['contact_email'],
            contactPhone: $row['contact_phone'],
            footerText: $row['footer_text'],
            copyrightText: $row['copyright_text'],
            submissionUrl: $row['submission_url'],
            facebookUrl: $row['facebook_url'],
            linkedinUrl: $row['linkedin_url'],
            xUrl: $row['x_url'],
            youtubeUrl: $row['youtube_url'],
            homeIntroHtml: $row['home_intro_html'],
            programFileMediaId: $row['program_file_media_id'],
            isCurrent: (bool) $row['is_current'],
            hasBeenCurrent: (bool) $row['has_been_current'],
        );
    }
}
