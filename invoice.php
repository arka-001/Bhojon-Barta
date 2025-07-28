<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require_once('TCPDF-main/tcpdf.php');
require_once('connection/connect.php');

// Prevent direct access to this file
if (basename($_SERVER['PHP_SELF']) === 'invoice.php') {
    header('HTTP/1.0 403 Forbidden');
    echo "Direct access to this file is not allowed.";
    exit;
}

// Function to generate the PDF invoice
function generateInvoicePDF($user, $cart_items, $item_total, $delivery_charge, $discount, $order_ids) {
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Bhojon Barta');
    $pdf->SetTitle('Order Invoice');
    $pdf->SetSubject('Order Invoice');
    $pdf->SetKeywords('TCPDF, PDF, invoice, order');

    $pdf->SetHeaderData('inc.jpg', 30, 'Bhojon Barta - Order Invoice', "Your Order Summary", array(0, 64, 255), array(0, 64, 128));
    $pdf->setFooterData(array(0, 64, 0), array(0, 64, 128));

    $pdf->setHeaderFont([PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN]);
    $pdf->setFooterFont([PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA]);
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->setHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->setFooterMargin(PDF_MARGIN_FOOTER);
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    $pdf->SetFont('dejavusans', '', 10, '', true);
    $pdf->AddPage();

    $html = '<h1>Order Invoice</h1>';
    $html .= '<p>Dear ' . htmlspecialchars($user["f_name"] . " " . $user["l_name"]) . ',</p>';
    $html .= '<p>Order ID(s): ' . htmlspecialchars(implode(", ", $order_ids)) . '</p>';
    $html .= '<p>Order Date: ' . date('Y-m-d H:i:s') . '</p>';
    $html .= '<p>Here is your order summary:</p>';

    $original_total = 0;
    $discounted_total = 0;

    // Group items by restaurant
    foreach ($cart_items as $res_id => $items) {
        $restaurant_name = htmlspecialchars($items[array_key_first($items)]['restaurant_name']);
        $html .= '<h3>From: ' . $restaurant_name . '</h3>';
        $html .= '<table border="1" cellpadding="4">';
        $html .= '<thead><tr><th>Item</th><th>Quantity</th><th>Price</th><th>Discount</th><th>Subtotal</th></tr></thead>';
        $html .= '<tbody>';

        foreach ($items as $item) {
            $original_price = $item["price"] * $item["quantity"];
            $item_price = isset($item["discounted_price"]) ? $item["discounted_price"] : $original_price;
            $item_discount = $original_price - $item_price;

            $original_total += $original_price;
            $discounted_total += $item_price;

            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($item["title"]) . '</td>';
            $html .= '<td>' . htmlspecialchars($item["quantity"]) . '</td>';
            $html .= '<td>₹' . htmlspecialchars(number_format($item["price"], 2)) . '</td>';
            $html .= '<td>₹' . htmlspecialchars(number_format($item_discount, 2)) . '</td>';
            $html .= '<td>₹' . htmlspecialchars(number_format($item_price, 2)) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';
    }

    $html .= '<p>Subtotal: ₹' . number_format($original_total, 2) . '</p>';
    $html .= '<p>Discount: -₹' . number_format($original_total - $discounted_total, 2) . '</p>';
    $html .= '<p>Discounted Subtotal: ₹' . number_format($discounted_total, 2) . '</p>';
    $html .= '<p>Delivery Charge: ₹' . number_format($delivery_charge, 2) . '</p>';
    $html .= '<p><strong>Total: ₹' . number_format($discounted_total + $delivery_charge, 2) . '</strong></p>';

    $pdf->writeHTML($html, true, false, true, false, '');
    return $pdf->Output('invoice.pdf', 'S');
}

// Function to send the invoice email
function sendInvoiceEmail($user, $cart_items, $item_total, $delivery_charge, $discount, $order_ids) {
    $mail = new PHPMailer(true);
    $email_sent = false;

    try {
        $mail->SMTPDebug = SMTP::DEBUG_OFF;
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'bhojonbarta@gmail.com';
        $mail->Password = 'zyys vops vyua zetu'; // Ensure this is a valid App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        $mail->setFrom('bhojonbarta@gmail.com', 'Bhojon Barta');
        $mail->addAddress($user["email"], $user["f_name"] . " " . $user["l_name"]);

        $mail->isHTML(true);
        $mail->Subject = 'Your Bhojon Barta Order Invoice';
        $mail->Body = 'Dear ' . htmlspecialchars($user["f_name"] . " " . $user["l_name"]) . ",<br><br>Thank you for your order! Please find your invoice attached for Order ID(s): " . htmlspecialchars(implode(", ", $order_ids)) . ".<br><br>Sincerely,<br>Bhojon Barta";
        $mail->AltBody = 'Dear ' . htmlspecialchars($user["f_name"] . " " . $user["l_name"]) . ", thank you for your order. Your invoice is attached for Order ID(s): " . htmlspecialchars(implode(", ", $order_ids)) . ".";

        $pdf_content = generateInvoicePDF($user, $cart_items, $item_total, $delivery_charge, $discount, $order_ids);
        $mail->addStringAttachment($pdf_content, 'invoice.pdf', 'base64', 'application/pdf');

        $mail->send();
        error_log("Invoice email sent successfully to " . $user["email"]);
        $email_sent = true;
    } catch (Exception $e) {
        error_log("Invoice email could not be sent to " . $user["email"] . ". Mailer Error: " . $mail->ErrorInfo);
        $email_sent = false;
    }

    return $email_sent;
}
?>