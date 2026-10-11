<?php

date_default_timezone_set('Asia/Manila');
require_once __DIR__ . '/fpdf/fpdf.php';

function pdfText($text)
{
    return mb_convert_encoding((string)$text, 'ISO-8859-1', 'UTF-8');
}

function pdfMoney($amount)
{
    return 'PHP ' . number_format((float)$amount, 2);
}

class ReportPDF extends FPDF
{
    public $reportTitle = '';
    public $reportSubtitle = '';
    public $rowHeight = 9;

    public $tableHeaders = [];
    public $tableWidths = [];
    public $tableAligns = [];
    public $tableBoldCol = -1;

    function __construct($title, $subtitle = '', $orientation = 'P')
    {
        parent::__construct($orientation, 'mm', 'A4');
        $this->reportTitle = $title;
        $this->reportSubtitle = $subtitle;
        $this->SetMargins(10, 10, 10);
        $this->SetAutoPageBreak(true, 18);
        $this->AliasNbPages();
        $this->AddPage();
    }

    function Header()
    {
        $pageWidth = $this->GetPageWidth();

        $this->SetFillColor(183, 116, 102);
        $this->Rect(0, 0, $pageWidth, 26, 'F');
        $this->SetFillColor(226, 181, 154);
        $this->Rect(0, 26, $pageWidth, 1.5, 'F');

        $this->SetTextColor(255, 243, 220);
        $this->SetFont('Arial', '', 8);
        $this->SetXY(10, 6);
        $this->Cell($pageWidth - 20, 4, 'BAGUIO PASALUBONG SHOP', 0, 0, 'L');
        $this->SetXY(10, 6);
        $this->Cell($pageWidth - 20, 4, 'ADMIN REPORT', 0, 0, 'R');

        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 18);
        $this->SetXY(10, 12);
        $this->Cell(0, 10, pdfText($this->reportTitle), 0, 1, 'L');

        $nextY = 35;
        if ($this->reportSubtitle != '') {
            $this->SetFont('Arial', '', 9);
            $text = pdfText($this->reportSubtitle);
            $boxWidth = $this->GetStringWidth($text) + 10;

            $this->SetFillColor(255, 243, 220);
            $this->SetDrawColor(226, 181, 154);
            $this->SetTextColor(121, 91, 63);
            $this->SetXY(10, 32);
            $this->Cell($boxWidth, 7, $text, 1, 0, 'C', true);
            $nextY = 44;
        }

