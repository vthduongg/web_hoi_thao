<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Nội dung trang (HTML tiếng Anh) và chương trình của VietESL 2025, dùng bởi VietEsl2025Seeder.
 * Viết lại theo https://vietesl2025.hpu2.edu.vn/ và ảnh AGENDA.png của site gốc.
 */
final class VietEsl2025Content
{
    /** @return list<array{slug: string, title: string, module: string, body: string}> */
    public static function pages(): array
    {
        return [
            ['slug' => 'call-for-papers', 'title' => 'Call for Papers', 'module' => 'none', 'body' => self::callForPapers()],
            ['slug' => 'important-dates', 'title' => 'Important Dates', 'module' => 'important_dates',
                'body' => '<p>All dates are in Vietnam time (GMT+7).</p>'],
            ['slug' => 'scientific-committee', 'title' => 'Scientific Committee', 'module' => 'committees', 'body' => ''],
            ['slug' => 'organizing-committee', 'title' => 'Organizing Committee', 'module' => 'committees', 'body' => ''],
            ['slug' => 'keynote-speakers', 'title' => 'Keynote Speakers', 'module' => 'keynote_speakers', 'body' => ''],
            ['slug' => 'program', 'title' => 'Program', 'module' => 'program',
                'body' => '<p>Conference agenda: Friday, 14 November 2025, Hanoi Pedagogical University 2.</p>'],
            ['slug' => 'paper-submission', 'title' => 'Paper Submission', 'module' => 'none', 'body' => self::paperSubmission()],
            ['slug' => 'sponsor', 'title' => 'Sponsor', 'module' => 'sponsors', 'body' => ''],
            ['slug' => 'contact', 'title' => 'Contact Us', 'module' => 'contacts',
                'body' => '<p>For conference inquiries, please email <a href="mailto:vietesl2025@hpu2.edu.vn">vietesl2025@hpu2.edu.vn</a> '
                    . 'or call (+84) 2113.863.416.</p>'],
            ['slug' => 'travel-info', 'title' => 'Travel Info', 'module' => 'none', 'body' => self::travelInfo()],
        ];
    }

    private static function callForPapers(): string
    {
        return '<h2>Key information</h2><ul>'
            . '<li><strong>Organizer:</strong> Hanoi Pedagogical University 2 (HPU2), in collaboration with the National Foreign Languages Project</li>'
            . '<li><strong>Sponsor:</strong> Regional English Language Office (RELO), U.S. Embassy in Vietnam</li>'
            . '<li><strong>Date:</strong> Friday, 14 November 2025</li>'
            . '<li><strong>Venue:</strong> Hanoi Pedagogical University 2, Xuan Hoa Ward, Phu Tho Province</li>'
            . '<li><strong>Language:</strong> English</li></ul>'
            . '<h2>Background</h2><p>In the context of globalization and the continuous development of technology, English has become an '
            . 'essential tool for communication, education and career success. Integrating ESL into Vietnamese schools creates both '
            . 'opportunities and challenges, alongside trends such as EMI, CLIL and ESP and the growing role of educational technology. '
            . 'The conference is a platform for researchers and educators to share ideas on ESL teaching.</p>'
            . '<h2>Conference themes</h2><ol>'
            . '<li><strong>Teaching and learning ESL in schools:</strong> teaching methods, student motivation, assessment, CLIL, EMI, ESP and learner autonomy.</li>'
            . '<li><strong>Curriculum, policy and teacher development:</strong> curriculum design, national reforms, teacher professional development, '
            . 'bilingual policies, the role of local languages and proficiency frameworks.</li>'
            . '<li><strong>Technology and innovation:</strong> AI, online and blended learning, gamification and MALL, digital materials, VR/AR '
            . 'and technology-supported autonomy.</li>'
            . '<li><strong>Professional development and research:</strong> in-service teacher training, communities of practice, research methodology, '
            . 'intercultural competence and outcome-based education.</li></ol>'
            . '<h2>Presentation formats</h2><ul>'
            . '<li><strong>Keynote</strong> (50 minutes): plenary talks by invited speakers.</li>'
            . '<li><strong>Oral presentation</strong> (20–30 minutes): parallel sessions on research and classroom practice.</li>'
            . '<li><strong>Poster</strong> (30 minutes): interactive sessions in the poster area.</li>'
            . '<li><strong>Workshop</strong> (45–60 minutes): hands-on sessions on teaching innovations.</li></ul>'
            . self::submissionAndFees();
    }

