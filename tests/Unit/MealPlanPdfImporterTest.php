<?php

namespace Tests\Unit;

use App\Services\MealPlanPdfImporter;
use App\Models\MealPlanGuest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MealPlanPdfImporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_crystal_reports_xml_records(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'meal-plan-') . '.xml';
        file_put_contents($path, <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<FormattedReport xmlns="urn:crystal-reports:schemas" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <FormattedReportObject xsi:type="CTFormattedText"><TextValue>MEAL PLAN reports ON 23/09/2026</TextValue></FormattedReportObject>
    <FormattedAreaPair Level="1" Type="Details"><FormattedReportObjects>
        <FormattedReportObject FieldName="{@Name}"><FormattedValue>Lª Huúnh Kim Ng©n</FormattedValue></FormattedReportObject>
        <FormattedReportObject FieldName="{rptBrefast.BirthDate}"><FormattedValue>29/04/1988</FormattedValue></FormattedReportObject>
        <FormattedReportObject FieldName="{rptBrefast.RoomCode}"><FormattedValue>314</FormattedValue></FormattedReportObject>
        <FormattedReportObject FieldName="{rptBrefast.ArrivalTime}"><FormattedValue>22/09/2026</FormattedValue></FormattedReportObject>
        <FormattedReportObject FieldName="{rptBrefast.DepartureTime}"><FormattedValue>25/09/2026</FormattedValue></FormattedReportObject>
        <FormattedReportObject FieldName="{rptBrefast.NumAdult}"><FormattedValue>1</FormattedValue></FormattedReportObject>
        <FormattedReportObject FieldName="{rptBrefast.NumChild}"><FormattedValue>0</FormattedValue></FormattedReportObject>
        <FormattedReportObject FieldName="{rptBrefast.RoomTypeCode}"><FormattedValue>DTC</FormattedValue></FormattedReportObject>
        <FormattedReportObject FieldName="{rptBrefast.Night}"><FormattedValue>3</FormattedValue></FormattedReportObject>
        <FormattedReportObject FieldName="{rptBrefast.QTich}"><FormattedValue>Viet Nam</FormattedValue></FormattedReportObject>
        <FormattedReportObject FieldName="{rptBrefast.RoomRateCode}"><FormattedValue></FormattedValue></FormattedReportObject>
        <FormattedReportObject FieldName="{@Breakfast}"><FormattedValue>Breakfast Include</FormattedValue></FormattedReportObject>
        <FormattedReportObject FieldName="{rptBrefast.BreakfastCode}"><FormattedValue>B1</FormattedValue></FormattedReportObject>
        <FormattedReportObject FieldName="{rptBrefast.ClientName}"><FormattedValue>FIT</FormattedValue></FormattedReportObject>
    </FormattedReportObjects></FormattedAreaPair>
</FormattedReport>
XML);

        $result = (new MealPlanPdfImporter())->importXml($path);
        unlink($path);

        $this->assertSame('2026-09-23', $result['report_date']);
        $this->assertSame(1, $result['rows']);
        $this->assertSame('Lê Huỳnh Kim Ngân', MealPlanGuest::firstOrFail()->guest_name);
        $this->assertSame('1988-04-29', MealPlanGuest::firstOrFail()->birthday->toDateString());
    }

    public function test_it_parses_guest_rows_and_repairs_vietnamese_names(): void
    {
        $text = <<<'PDF'
MEAL PLAN reports ON 03/09/2026
201 SDT 3 PARK JUNG SIC 02/09/2026 03/09/2026 1 AGODA STR Korea Republic of Breakfast Include B1 1 0
301 PDD KiÒu Vò Kim Ng©n 02/09/2026 03/09/2026 1 AGODA STR Viet Nam Breakfast Include B1 1 0
304 SDL 2 JIRARATANATHAM SUTHAM02/09/2026 05/09/2026 3 FIT Thailand Breakfast Include B1 1 0
305 PDD 2 TAN TZY LING
02/09/2026 05/09/2026 3 AGODA STR Malaysia Breakfast Include B1 1 0
PDF;

        $rows = (new MealPlanPdfImporter())->parseRows($text);

        $this->assertCount(4, $rows);
        $this->assertSame('Kiều Võ Kim Ngân', $rows[1]['guest_name']);
        $this->assertSame('Korea Republic of', $rows[0]['nationality']);
        $this->assertSame(1, $rows[0]['nights']);
        $this->assertSame('JIRARATANATHAM SUTHAM', $rows[2]['guest_name']);
        $this->assertSame('TAN TZY LING', $rows[3]['guest_name']);
    }

    public function test_it_repairs_remaining_legacy_vietnamese_names(): void
    {
        $text = <<<'PDF'
MEAL PLAN reports ON 03/09/2026
405 PDD §ç tR¬NG yÕN nHI 22/09/2026 23/09/2026 1 AGODA STR Viet Nam Breakfast Include B1 1 0
PDF;

        $rows = (new MealPlanPdfImporter())->parseRows($text);

        $this->assertSame('Đỗ Trương Yến Nhi', $rows[0]['guest_name']);

        $rows = (new MealPlanPdfImporter())->parseRows(str_replace('tR¬NG', "tR\u{00AD}¬NG", $text));

        $this->assertSame('Đỗ Trương Yến Nhi', $rows[0]['guest_name']);
    }

    public function test_it_repairs_mangled_vietnamese_names_from_exported_reports(): void
    {
        $names = [
            'Võ Kh¾c Tr¬ng',
            'Nguyễn §øc B¶n',
            'nG« tHÞ lAN uYªN',
            'TR¦¥NG MINH NGäC',
        ];

        $expected = [
            'Võ Khắc Trọng',
            'Nguyễn Đức Bản',
            'Ngọc Thị Lan Uyên',
            'Trương Minh Ngọc',
        ];

        foreach ($names as $index => $name) {
            $this->assertSame($expected[$index], (new MealPlanPdfImporter())->parseRows("MEAL PLAN reports ON 03/09/2026\n101 PDD {$name} 22/09/2026 23/09/2026 1 AGODA STR Viet Nam Breakfast Include B1 1 0")[0]['guest_name']);
        }
    }
}