        $this->SetY($nextY);
    }

    function Footer()
    {
        $pageWidth = $this->GetPageWidth();
        $half = ($pageWidth - 20) / 2;

        $this->SetY(-16);
        $this->SetDrawColor(226, 181, 154);
        $this->SetLineWidth(0.2);
        $this->Line(10, $this->GetY(), $pageWidth - 10, $this->GetY());

        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(149, 124, 98);
        $this->Cell($half, 8, 'Generated ' . date('M j, Y g:i A'), 0, 0, 'L');
        $this->Cell($half, 8, 'Page ' . $this->PageNo() . ' of {nb}', 0, 0, 'R');

        $this->SetFillColor(183, 116, 102);
        $this->Rect(0, $this->GetPageHeight() - 4, $pageWidth, 4, 'F');
    }

    function summaryCards($cards)
    {
        $gap = 4;
        $count = count($cards);
        $cardWidth = ($this->GetPageWidth() - 20 - $gap * ($count - 1)) / $count;
        $y = $this->GetY();

        $this->SetLineWidth(0.3);

        for ($i = 0; $i < $count; $i++) {
            $x = 10 + $i * ($cardWidth + $gap);

            if ($i == $count - 1) {
                $this->SetFillColor(183, 116, 102);
                $this->SetDrawColor(183, 116, 102);
                $labelColor = [255, 243, 220];
                $valueColor = [255, 255, 255];
            } else {
                $this->SetFillColor(255, 243, 220);
                $this->SetDrawColor(226, 181, 154);
                $labelColor = [149, 124, 98];
                $valueColor = [74, 59, 48];
            }

            $this->Rect($x, $y, $cardWidth, 18, 'DF');

            $this->SetFont('Arial', '', 8);
            $this->SetTextColor($labelColor[0], $labelColor[1], $labelColor[2]);
            $this->SetXY($x + 4, $y + 3);
            $this->Cell($cardWidth - 8, 4, pdfText($cards[$i][0]), 0, 0, 'L');

            $this->SetFont('Arial', 'B', 13);
            $this->SetTextColor($valueColor[0], $valueColor[1], $valueColor[2]);
            $this->SetXY($x + 4, $y + 8.5);
            $this->Cell($cardWidth - 8, 7, pdfText($cards[$i][1]), 0, 0, 'L');
        }

        $this->SetLineWidth(0.2);
        $this->SetY($y + 18 + 8);
    }

    function sectionTitle($text)
    {
        $this->SetFont('Arial', 'B', 11);
        $this->SetTextColor(74, 59, 48);
        $this->Cell(0, 7, pdfText($text), 0, 1, 'L');

        $this->SetDrawColor(183, 116, 102);
        $this->SetLineWidth(0.6);
        $this->Line(10, $this->GetY(), 30, $this->GetY());
        $this->SetLineWidth(0.2);
        $this->Ln(4);
    }

    function bodyStyle()
    {
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(74, 59, 48);
        $this->SetDrawColor(240, 226, 214);
        $this->SetLineWidth(0.2);
    }

    function fit($text, $width)
    {
        $text = pdfText($text);
        while ($text != '' && $this->GetStringWidth($text) > $width - 3) {
            $text = substr($text, 0, -1);
        }
        return $text;
    }

    function tableHeader()
    {
        $this->SetFillColor(183, 116, 102);
        $this->SetDrawColor(167, 102, 89);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 9);
        $this->SetLineWidth(0.4);

        for ($i = 0; $i < count($this->tableHeaders); $i++) {
            $this->Cell($this->tableWidths[$i], 10, pdfText($this->tableHeaders[$i]), 'B', 0, $this->tableAligns[$i], true);
        }
        $this->Ln();
        $this->SetLineWidth(0.2);
    }

    function checkNewPage($height)
    {
        if ($this->GetY() + $height > $this->PageBreakTrigger) {
            $this->AddPage();
            $this->tableHeader();
            $this->bodyStyle();
        }
    }

    function drawTable($headers, $widths, $aligns, $rows, $totalRow = null, $boldCol = -1)
    {
        $this->tableHeaders = $headers;
        $this->tableWidths = $widths;
        $this->tableAligns = $aligns;
        $this->tableBoldCol = $boldCol;

        $this->tableHeader();
        $this->bodyStyle();

        if (count($rows) == 0) {
            $this->Cell(array_sum($widths), 12, 'No records found.', 'B', 1, 'C');
            return;
        }

        foreach ($rows as $row) {
            $this->checkNewPage($this->rowHeight);
            for ($i = 0; $i < count($widths); $i++) {
                $this->SetFont('Arial', ($i == $boldCol) ? 'B' : '', 9);
                $this->Cell($widths[$i], $this->rowHeight, $this->fit($row[$i], $widths[$i]), 'B', 0, $aligns[$i]);
            }
            $this->Ln();
        }

        if ($totalRow != null) {
            $this->checkNewPage(11);
            $this->SetFillColor(255, 243, 220);
            $this->SetDrawColor(183, 116, 102);
            $this->SetLineWidth(0.4);
            $this->SetFont('Arial', 'B', 9);
            for ($i = 0; $i < count($widths); $i++) {
                $this->Cell($widths[$i], 11, $this->fit($totalRow[$i], $widths[$i]), 'T', 0, $aligns[$i], true);
            }
            $this->Ln();
            $this->bodyStyle();
        }
    }
}