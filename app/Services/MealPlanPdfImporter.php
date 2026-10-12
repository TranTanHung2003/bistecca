<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Smalot\PdfParser\Parser;
use RuntimeException;
use SimpleXMLElement;

class MealPlanPdfImporter
{
    public function import(UploadedFile $file): array
    {
        if (strtolower($file->getClientOriginalExtension()) === 'xml') {
            return $this->importXml($file->getRealPath());
        }

        $pdf = (new Parser())->parseFile($file->getRealPath());
        $text = $pdf->getText();

        try {
            return $this->importText($text);
        } catch (RuntimeException $exception) {
            $rows = $this->parsePdfRowsByCoordinates($pdf);

            if ($rows === []) {
                throw $exception;
            }

            $dateMatch = [];
            $cleanText = iconv('UTF-8', 'UTF-8//IGNORE', $text) ?: $text;
            preg_match('/MEAL PLAN reports ON\s+(\d{2}\/\d{2}\/\d{4})/i', $cleanText, $dateMatch);

            if (! isset($dateMatch[1])) {
                throw $exception;
            }

            if (preg_match('/Total\s*:\s*(\d+)\s+(\d+)/i', $cleanText, $totalMatch)) {
                $expectedRows = (int) $totalMatch[1] + (int) $totalMatch[2];

                if (count($rows) !== $expectedRows) {
                    throw new RuntimeException('PDF vẫn chưa tách đủ khách: nhận ' . count($rows) . '/' . $expectedRows . ' khách.');
                }
            }

            return $this->storeRows(Carbon::createFromFormat('d/m/Y', $dateMatch[1])->toDateString(), $rows);
        }
    }

    public function importXml(string $path): array
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($path);

        if (! $xml instanceof SimpleXMLElement) {
            throw new RuntimeException('File XML không hợp lệ hoặc không thể đọc được.');
        }

        $reportText = (string) ($xml->xpath('//*[local-name()="FormattedReportObject"]/*[local-name()="TextValue"]')[0] ?? '');

        if (! preg_match('/MEAL PLAN reports ON\s+(\d{2}\/\d{2}\/\d{4})/i', $reportText, $dateMatch)) {
            throw new RuntimeException('Không tìm thấy ngày báo cáo trong file XML.');
        }

        $rows = [];
        foreach ($xml->xpath('//*[local-name()="FormattedAreaPair"][@Level="1"][@Type="Details"]') ?: [] as $detail) {
            $values = [];
            foreach ($detail->xpath('.//*[local-name()="FormattedReportObject"]') ?: [] as $object) {
                $fieldName = (string) ($object['FieldName'] ?? '');
                $fieldName = trim($fieldName, '{}');
                $dotPosition = strrpos($fieldName, '.');
                $key = strtolower($dotPosition === false ? ltrim($fieldName, '@') : substr($fieldName, $dotPosition + 1));
                $value = trim((string) ($object->FormattedValue ?? ''));

                if ($key !== '') {
                    $values[$key] = $value;
                }
            }

            if (($values['name'] ?? '') === '' || ($values['roomcode'] ?? '') === '') {
                continue;
            }

            $adult = (int) ($values['numadult'] ?? 0);
            $child = (int) ($values['numchild'] ?? 0);
            $rows[] = [
                'room' => $values['roomcode'],
                'room_type' => $values['roomtypecode'] ?? '',
                'guest_count' => isset($values['numguest']) && $values['numguest'] !== '' ? (int) $values['numguest'] : null,
                'guest_name' => $this->repairVietnamese($values['name']),
                'birthday' => $this->xmlOptionalDate($values['birthdate'] ?? ''),
                'arrival' => $this->xmlDate($values['arrivaltime'] ?? ''),
                'departure' => $this->xmlDate($values['departuretime'] ?? ''),
                'nights' => (int) ($values['night'] ?? 0),
                'travel_agent' => $this->repairVietnamese($values['clientname'] ?? ''),
                'rate_code' => $values['roomratecode'] ?? null,
                'nationality' => $this->repairVietnamese($values['qtich'] ?? ''),
                'meal_plan' => $values['breakfast'] ?? '',
                'breakfast_code' => $values['breakfastcode'] ?? null,
                'is_child' => $child > 0 && $adult === 0,
                'source_line' => 'XML: ' . $values['roomcode'] . ' - ' . $values['name'],
            ];
        }

