<?php

require_once __DIR__ . '/store_info.php';
require_once __DIR__ . '/order_helpers.php';   
require_once __DIR__ . '/mailer.php';          
require_once __DIR__ . '/receipt_pdf.php';     

function emailMoney($amount): string
{
    return '&#8369;' . number_format((float) $amount, 2);
}

function getOrderEmailData(mysqli $conn, int $orderId): ?array
{
    $res = mysqli_execute_query(
        $conn,
        "SELECT * FROM v_order_summary WHERE order_id = ? LIMIT 1",
        [$orderId]
    );
    $order = mysqli_fetch_assoc($res);
    if (!$order) {
        return null;
    }

    $res = mysqli_execute_query(
        $conn,
        "SELECT product_name, quantity, unit_price, line_total
         FROM v_order_details WHERE order_id = ? ORDER BY product_name",
        [$orderId]
    );
    return ['order' => $order, 'items' => mysqli_fetch_all($res, MYSQLI_ASSOC)];
}

function emailBox(string $html): string
{
    return '<div style="background:#fff8e6;border-left:4px solid #b77466;padding:10px 14px;margin:14px 0;font-size:14px;">'
        . $html . '</div>';
}


function emailRow(string $label, string $valueHtml): string
{
    return '<tr><td style="padding:3px 12px 3px 0;color:#666;vertical-align:top;">' . h($label) . '</td>'
        . '<td style="padding:3px 0;"><strong>' . $valueHtml . '</strong></td></tr>';
}


