<?php

namespace App\Http\Controllers\Helpers;

use App\Http\Controllers\Controller;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
class MailController extends Controller
{
    /**
     * The function sends the email, expecting as a parameter a data array which carries the recipient, the subject 
     * and the content of the email to be sent.
     * 
     *  data for email
     *   $data = [
     *       'affair' => 'affair',
     *       'mail' => mail,
     *       'content' => content,
     *   ];
     *
     * @param mixed $data
     * @return bool
     */
    public function sendMail($data)
    {
        // class
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = env('MAIL_HOST');
            $mail->SMTPAuth = true;
            $mail->Username = env('MAIL_USERNAME');
            $mail->Password = env('MAIL_PASSWORD');
            $mail->SMTPSecure = env('MAIL_ENCRYPTION', PHPMailer::ENCRYPTION_STARTTLS);
            $mail->Port = env('MAIL_PORT', 587);

            // Set the encoding to UTF-8
            $mail->CharSet = 'UTF-8';

            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ];

            // Dynamic parameters
            $subject = $data['affair'];
            $content = '<p>_EMAIL_CONTENT</p>';
            $toEmail = $data['mail'];
            $toName = '_RECIPIENT';

            $mail->setFrom(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
            $mail->addAddress($toEmail, $toName);
            $htmlBody = $data['content'];

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = strip_tags($content);
            $mail->send();

            //returns true if the email was sent
            return true;
        } catch (Exception $e) {

            // returns false if there was an error when trying to send the email
            return false;
        }
    }
}
