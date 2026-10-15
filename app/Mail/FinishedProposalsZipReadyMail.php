<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FinishedProposalsZipReadyMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public string $downloadUrl,
        public int $proposalCount,
        public int $linkTtlHours,
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Seu ZIP do relatório de propostas finalizadas está pronto',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.reports.finished-proposals-zip-ready',
        );
    }
}