function statusEmailParts(array $o): array
{
    $no         = orderNumber((int) $o['order_id']);
    $status     = $o['order_status'];
    $isDelivery = $o['fulfillment_method'] === 'Delivery';
    $when       = !empty($o['scheduled_at']) ? date('l, M j, Y g:i A', strtotime($o['scheduled_at'])) : '';
    $whenHtml   = $when !== '' ? h($when) : 'your chosen schedule';
    $contact    = h(STORE_PHONE) . ' / ' . h(STORE_EMAIL);
    $recipient  = trim((string) $o['recipient_name']) !== '' ? $o['recipient_name'] : $o['customer_name'];
    $phone      = trim((string) $o['recipient_phone']) !== '' ? $o['recipient_phone'] : $o['contact_number'];
    $unpaid     = ($o['payment_status'] ?? 'Unpaid') !== 'Paid';

    $parts = [
        'subject' => 'Order ' . $no . ' is now ' . $status,
        'intro'   => '<p>The status of your order was updated to <strong>' . h($status) . '</strong>.</p>',
        'box'     => '',
    ];

    switch ($status) {
        case 'Pending':
            $parts['subject'] = 'We received your order ' . $no;
            $parts['intro']   = '<p>Thank you for your order! We have <strong>received it</strong> and will confirm it soon. '
                . ($isDelivery ? 'Delivery' : 'Pick-up') . ' is scheduled for <strong>' . $whenHtml . '</strong>.</p>';
            $parts['box'] = emailBox(
                '<strong>What happens next</strong>'
                . '<p style="margin:6px 0 0;">We will email you when your order is confirmed, being prepared, and '
                . ($isDelivery ? 'out for delivery' : 'ready for pick-up') . '. '
                . 'Payment is cash (' . ($isDelivery ? 'on delivery' : 'at pick-up') . '): <strong>'
                . emailMoney($o['grand_total']) . '</strong>.</p>'
                . '<p style="margin:6px 0 0;">Changed your mind? You can cancel from <strong>My Orders</strong> '
                . 'until we confirm your order. After that it can no longer be cancelled.</p>'
            );
            break;

        case 'Confirmed':
            $parts['subject'] = 'Order ' . $no . ' is confirmed';
            $parts['intro']   = '<p>Good news! Your order has been <strong>confirmed</strong>. '
                . ($isDelivery ? 'Delivery' : 'Pick-up') . ' is scheduled for <strong>' . $whenHtml . '</strong>.</p>'
                . '<p>Please note that a confirmed order <strong>can no longer be cancelled</strong>, '
                . 'because we are starting to get your items ready. If you have a concern, please contact us at '
                . $contact . '.</p>';
            break;

        case 'Preparing':
            $parts['subject'] = 'We are preparing your order ' . $no;
            $parts['intro']   = '<p>This is a friendly reminder that we are now <strong>preparing your order</strong>. '
                . ($isDelivery ? 'It will be delivered' : 'It will be ready for pick-up') . ' on <strong>' . $whenHtml . '</strong>.</p>'
                . '<p>You do not need to do anything. We will email you again when ' .
                ($isDelivery ? 'it is out for delivery.' : 'it is ready for pick-up.') . '</p>';
            break;

        case 'Ready':   // pick-up orders
            $parts['subject'] = 'Your order ' . $no . ' is ready for pick-up';
            $parts['intro']   = '<p>Your order is <strong>ready for pick-up</strong>! Please come to our store during store hours: '
                . date('g:i A', mktime(STORE_OPEN_HOUR, 0, 0)) . ' to ' . date('g:i A', mktime(STORE_CLOSE_HOUR, 0, 0)) . '.</p>';
            $parts['box'] = emailBox(
                '<strong>Show this when you get here</strong>'
                . '<table style="font-size:14px;margin-top:6px;">'
                . emailRow('Order ID', '<span style="font-size:18px;">' . h($no) . '</span>')
                . emailRow('Name for pick-up', h($recipient))
                . emailRow('Contact number', h($phone))
                . ($when !== '' ? emailRow('Pick-up schedule', h($when)) : '')
                . ($unpaid ? emailRow('Amount to pay (cash)', emailMoney($o['grand_total'])) : '')
                . '</table>'
                . '<p style="margin:8px 0 0;">Our staff will check your <strong>Order ID</strong> and name before handing over your order. '
                . 'Please bring a valid ID. You will pay in cash at the store, and your receipt will be emailed after payment.</p>'
                . '<p style="margin:8px 0 0;">' . h(STORE_NAME) . '<br>' . h(STORE_ADDRESS) . '</p>'
            );
            break;

        case 'Out for Delivery':
            $parts['subject'] = 'Your order ' . $no . ' is out for delivery';
            $parts['intro']   = '<p>Your order is <strong>out for delivery</strong> and on its way to you. '
                . 'Please keep your phone nearby so our rider can reach you.</p>';
            $parts['box'] = emailBox(
                '<strong>Before the rider arrives</strong>'
                . '<table style="font-size:14px;margin-top:6px;">'
                . ($unpaid ? emailRow('Please prepare (cash)', emailMoney($o['grand_total'])) : '')
                . emailRow('Deliver to', h($o['delivery_address']))
                . emailRow('Receiver', h($recipient) . ', ' . h($phone))
                . '</table>'
                . '<p style="margin:8px 0 0;">Payment is cash on delivery, so please have the exact amount ready if you can. '
                . 'For proof of delivery, our rider will <strong>take photos of your order</strong> at hand-over. '
                . 'Please check your items when you receive them.</p>'
            );
            break;

        case 'Completed':
            $parts['subject'] = 'Thank you for your order ' . $no . ' (receipt attached)';
            $parts['intro']   = '<p>Thank you for shopping with ' . h(STORE_NAME) . '! We received your payment of <strong>'
                . emailMoney($o['grand_total']) . '</strong>, and your order is now <strong>completed</strong>.</p>'
                . '<p>Your receipt is attached to this email as a PDF.</p>';
            $parts['box'] = emailBox(
                '<strong>How was your experience?</strong>'
                . '<p style="margin:6px 0 0;">Please take a moment to review the products you bought. '
                . 'Your feedback helps us improve our products and our store. Salamat po!</p>'
            );
            break;

        case 'Cancelled':
            $parts['subject'] = 'Order ' . $no . ' was cancelled';
            if (trim((string) $o['cancel_reason']) !== '') {   // cancelled by the store (a reason is required)
                $parts['intro'] = '<p>We are sorry, your order was <strong>cancelled by the store</strong>. Reason: '
                    . h($o['cancel_reason']) . '</p>'
                    . '<p>If you have questions, please contact us at ' . $contact . '.</p>';
            } else {                                           // cancelled by the customer
                $parts['intro'] = '<p>As you requested, your order was <strong>cancelled</strong>. No payment is needed.</p>'
                    . '<p>We hope to serve you again soon. If this was a mistake, you can place a new order anytime '
                    . 'or contact us at ' . $contact . '.</p>';
            }
            break;
    }
    return $parts;
}