    private static function submissionAndFees(): string
    {
        return '<h2>Submission guidelines</h2><ul>'
            . '<li>Manuscripts must be original, written in English and not previously published. Each author may be first author of no more than two papers.</li>'
            . '<li><strong>Abstract:</strong> title, authors, affiliations, email, 3–5 keywords and an abstract of up to 250 words.</li>'
            . '<li><strong>Full paper:</strong> 4,000–7,000 words. Accepted papers are published in the conference proceedings with an ISBN.</li>'
            . '<li>Format: Microsoft Word (.docx), APA 7th edition, Times New Roman 12 pt.</li>'
            . '<li>Submit by email to <a href="mailto:vietesl2025@hpu2.edu.vn">vietesl2025@hpu2.edu.vn</a>.</li></ul>'
            . '<h2>Registration fees</h2><table><thead><tr><th>Participant</th><th>Fee</th></tr></thead><tbody>'
            . '<tr><td>International participants</td><td>25 USD</td></tr>'
            . '<tr><td>Domestic participants</td><td>500,000 VND</td></tr>'
            . '<tr><td>Doctoral students</td><td>250,000 VND</td></tr>'
            . '<tr><td>Graduate and undergraduate students</td><td>Free</td></tr></tbody></table>'
            . '<p>The fee covers conference materials, administration, refreshments and one lunch. '
            . 'Register at <a href="https://tinyurl.com/mrx6kbm3">https://tinyurl.com/mrx6kbm3</a>.</p>'
            . '<h2>Publication fee (accepted full papers)</h2><ul><li>International authors: 100 USD per paper</li>'
            . '<li>Domestic authors: 1,500,000 VND per paper</li></ul>'
            . '<h2>Payment</h2><ul><li>Account name: HANOI PEDAGOGICAL UNIVERSITY 2</li><li>Account number: 4260276664</li>'
            . '<li>Bank: BIDV, Phuc Yen Branch</li>'
            . '<li>Transfer note: [Full name]_[Paper ID]_vietesl2025_thamdu (registration) or [Full name]_[Paper ID]_vietesl2025_xuatban (publication)</li></ul>'
            . '<p>Please email the payment receipt to vietesl2025@hpu2.edu.vn after the transfer.</p>'
            . '<h2>Visa support</h2><p>International participants may request an invitation letter from the Organizing Committee for their visa application.</p>';
    }

    private static function paperSubmission(): string
    {
        return '<h2>Full paper guidelines</h2><ul>'
            . '<li><strong>File name:</strong> FullNameOfAuthors_2WordsStartingTitle, e.g. <em>Nguyen_Van_A_Implementing_Technology.docx</em></li>'
            . '<li><strong>Length:</strong> 4,000–7,000 words.</li>'
            . '<li><strong>Title page:</strong> manuscript title, author names and affiliations, and contact details of the corresponding author.</li>'
            . '<li><strong>Structure:</strong> abstract (up to 250 words) and 4–6 keywords; introduction, literature review, methodology, '
            . 'findings, discussion, conclusion and references.</li>'
            . '<li><strong>Citations:</strong> APA 7th edition.</li>'
            . '<li><strong>Formatting:</strong> Times New Roman 12 pt, double spacing, 1-inch margins on all sides.</li>'
            . '<li><strong>Research design:</strong> state the problem, objectives and research questions; describe sampling, instruments, '
            . 'protocols and analysis in detail; relate findings to existing literature; discuss implications, ethical considerations and limitations.</li>'
            . '<li><strong>File format:</strong> Microsoft Word (.docx).</li></ul>'
            . '<p>Send your paper to <a href="mailto:vietesl2025@hpu2.edu.vn">vietesl2025@hpu2.edu.vn</a>. '
            . 'See <a href="/important-dates">Important Dates</a> for deadlines.</p>';
    }