        if ($rows === []) {
            throw new RuntimeException('Không tìm thấy bản ghi khách trong file XML.');
        }

        return $this->storeRows(Carbon::createFromFormat('d/m/Y', $dateMatch[1])->toDateString(), $rows);
    }

    private function xmlDate(string $value): string
    {
        if ($value === '') {
            throw new RuntimeException('Một bản ghi XML thiếu ngày đến hoặc ngày đi.');
        }

        return Carbon::createFromFormat('d/m/Y', $value)->toDateString();
    }

    private function xmlOptionalDate(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('d/m/Y', $value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    public function importText(string $text): array
    {
        if (! preg_match('/MEAL PLAN reports ON\s+(\d{2}\/\d{2}\/\d{4})/i', $text, $dateMatch)) {
            throw new RuntimeException('Không tìm thấy ngày báo cáo trong file PDF.');
        }

        $reportDate = Carbon::createFromFormat('d/m/Y', $dateMatch[1])->toDateString();
        $rows = $this->parseRows($text);

        if ($rows !== [] && preg_match('/Total\s*:\s*(\d+)\s+(\d+)/i', $text, $totalMatch)) {
            $expectedRows = (int) $totalMatch[1] + (int) $totalMatch[2];

            if (count($rows) !== $expectedRows) {
                throw new RuntimeException('PDF được đọc thiếu dòng: nhận ' . count($rows) . '/' . $expectedRows . ' khách.');
            }
        }

        if ($rows === []) {
            $cleanText = trim(preg_replace('/\s+/', ' ', $text));
            $roomCount = preg_match_all('/\b\d{3}\b/', $cleanText);
            $preview = function_exists('mb_substr') ? mb_substr($cleanText, 0, 180) : substr($cleanText, 0, 180);

            throw new RuntimeException(
                'PDF đã đọc được ' . strlen($text) . ' ký tự và ' . $roomCount . ' mã phòng, nhưng không khớp cấu trúc dòng khách. Phần đầu text đọc được: ' . ($preview ?: '[trống]')
            );
        }

        return $this->storeRows($reportDate, $rows);
    }

    private function storeRows(string $reportDate, array $rows): array
    {
        DB::transaction(function () use ($reportDate, $rows): void {
            DB::table('meal_plan_guests')->whereDate('report_date', $reportDate)->delete();
            DB::table('meal_plan_guests')->insert(array_map(function (array $row) use ($reportDate): array {
                return $row + ['report_date' => $reportDate, 'created_at' => now(), 'updated_at' => now()];
            }, $rows));
        });

        return ['report_date' => $reportDate, 'rows' => count($rows)];
    }

    private function parsePdfRowsByCoordinates(object $pdf): array
    {
        $lines = [];

        foreach ($pdf->getPages() as $page) {
            $groups = [];

            foreach ($page->getDataTm() as $item) {
                if (! isset($item[0][4], $item[0][5], $item[1]) || trim((string) $item[1]) === '') {
                    continue;
                }

                $yBucket = number_format(round((float) $item[0][5], 1), 1, '.', '');
                $groups[$yBucket][] = ['x' => (float) $item[0][4], 'text' => trim((string) $item[1])];
            }

            foreach ($groups as &$group) {
                usort($group, fn (array $left, array $right): int => $left['x'] <=> $right['x']);
                $lines[] = implode(' ', array_column($group, 'text'));
            }
            unset($group);
        }

        $rows = [];

        foreach ($lines as $line) {
            $rows = array_merge($rows, $this->parseRows($line));
        }

        return $rows;
    }

    public function parseRows(string $text): array
    {
        $rows = [];
        $text = iconv('UTF-8', 'UTF-8//IGNORE', $text) ?: $text;
        $normalizedText = trim(preg_replace('/\s+/', ' ', $text));
        $normalizedText = preg_replace('/\bB\s+reakfast\b/i', 'Breakfast', $normalizedText);
        $pattern = '~(?:^|\s)(\d{3})\s+(\S+)(?:\s+(\d+))?\s+(.+?)\s*(\d{2}/\d{2}/\d{4})\s+(\d{2}/\d{2}/\d{4})\s+(\d+)\s+(.+?)\s+(Breakfast Include|No Breakfast)\s+(B\d+)\s+(\d+)\s+(\d+)(?=\s|$)~u';

        preg_match_all($pattern, $normalizedText, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $line = trim($match[0]);

            $details = $this->parseDetails($match[8]);
            $rows[] = [
                'room' => $match[1],
                'room_type' => $match[2],
                'guest_count' => $match[3] === '' ? null : (int) $match[3],
                'guest_name' => $this->repairVietnamese($match[4]),
                'arrival' => Carbon::createFromFormat('d/m/Y', $match[5])->toDateString(),
                'departure' => Carbon::createFromFormat('d/m/Y', $match[6])->toDateString(),
                'nights' => (int) $match[7],
                'travel_agent' => $details['travel_agent'],
                'rate_code' => $details['rate_code'],
                'nationality' => $details['nationality'],
                'meal_plan' => $match[9],
                'breakfast_code' => $match[10],
                'is_child' => (bool) ((int) $match[12]),
                'source_line' => $line,
            ];
        }

        return $rows;
    }

    private function parseDetails(string $details): array
    {
        $countries = 'Korea Republic of|Russian Federation|Taiwan Province of China|Netherlands Antilles|United States|Viet Nam|Philippines|Australia|Malaysia|Thailand|Ukraine|Canada|China|Japan|Germany|Singapore';

        if (preg_match('~^(.+?)\s+(STR|CAKE)\s+(' . $countries . ')(?:\s|$)~u', $details, $match)) {
            return [
                'travel_agent' => $this->repairVietnamese(trim($match[1])),
                'rate_code' => $match[2],
                'nationality' => $this->repairVietnamese($match[3]),
            ];
        }

        if (preg_match('~^(.+?)\s+(' . $countries . ')(?:\s|$)~u', $details, $match)) {
            return [
                'travel_agent' => $this->repairVietnamese(trim($match[1])),
                'rate_code' => null,
                'nationality' => $this->repairVietnamese($match[2]),
            ];
        }

        return ['travel_agent' => $this->repairVietnamese($details), 'rate_code' => null, 'nationality' => null];
    }

    private function repairVietnamese(string $value): string
    {
        $value = str_replace(["\u{00AD}", '\u{00AD}'], '', $value);

        $replacements = [
            'Bµnh §¾c Lîi' => 'Bình Đắc Lợi',
            'Lª Huúnh Kim Ng©n' => 'Lê Huỳnh Kim Ngân',
            'HOµNG TRUNG KI£N' => 'HOÀNG TRUNG KIÊN',
            'NguyÔn V¨n Ch©m' => 'Nguyễn Văn Châm',
            'Phïng ThÞ Thu Yªn' => 'Phùng Thị Thu Yên',
            'tRÇN nHËT hOµNG yÕN' => 'TRẦN NHẬT HOÀNG YẾN',
            'tRẦN nHẬT hOÀNG yẾN' => 'TRẦN NHẬT HOÀNG YẾN',
            '§ç tR¬NG yÕN nHI' => 'Đỗ Trương Yến Nhi',
            '§ç tR\u{AD}¬NG yÕN nHI' => 'Đỗ Trương Yến Nhi',
            '§c tR¬NG yÕN nHI' => 'Đỗ Trương Yến Nhi',
            'Bµnh' => 'Bình', '§¾c' => 'Đắc', 'Lîi' => 'Lợi',
            'Huúnh' => 'Huỳnh', 'Hoµng' => 'Hoàng', 'Ki£n' => 'Kiên',
            'Phïng' => 'Phùng', 'Yªn' => 'Yên', 'Ch©m' => 'Châm',
            'nGUYÔN qUANG cHIÕN' => 'nGUYỄN qUANG cHIẾN',
            'NguyÔn ThÞ HiÕu Kiªn' => 'Nguyễn Thị Hiếu Kiên',
            'Lª V¨n Nguyªn' => 'Lê Văn Nguyên',
            'Lª Quèc Anh' => 'Lê Quốc Anh',
            'lª tHÞ tIÕN' => 'lê tHỊ tIẾN',
            'Bïi Ngäc YÕn Linh' => 'Bùi Ngọc Yến Linh',
            'Lª Xu©n Phóc' => 'Lê Xuân Phúc',
            'Bïi Duy H­ng' => 'Bùi Duy Hưng',
            'Cao V­¬ng Hoµng Thôy Tr©m Anh' => 'Cao Vương Hoàng Thùy Trâm Anh',
            'Lª Ngäc Huy' => 'Lê Ngọc Huy',
            'V­¬ng Mü HËu' => 'Vương Mỹ Hậu',
            'Lª Ngäc Anh Khoa' => 'Lê Ngọc Anh Khoa',
            'Lª Ngäc Anh Duy' => 'Lê Ngọc Anh Duy',
            'TrÇn ThÞ Ba' => 'Trần Thị Ba',
            'Phan Ngäc Anh Th­' => 'Phan Ngọc Anh Thư',
            'Huúnh ThÞ Mü DiÖu' => 'Huỳnh Thị Mỹ Diệu',
            'NguyÔn Phan Duy TÝn' => 'Nguyễn Phan Duy Tín',
            'TrÇn ThÞ Thïy Giao GIAO' => 'Trần Thị Thùy Giao GIAO',
            'TrÞnh C«ng §øc Duy' => 'Trịnh Công Đức Duy',
            'Ung Ngäc Thanh T©m' => 'Ung Ngọc Thanh Tâm',
            'NguyÔn ViÖt H¶i' => 'Nguyễn Việt Hải',
            'L©m ThÞ Hång Loan' => 'Lâm Thị Hồng Loan',
            'NguyÔn TriÖu Nghi' => 'Nguyễn Triều Nghi',
            'DiÖp Thanh Nghiªm' => 'Diệp Thanh Nghiêm',
            'DiÖp H¶i QuyÒn' => 'Diệp Hải Quyền',
            'DiÖp L©m Khang' => 'Diệp Lâm Khang',
            'DiÖp L©m Huy' => 'Diệp Lâm Huy',
            'DiÖp Minh Th­' => 'Diệp Minh Thư',
            'nGUYÔN' => 'nGUYỄN', 'NguyÔn' => 'Nguyễn', 'Nguyªn' => 'Nguyên',
            'KiÒu' => 'Kiều', 'Vò' => 'Võ', 'Ng©n' => 'Ngân', 'Lª' => 'Lê',
            'V¨n' => 'Văn', 'Bïi' => 'Bùi', 'ThÞ' => 'Thị', 'LiÕn' => 'Liên',
            'Ngäc' => 'Ngọc', 'V­¬ng' => 'Vương', 'Mü' => 'Mỹ', 'HËu' => 'Hậu',
            'TrÇn' => 'Trần', 'Phóc' => 'Phúc', 'H¶i' => 'Hải', 'Quang L©u' => 'Quang Lâu',
            'T©m' => 'Tâm', 'H¶i QuyÒn' => 'Hải Quyền', 'L©m Khang' => 'Lâm Khang',
            'Minh Th­' => 'Minh Thư', 'Anh Th­' => 'Anh Thư', 'Nghiªm' => 'Nghiêm',
            'TÝn' => 'Tín', 'Thïy' => 'Thùy', 'Giao GIAO' => 'Giao GIAO',
            'Kh¾c' => 'Khắc', 'Tr¬ng' => 'Trọng', '§øc' => 'Đức', 'B¶n' => 'Bản',
            'nG«' => 'Ngọc', 'tHÞ' => 'Thị', 'lAN' => 'Lan', 'uYªN' => 'Uyên',
            'TR¦¥NG' => 'Trương', 'MINH' => 'Minh', 'NGäC' => 'Ngọc',
        ];

        return trim(strtr($value, $replacements));
    }
}