function buildOrderStatusEmail(mysqli $conn, int $orderId): ?array
{
    $data = getOrderEmailData($conn, $orderId);
    if (!$data) {
        return null;
    }
    $o     = $data['order'];
    $items = $data['items'];
    $parts = statusEmailParts($o);
    $cell  = 'padding:6px 8px;border-bottom:1px solid #eee;';

    $rows = '';
    foreach ($items as $it) {
        $rows .= '<tr>'
            . '<td style="' . $cell . '">' . h($it['product_name']) . '</td>'
            . '<td style="' . $cell . 'text-align:center;">' . (int) $it['quantity'] . '</td>'
            . '<td style="' . $cell . 'text-align:right;">' . emailMoney($it['unit_price']) . '</td>'
            . '<td style="' . $cell . 'text-align:right;">' . emailMoney($it['line_total']) . '</td>'
            . '</tr>';
    }

    $isDelivery = $o['fulfillment_method'] === 'Delivery';
    $where = $isDelivery
        ? 'Delivery to ' . h($o['delivery_address']) . (!empty($o['area_name']) ? ' (' . h($o['area_name']) . ')' : '')
        : 'Store pick-up';
    $when = !empty($o['scheduled_at']) ? date('M j, Y g:i A', strtotime($o['scheduled_at'])) : '';

    $html = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>'
        . '<div style="font-family:Arial,Helvetica,sans-serif;max-width:560px;margin:auto;color:#222;">'
        . '<h2 style="margin-bottom:4px;">Order ' . h(orderNumber($orderId)) . '</h2>'
        . '<p style="margin-top:0;"><strong>Status: ' . h($o['order_status']) . '</strong></p>'
        . '<p>Hi ' . h($o['first_name']) . ',</p>'
        . $parts['intro']
        . $parts['box']
        . '<h3 style="margin:18px 0 6px;font-size:15px;">Order summary</h3>'
        . '<table style="width:100%;border-collapse:collapse;font-size:14px;">'
        . '<tr style="background:#f4f4f4;">'
        . '<th style="padding:6px 8px;text-align:left;">Product</th>'
        . '<th style="padding:6px 8px;">Qty</th>'
        . '<th style="padding:6px 8px;text-align:right;">Unit price</th>'
        . '<th style="padding:6px 8px;text-align:right;">Total</th></tr>'
        . $rows
        . '<tr><td colspan="3" style="padding:6px 8px;text-align:right;">Subtotal</td>'
        . '<td style="padding:6px 8px;text-align:right;">' . emailMoney($o['items_total']) . '</td></tr>'
        . '<tr><td colspan="3" style="padding:6px 8px;text-align:right;">Delivery fee</td>'
        . '<td style="padding:6px 8px;text-align:right;">' . emailMoney($o['delivery_fee']) . '</td></tr>'
        . '<tr><td colspan="3" style="padding:8px;text-align:right;font-weight:bold;border-top:2px solid #222;">Grand total</td>'
        . '<td style="padding:8px;text-align:right;font-weight:bold;border-top:2px solid #222;">' . emailMoney($o['grand_total']) . '</td></tr>'
        . '</table>'
        . '<p style="font-size:14px;">' . $where . ($when !== '' ? '<br>Scheduled: ' . h($when) : '')
        . '<br>Payment: Cash (' . h($o['payment_status'] ?? 'Unpaid') . ')</p>'
        . '<hr style="border:0;border-top:1px solid #eee;">'
        . '<p style="color:#888;font-size:12px;">' . h(STORE_NAME) . '<br>' . h(STORE_ADDRESS)
        . '<br>' . h(STORE_PHONE) . ' | ' . h(STORE_EMAIL) . '</p>'
        . '</div></body></html>';

    return [
        'to'      => $o['email'],
        'subject' => $parts['subject'],
        'html'    => $html,
        'status'  => $o['order_status'],
    ];
}


function sendOrderStatusEmail(mysqli $conn, int $orderId): bool
{
    $GLOBALS['last_mail_error'] = '';   
    try {
        $mail = buildOrderStatusEmail($conn, $orderId);
        if (!$mail) {
            $GLOBALS['last_mail_error'] = 'order not found in v_order_summary';
            return false;
        }

        $attachments = [];
        if ($mail['status'] === 'Completed') {
            try {
                $data = getOrderEmailData($conn, $orderId);
                $attachments[] = [
                    'name' => 'receipt-' . orderNumber($orderId) . '.pdf',
                    'data' => buildReceiptPdf($data['order'], $data['items']),
                    'type' => 'application/pdf',
                ];
            } catch (Throwable $e) {
                error_log('receipt pdf failed: ' . $e->getMessage());
            }
        }

        $sent = sendMail($mail['to'], $mail['subject'], $mail['html'], $attachments);
        if (!$sent && $GLOBALS['last_mail_error'] === '') {
            $GLOBALS['last_mail_error'] = 'the mail server refused or could not be reached';
        }
        return $sent;
    } catch (Throwable $e) {
        error_log('sendOrderStatusEmail failed: ' . $e->getMessage());
        $GLOBALS['last_mail_error'] = $e->getMessage() . ' (' . basename($e->getFile()) . ' line ' . $e->getLine() . ')';
        return false;
    }
}