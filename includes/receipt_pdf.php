<?php

require_once __DIR__ . '/report_pdf.php';   
require_once __DIR__ . '/order_helpers.php'; 
require_once __DIR__ . '/store_info.php';    

const RECEIPT_COLS = 36;   

function receiptRow(string $left, string $right = ''): string
{
    $space = RECEIPT_COLS - strlen($left) - strlen($right);
    if ($space < 1) {
        $left  = substr($left, 0, max(0, RECEIPT_COLS - strlen($right) - 1));
        $space = 1;
    }
    return $left . str_repeat(' ', $space) . $right;
}

function receiptDraw(array $order, array $items, float $pageHeight): array
{
    $pdf = new FPDF('P', 'mm', [80, $pageHeight]);
    $pdf->SetMargins(5, 6, 5);
    $pdf->SetAutoPageBreak(false);
    $pdf->AddPage();
    $pdf->SetTextColor(0, 0, 0);

    $line = function (string $text, string $align = 'L', string $style = '', int $size = 9) use ($pdf) {
        $pdf->SetFont('Courier', $style, $size);
        $pdf->Cell(70, 4.4, $text, 0, 1, $align);
    };
    $wrap = function (string $text, int $width = RECEIPT_COLS) {
        return explode("\n", wordwrap(pdfText($text), $width, "\n", true));
    };
    $rule  = function (string $char = '-') use ($line) {
        $line(str_repeat($char, RECEIPT_COLS));
    };
    $kv = function (string $label, string $value) use ($line, $wrap) {
        $prefix = str_pad($label, 10) . ': ';
        foreach ($wrap($value, RECEIPT_COLS - strlen($prefix)) as $i => $part) {
            $line(($i === 0 ? $prefix : str_repeat(' ', strlen($prefix))) . $part);
        }
    };

    $line(pdfText(STORE_NAME), 'C', 'B', 11);
    foreach ($wrap(STORE_ADDRESS) as $part) {
        $line($part, 'C');
    }
    $line('Tel: ' . STORE_PHONE, 'C');
    $line(STORE_EMAIL, 'C');
    $pdf->Ln(1);
    $rule();
    $line('SALES RECEIPT', 'C', 'B', 10);
    $rule();

    // order info
    $paidAt = !empty($order['payment_date']) ? $order['payment_date'] : date('Y-m-d H:i:s');
    $kv('Order No', orderNumber((int) $order['order_id']));
    $kv('Date Paid', date('M j, Y g:i A', strtotime($paidAt)));
    $kv('Customer', (string) $order['customer_name']);
    if ($order['fulfillment_method'] === 'Delivery') {
        $kv('Method', 'Delivery');
        $kv('Deliver to', (string) $order['delivery_address']);
    } else {
        $kv('Method', 'Store pick-up');
    }
    $kv('Cashier', trim((string) ($order['confirmed_name'] ?? '')) !== '' ? (string) $order['confirmed_name'] : 'Store');
    $rule();

    // items
    $line(receiptRow('ITEM', 'AMOUNT'), 'L', 'B');
    $rule();
    foreach ($items as $it) {
        foreach ($wrap((string) $it['product_name']) as $part) {
            $line($part);
        }
        $left = '  ' . (int) $it['quantity'] . ' x ' . pdfMoney($it['unit_price']);
        $line(receiptRow($left, pdfMoney($it['line_total'])));
    }
    $rule();

    // totals 
    $total = (float) $order['grand_total'];
    $paid  = isset($order['amount_paid']) ? (float) $order['amount_paid'] : $total;
    $line(receiptRow('Subtotal', pdfMoney($order['items_total'])));
    $line(receiptRow('Delivery fee', pdfMoney($order['delivery_fee'])));
    $rule('=');
    $line(receiptRow('TOTAL', pdfMoney($total)), 'L', 'B', 9);
    $rule('=');
    $line(receiptRow('Cash', pdfMoney($paid)));
    $line(receiptRow('Change', pdfMoney(max(0, $paid - $total))));
    $pdf->Ln(1);
    $line('*** PAID ***', 'C', 'B', 10);
    $rule();

    // footer
    $line('Thank you for shopping with us!', 'C');
    $line('Please keep this receipt.', 'C');

    return [$pdf, $pdf->GetY()];
}


function buildReceiptPdf(array $order, array $items): string
{
    [, $endY] = receiptDraw($order, $items, 400);                  
    [$pdf]    = receiptDraw($order, $items, max(100, $endY + 8));  
    return $pdf->Output('S');
}