    private static function travelInfo(): string
    {
        return '<h2>Venue</h2><p>Hanoi Pedagogical University 2 (HPU2), Xuan Hoa Ward, Phu Tho Province, Vietnam.</p>'
            . '<div class="embed-responsive"><iframe src="https://www.google.com/maps/embed?pb=!1m2!2m1!1sHanoi+Pedagogical+University+2" '
            . 'title="Map of Hanoi Pedagogical University 2" width="560" height="315"></iframe></div>'
            . '<h2>Getting there</h2><ul><li>About 45–60 minutes by road from central Hanoi.</li>'
            . '<li>About 20 km from Noi Bai International Airport.</li>'
            . '<li>The university is near National Highway 2 and the Hanoi – Lao Cai Expressway: you can travel by car, taxi or ride-hailing services such as Grab.</li>'
            . '<li>Local buses and electric vehicles connect HPU2 with nearby urban areas and tourist sites.</li></ul>'
            . '<h2>Places to visit</h2><ul>'
            . '<li><strong>Tam Dao</strong> (about 40 km): a mountain town with a stone church, Silver Waterfall and the Heaven\'s Gate viewpoint.</li>'
            . '<li><strong>Dai Lai Lake</strong> (about 15 km): boat tours, an art forest and outdoor activities.</li>'
            . '<li><strong>Tay Thien Zen Monastery</strong> (about 35 km): a Buddhist centre reached partly by cable car.</li>'
            . '<li><strong>Huong Canh pottery village</strong> (about 12 km): handcrafted ceramics and pottery workshops.</li></ul>';
    }

