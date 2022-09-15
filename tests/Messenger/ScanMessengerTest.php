<?php

namespace App\Tests\Messenger;

use App\Entity\Upload;
use App\Messenger\ScanMessenger;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

class ScanMessengerTest extends TestCase
{
    /**
     * @var MockObject|MailerInterface|null
     */
    private MockObject|MailerInterface|null $mailer = null;

    /**
     * @var MockObject|Upload|null
     */
    private MockObject|Upload|null $upload = null;

    protected function setUp(): void
    {
        $this->mailer = $this
            ->getMockBuilder(MailerInterface::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->upload = $this
            ->getMockBuilder(Upload::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    public function testSendTriggeredEmail(): void
    {
        $mailer = $this->mailer;
        $emailFrom = 'from@de.com';
        $emailTo = 'to@de.com';
        $upload = $this->upload;
        $triggers = ['trigger'];
        $url = 'www.debricked.com';

        $mailer
            ->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(TemplatedEmail::class))
        ;

        $messenger = new ScanMessenger(
            $mailer,
            $emailFrom,
            $emailTo
        );

        $email = $messenger->sendTriggeredEmail(
            $upload,
            $triggers,
            $url
        );

        $this->assertSame($emailFrom, $email->getFrom()[0]->getAddress());
        $this->assertSame($emailTo, $email->getTo()[0]->getAddress());
        $this->assertSame('messenger/triggeredEmail.html.twig', $email->getHtmlTemplate());
        $this->assertSame(
            [
                'upload' => $upload,
                'triggers' => $triggers,
                'url' => $url
            ],
            $email->getContext()
        );
    }
}