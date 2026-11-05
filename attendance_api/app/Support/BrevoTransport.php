<?php

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\MessageConverter;

final class BrevoTransport extends AbstractTransport
{
    public function __construct(private readonly string $key)
    {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        if (trim($this->key) === '') {
            throw new TransportException('Brevo API key is not configured.');
        }
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        if ($email->getAttachments() !== []) {
            throw new TransportException('Brevo attachments are not supported by this transport.');
        }
        $address = fn (Address $address): array => array_filter([
            'email' => $address->getAddress(), 'name' => $address->getName(),
        ], fn (string $value): bool => $value !== '');
        $payload = ['sender' => $address($message->getEnvelope()->getSender()), 'subject' => $email->getSubject()];
        foreach (['to' => $email->getTo(), 'cc' => $email->getCc(), 'bcc' => $email->getBcc()] as $field => $recipients) {
            if ($recipients !== []) {
                $payload[$field] = array_map($address, $recipients);
            }
        }
        if ($email->getReplyTo() !== []) {
            $payload['replyTo'] = $address($email->getReplyTo()[0]);
        }
        if ($email->getHtmlBody() !== null) {
            $payload['htmlContent'] = $email->getHtmlBody();
        }
        if ($email->getTextBody() !== null) {
            $payload['textContent'] = $email->getTextBody();
        }
        try {
            $response = Http::withHeaders(['api-key' => $this->key])->acceptJson()
                ->connectTimeout(10)->timeout(30)->withoutRedirecting()
                ->post('https://api.brevo.com/v3/smtp/email', $payload);
        } catch (ConnectionException) {
            throw new TransportException('Brevo connection failed or timed out.');
        }
        if ($response->status() !== 201 || ! is_string($response->json('messageId')) || $response->json('messageId') === '') {
            throw new TransportException('Brevo did not confirm email acceptance (HTTP '.$response->status().').');
        }
        $message->setMessageId($response->json('messageId'));
    }

    public function __toString(): string
    {
        return 'brevo';
    }
}