    /**
     * Chương trình ngày 14/11/2025: [tên phiên, phòng, [[bắt đầu, kết thúc, nội dung, người trình bày, phòng riêng]]].
     *
     * Hàm dài hơn 40 dòng có chủ đích (constitution, Nguyên tắc I): chỉ là bảng dữ liệu chép từ ảnh AGENDA.png.
     *
     * @return list<array{0: string, 1: string, 2: list<array{0: string, 1: string, 2: string, 3: ?string, 4: ?string}>}>
     */
    public static function programSessions(): array
    {
        $a1 = 'Grand Hall, Building A1';

        return [
            ['Opening and keynote presentations', $a1, [
                ['08:00', '08:30', 'Registration', null, null],
                ['08:30', '08:50', 'Opening remarks and introduction of guests; welcome speech by the representative of Hanoi Pedagogical University 2; '
                    . 'introductory remarks by the representative of the National Foreign Languages Project', 'MC', null],
                ['08:50', '09:40', 'Keynote presentation 1: ESL 4.0: Enhancing English Learning through Integration of AI and Audio Technologies '
                    . '(Chair: Dr. Nguyen Thi Ha Anh)', 'Prof. Alandeon W. Oliveira', null],
                ['09:40', '10:30', 'Keynote presentation 2: Transforming ESL Education in Southeast Asia: Innovation, Collaboration, and Capacity '
                    . 'Building for a Multilingual Education (Chair: Dr. Tran Ngoc Giang)', 'Dr. Sedighe Zamani Roodsari', null],
                ['10:30', '11:00', 'Tea break and poster presentation', null, null],
            ]],
            ['Strand 1: Professional Development (Chair: Dr. Cao Thi Hong Phuong)', $a1, [
                ['11:00', '11:20', 'Designing Context-Responsive TESOL Teacher Education in Vietnam: Insights from a National Needs Analysis', 'Cao Thi Hong Phuong', null],
                ['11:20', '11:40', 'Learning to Teach with AI: Pre-Service ESL Teachers\' Professional Development Experiences', 'Tran Ngoc Giang', null],
                ['11:40', '12:00', 'Teaching to differences: differentiated instruction strategies of award-winning EFL teachers in the 2024 Ninh Binh '
                    . 'provincial contest – a multiple case study', 'Mai Thi Thu Han, Bui Thi Van Anh', null],
            ]],
            ['Strand 2: Materials and Curriculum Development (Chair: Dr. Dang Thi Huong Thao)', 'Meeting room 2, Building A1', [
                ['11:00', '11:20', 'Evaluating ESP Programs for Non-English Majors: A Case Study from a Vietnamese Higher Education Institution',
                    'Vo Doan Tho, Bui My Ngoc, Nguyen Phu Quynh Nhu', null],
                ['11:20', '11:40', 'The Impact of the Flipped Classroom Model on Learning English Speaking Skills',
                    'Dang Thi Huong Thao, Tran Minh Tam, Tran Lan Phuong, Bui Hai Long', null],
                ['11:40', '12:00', 'A critical discourse analysis of speeches on climate change by the representatives of the United States and India '
                    . 'at the COP20 United Nations Conventions', 'Do Thi Phong Lan', null],
            ]],
            ['Strand 3: Computer-Assisted Language Learning (Chair: Dr. Tran Huong Quynh)', 'Room 703, Building A1', [
                ['11:00', '11:20', 'Learning goals, motivation, and the intercultural gap: A case study of a successful Vietnamese EFL learner', 'Trinh Thi Anh Hang', null],
                ['11:20', '11:40', 'The Role of Artificial Intelligence (AI) in Supporting Teachers of English in Vietnam', 'Pham Thi To Nhu, Nguyen Huu Anh Vuong', null],
                ['11:40', '12:00', 'The Perceptions of Teachers about the Integration of Artificial Intelligence in English Language Teaching and Learning '
                    . 'at a Gifted High School', 'Nguyen Thi Ha Anh, Luu Van Truong, Nguyen Hong Nhung', null],
            ]],
            ['Strand 4: Computer-Assisted Language Learning (Chair: Dr. Vuong Thi Hai Yen)', 'Room 706, Building A1', [
                ['11:00', '11:20', 'Redefining Teacher Roles in the Age of Artificial Intelligence from a Communicative Language Teaching Perspective',
                    'Nguyen Bich Dieu, Huynh Ngoc Mai Kha', null],
                ['11:20', '11:40', 'Integrating AI in English for Specific Purposes (ESP) Instruction for English', 'Vuong Thi Hai Yen', null],
                ['11:40', '12:00', 'Developing CLIL-Based Grade 11 Geography Videos with English Subtitles and Sign Language for Inclusive Education',
                    'Nguyen Thi Thu Hang, Nguyen Thi Thu Huyen, Vu Thi Lan Huong, Le Thi Phuong Chi, Duong Huyen My', null],
            ]],
            ['Strand 5: ESL Teaching and Learning (Chair: Dr. Nguyen Thi Huong Lan)', 'Meeting room 2, Building A2', [
                ['11:00', '11:20', 'Translanguaging in Vietnamese High Schools: A Pilot Study of Beliefs, Practices, and Instrument Refinement for '
                    . 'Participatory Action Research', 'Huong Vu, Eric A. Ambele, Pilanut Phusawisot', null],
                ['11:20', '11:40', 'The Multifaceted Challenges and Impediments to Implementing English as a Medium of Instruction for Mathematics '
                    . 'at Hanoi Pedagogical University 2', 'Pham Thanh Tuan', null],
                ['11:40', '12:00', 'CLIL in Mathematics teaching: A voice from experts', 'Dao Van Yen, Dang Luu Ngoc Hoa, Ngo Thi Thanh Huyen', null],
            ]],
            ['Afternoon', $a1, [
                ['12:00', '13:30', 'Lunch break', null, null],
                ['13:30', '14:00', 'Poster presentation', null, null],
                ['14:00', '16:00', 'Workshop 1: English Medium Instruction (EMI) for University Instructors '
                    . '(Moderator: Dr. Nguyen Thi Ha Anh; Facilitator: Tran Thi Ngan)', 'Prof. Alandeon W. Oliveira', $a1],
                ['14:00', '16:00', 'Workshop 2: Integrating CLIL for Effective Content and Language Teaching: Theory, Practice, and Lesson Design '
                    . '(Moderator: Dr. Vuong Thi Hai Yen; Facilitator: Nguyen Thi Thu Thuy)', 'Dr. Sedighe Zamani Roodsari', 'Meeting room 2, Building A1'],
            ]],
        ];
    }
}
