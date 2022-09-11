<?php

namespace App\Messenger;

use App\Entity\Upload;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Messages relating to scans.
 */
class ScanMessenger
{
    /**
     * @var MailerInterface
     */
    private MailerInterface $mailer;

    /**
     * @var string
     */
    private string $emailFrom;

    /**
     * @var string
     */
    private string $emailTo;

    /**
     * @param MailerInterface $mailer
     * @param string $emailFrom
     * @param string $emailTo
     */
    public function __construct(MailerInterface $mailer, string $emailFrom, string $emailTo)
    {
        $this->mailer = $mailer;
        $this->emailFrom = $emailFrom;
        $this->emailTo = $emailTo;
    }

    /**
     * @param Upload $upload
     * @param array $triggers
     * @return void
     * @throws TransportExceptionInterface
     */
    public function sendTriggeredEmail(Upload $upload, array $triggers): void
    {
        $email = new TemplatedEmail();

        $email
            ->from($this->emailFrom)
            ->to($this->emailTo)
            ->subject('Triggers have been triggered during a scan.')
            ->htmlTemplate('messenger/triggeredEmail.html.twig')
            ->context([
                'upload' => $upload,
                'triggers' => $triggers,
            ])
        ;

        $this->mailer->send($email);
    }
}