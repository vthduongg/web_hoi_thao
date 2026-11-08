<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Nội dung mẫu (tiếng Anh) cho các trang mặc định của website (FR-003).
 * Ban tổ chức sửa lại nội dung này trong trang quản trị.
 */
final class SamplePages
{
    /**
     * Hàm dài hơn 40 dòng có chủ đích (constitution, Nguyên tắc I — "có lý do ghi rõ"):
     * đây chỉ là một danh sách dữ liệu tĩnh, không có logic; tách nhỏ không làm dễ đọc hơn.
     *
     * @return list<array{slug: string, title: string, module: string, body: string}>
     */
    public static function all(): array
    {
        return [
            [
                'slug' => 'call-for-papers',
                'title' => 'Call for Papers',
                'module' => 'none',
                'body' => '<h2>Key information</h2><p>The conference invites researchers, lecturers, teachers and graduate students '
                    . 'to submit abstracts on English language teaching and learning.</p>'
                    . '<h2>Background and significance</h2><p>Language education is changing quickly. The conference creates a forum '
                    . 'to share practices, opportunities and challenges.</p>'
                    . '<h2>Objectives</h2><ul><li>Share research findings and classroom practice.</li><li>Build a network of researchers and teachers.</li></ul>'
                    . '<h2>Themes</h2><ul><li>Language teaching methodology</li><li>Technology in language education</li>'
                    . '<li>Assessment and testing</li><li>Teacher education and professional development</li></ul>'
                    . '<h2>Who should attend</h2><p>Researchers, lecturers, teachers, graduate students and education managers.</p>',
            ],
            [
                'slug' => 'important-dates',
                'title' => 'Important Dates',
                'module' => 'important_dates',
                'body' => '<p>Please note the following deadlines. All dates are in Vietnam time (GMT+7).</p>',
            ],
            [
                'slug' => 'scientific-committee',
                'title' => 'Scientific Committee',
                'module' => 'committees',
                'body' => '<p>Members of the Scientific Committee review all submissions.</p>',
            ],
            [
                'slug' => 'organizing-committee',
                'title' => 'Organizing Committee',
                'module' => 'committees',
                'body' => '<p>The Steering Committee and the Organizing Committee are responsible for the conference.</p>',
            ],
            [
                'slug' => 'keynote-speakers',
                'title' => 'Keynote Speakers',
                'module' => 'keynote_speakers',
                'body' => '<p>We are honoured to welcome the following keynote speakers.</p>',
            ],
            [
                'slug' => 'program',
                'title' => 'Program',
                'module' => 'program',
                'body' => '<p>The detailed program will be published closer to the conference.</p>',
            ],
            [
                'slug' => 'paper-submission',
                'title' => 'Paper Submission',
                'module' => 'none',
                'body' => '<h2>Full paper guidelines</h2><ul>'
                    . '<li>File name: <strong>Surname_FirstName_Paper.docx</strong></li>'
                    . '<li>Length: 3,000–5,000 words, including references.</li>'
                    . '<li>Title page: title, authors, affiliations, emails, abstract (200–250 words) and 3–5 keywords.</li>'
                    . '<li>Structure: Introduction, Literature review, Methodology, Findings and discussion, Conclusion, References.</li>'
                    . '<li>Referencing style: APA 7th edition.</li>'
                    . '<li>Font: Times New Roman 12 pt, 1.5 line spacing.</li>'
                    . '<li>Research papers must describe the research method clearly.</li>'
                    . '<li>File format: Microsoft Word (.docx).</li></ul>',
            ],
            [
                'slug' => 'sponsor',
                'title' => 'Sponsors and Partners',
                'module' => 'sponsors',
                'body' => '<p>We thank our organizers, co-organizers and sponsors.</p>',
            ],
            [
                'slug' => 'contact',
                'title' => 'Contact',
                'module' => 'contacts',
                'body' => '<p>For any questions, please contact the Organizing Committee.</p>',
            ],
            [
                'slug' => 'travel-info',
                'title' => 'Travel Info',
                'module' => 'none',
                'body' => '<h2>Location</h2><p>Hanoi Pedagogical University 2 is in Phuc Yen, about 25 km from Noi Bai International Airport '
                    . 'and 40 km from the centre of Hanoi.</p><h2>Getting there</h2><ul><li>Taxi from Noi Bai airport: about 35 minutes.</li>'
                    . '<li>Bus from Hanoi city centre: about 75 minutes.</li></ul>',
            ],
        ];
    }
}
