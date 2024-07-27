<?php
// Function to send an email using SendGrid
function send_email($email_address, $email_subject, $email_content): bool
{
    $email = new \SendGrid\Mail\Mail();
    $email->setFrom($_ENV['SERVER_FROM_EMAIL'], "Castellanario");
    $email->setSubject($email_subject);
    $email->addTo($email_address);
    $email->addContent("text/html", $email_content);
    $sendgrid = new \SendGrid($_ENV['SENDGRID_API_KEY']);
    try {
        $sendgrid->send($email);
        return true;
    } catch (Exception $e) {
        return false;
    }
}