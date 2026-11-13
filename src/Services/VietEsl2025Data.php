<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Nội dung thật của VietESL 2025 (https://vietesl2025.hpu2.edu.vn/), dùng bởi VietEsl2025Seeder.
 *
 * Chỉ là dữ liệu tĩnh, không có logic. Những chỗ hai trang của site gốc ghi khác nhau, lấy theo trang
 * Call for Papers (bản cập nhật sau): thông báo chấp nhận tóm tắt 27/10, hạn nộp toàn văn gia hạn đến 05/11.
 */
final class VietEsl2025Data
{
    public const YEAR = 2025;

    /** Thư mục ảnh (logo, ảnh diễn giả, ảnh chương trình) tính từ gốc dự án. */
    public const IMAGE_DIR = 'database/seed-data/vietesl2025';

    public const CONFERENCE = [
        'name' => 'VietESL International Conference 2025',
        'theme' => 'Practices, Opportunities and Challenges',
        'year' => self::YEAR,
        'venue' => 'Hanoi Pedagogical University 2, Xuan Hoa Ward, Phu Tho Province, Vietnam',
        'conference_language' => 'English',
        'public_language' => 'en',
        'contact_email' => 'vietesl2025@hpu2.edu.vn',
        'contact_phone' => '+84 2113 863 416',
        'footer_text' => 'Hanoi Pedagogical University 2, in collaboration with the National Foreign Languages Project, Ministry of Education and Training',
        'copyright_text' => '© 2025 HPU2',
        'home_intro_html' => '<p>The VietESL International Conference 2025 is organized by Hanoi Pedagogical University 2 (HPU2) '
            . 'in collaboration with the National Foreign Languages Project and sponsored by the Regional English Language Office (RELO), '
            . 'U.S. Embassy in Vietnam.</p>'
            . '<p>The conference is a forum for researchers, educators, policymakers and teachers to share research and practice in '
            . 'teaching English as a second language, with a focus on pedagogical innovation, policy and technology.</p>',
    ];

    /** [nhãn, trạng thái, ngày, ngày cũ (khi gia hạn), vai trò đặc biệt] */
    public const IMPORTANT_DATES = [
        ['First announcement', 'normal', '2025-07-25', null, null],
        ['Second announcement', 'normal', '2025-09-30', null, null],
        ['Abstract submission deadline', 'normal', '2025-10-15', null, 'abstract_deadline'],
        ['Abstract acceptance notification', 'normal', '2025-10-27', null, null],
        ['Participant registration deadline (5:00 PM)', 'normal', '2025-10-30', null, null],
        ['Full paper submission deadline', 'extended', '2025-11-05', '2025-10-30', 'full_paper_deadline'],
        ['Full paper acceptance notification', 'tba', null, null, null],
        ['Conference date', 'normal', '2025-11-14', null, 'conference_day'],
    ];

    /** [tên ban, slug trang hiển thị, [[học hàm học vị, họ tên, chức vụ và đơn vị, vai trò]]] */
    public const COMMITTEES = [
        ['Steering Committee', 'organizing-committee', [
            ['Assoc. Prof. Dr.', 'Nguyen Quang Huy', 'Rector, Hanoi Pedagogical University 2', 'Chairperson'],
            ['Dr.', 'Nguyen Thi Mai Huu', 'Director, National Foreign Languages Project, Ministry of Education and Training', 'Vice Chairperson'],
            ['Dr.', 'Cao Ba Cuong', 'Vice Rector, Hanoi Pedagogical University 2', 'Vice Chairperson'],
            ['Dr.', 'Hoang Ngoc Tuan', 'Head, Office of Science, Technology and International Cooperation, Hanoi Pedagogical University 2', 'Member'],
        ]],
        ['Organizing Committee', 'organizing-committee', [
            ['Dr.', 'Cao Ba Cuong', 'Vice Rector, Hanoi Pedagogical University 2', 'Chairperson'],
            ['Dr.', 'Hoang Ngoc Tuan', 'Head, Office of Science, Technology and International Cooperation, Hanoi Pedagogical University 2', 'Vice Chairperson'],
            ['Dr.', 'Nguyen Thi Ha Anh', 'Deputy Head, Faculty of English, Hanoi Pedagogical University 2', 'Permanent Member'],
            ['M.A.', 'Le Thi Hang', 'National Foreign Languages Project Management Board, Ministry of Education and Training', 'Member'],
            ['Assoc. Prof. Dr.', 'Nguyen Xuan Thanh', 'Head, Faculty of Biology, Hanoi Pedagogical University 2', 'Member'],
            ['Assoc. Prof. Dr.', 'Nguyen Thu Huong', 'Head, Faculty of Preschool Education, Hanoi Pedagogical University 2', 'Member'],
            ['Dr.', 'Nguyen Thi Tuyet Minh', 'Head, Faculty of Primary Education, Hanoi Pedagogical University 2', 'Member'],
            ['Dr.', 'Tran Van Bang', 'Head, Faculty of Mathematics, Hanoi Pedagogical University 2', 'Member'],
            ['Assoc. Prof. Dr.', 'Dao Thi Viet Anh', 'Head, Faculty of Chemistry, Hanoi Pedagogical University 2', 'Member'],
            ['Assoc. Prof. Dr.', 'Nguyen Huy Thao', 'Head, Faculty of Physics, Hanoi Pedagogical University 2', 'Member'],
            ['Dr.', 'Nguyen Huy Hung', 'Head, Office of Organization and Administration Affairs, Hanoi Pedagogical University 2', 'Member'],
            ['M.A.', 'Nguyen Hoang Ngoc', 'Head, Office of Planning and Finance, Hanoi Pedagogical University 2', 'Member'],
            ['Dr.', 'Tran Vu Khanh', 'Deputy Head, Office of Academic Affairs, Hanoi Pedagogical University 2', 'Member'],
            ['M.A.', 'Tran Thanh Trung', 'Deputy Director, Information Technology Center, Hanoi Pedagogical University 2', 'Member'],
            ['Dr.', 'Nguyen Van Duong', 'Deputy Head, Office of Political and Student Affairs, Hanoi Pedagogical University 2', 'Member'],
            ['M.A.', 'Nguyen Van Dung', 'Deputy Head, Office of Facility Management, Hanoi Pedagogical University 2', 'Member'],
        ]],
        ['Scientific Committee', 'scientific-committee', [
            ['Dr.', 'Nguyen Thi Ha Anh', 'Deputy Head, Faculty of English, Hanoi Pedagogical University 2', 'Chairperson'],
            ['Dr.', 'Nguyen Thi Hong Nhat', 'Head, Faculty of English, Hanoi Pedagogical University 2', 'Vice Chairperson'],
            ['M.A.', 'Vu Thi Lua', 'National Foreign Languages Project, Ministry of Education and Training', 'Member'],
            ['Prof.', 'Alandeon Oliveira', 'University at Albany, USA', 'Member'],
            ['Dr.', 'Sedighe Zamani Roodsari', 'Auburn University, USA', 'Member'],
            ['Dr.', 'Christoffer Conrad Marcial Tejada', 'Batangas State University, the Philippines', 'Member'],
            ['Dr.', 'Alice A. Cabingan', 'Dean, College of Teacher Education, Batangas State University, the Philippines', 'Member'],
            ['Dr.', 'Ngo Thi Thanh Huyen', 'Head, Division of English Language and Culture, Faculty of English, Hung Vuong University', 'Member'],
            ['Dr.', 'Vuong Thi Hai Yen', 'Head, Faculty of Foreign Languages, Hanoi Metropolitan University', 'Member'],
            ['Dr.', 'Dang Ngoc Nam', 'Lecturer, Faculty of English, Hanoi Pedagogical University 2', 'Member'],
            ['Dr.', 'Nguyen Thi Phuong Lan', 'Lecturer, Faculty of English, Hanoi Pedagogical University 2', 'Member'],
            ['M.A.', 'Luu Thi Huong', 'Lecturer, Faculty of English, Hanoi Pedagogical University 2', 'Member'],
            ['M.A.', 'Do Thi Thanh Dung', 'Lecturer, Faculty of English, Hanoi Pedagogical University 2', 'Member'],
        ]],
    ];

    /** [ảnh, học hàm học vị, họ tên, đơn vị, quốc gia, tên bài trình bày] */
    public const SPEAKERS = [
        ['speaker-1.jpg', 'Prof.', 'Alandeon W. Oliveira', 'University at Albany', 'USA',
            'ESL 4.0: Enhancing English Learning through Integration of AI and Audio Technologies'],
        ['speaker-2.jpg', 'Dr.', 'Sedighe Zamani Roodsari', 'Auburn University', 'USA',
            'Transforming ESL Education in Southeast Asia: Innovation, Collaboration, and Capacity Building for a Multilingual Education'],
        ['speaker-3.jpg', 'Dr.', 'Kristoffer Conrad Marcial Tejada', 'University of Batangas', 'Philippines', null],
    ];

    /** [hạng, tên, tệp logo, website] */
    public const SPONSORS = [
        ['Organizers', 'Hanoi Pedagogical University 2', 'hpu2-logo.png', 'https://hpu2.edu.vn'],
        ['Organizers', 'National Foreign Languages Project, Ministry of Education and Training', 'nflp-logo.png', null],
        ['Sponsor', 'Regional English Language Office (RELO), U.S. Embassy Hanoi', 'relo-us-embassy.jpg', null],
    ];

    /** [họ tên, đơn vị, email, điện thoại] */
    public const CONTACTS = [
        ['Dr. Dang Ngoc Nam', 'Hanoi Pedagogical University 2', 'dangngocnam@hpu2.edu.vn', '0979 200 418'],
        ['Ms. Nguyen Thi Yen', 'Hanoi Pedagogical University 2', 'nguyenthiyen@hpu2.edu.vn', '0358 066 386'],
    ];

    /** [tiêu đề, mô tả ngắn, link] */
    public const HIGHLIGHTS = [
        ['Keynote speakers', 'Plenary talks by speakers from the University at Albany, Auburn University and the University of Batangas.', '/keynote-speakers'],
        ['Four presentation formats', 'Keynotes, oral presentations in five parallel strands, poster sessions and hands-on workshops.', '/program'],
        ['Registration fees', 'International 25 USD, domestic 500,000 VND, doctoral students 250,000 VND, graduate and undergraduate students free.', '/call-for-papers'],
    ];

    /** [nhãn, slug trang (null = mục cha không có trang), mục con] */
    public const MENU = [
        ['Conference', null, [
            ['Call for Papers', 'call-for-papers'],
            ['Important Dates', 'important-dates'],
            ['Scientific Committee', 'scientific-committee'],
            ['Organizing Committee', 'organizing-committee'],
            ['Keynote Speakers', 'keynote-speakers'],
        ]],
        ['Program', 'program', []],
        ['Paper Submission', 'paper-submission', []],
        ['Sponsor', 'sponsor', []],
        ['Contact', 'contact', []],
        ['Travel Info', 'travel-info', []],
    ];
}
