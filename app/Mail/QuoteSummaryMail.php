<?php

namespace App\Mail;

use App\Models\User;
use App\Services\QuotePdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuoteSummaryMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $breakdown  `FeeBreakdown::toArray()`
     */
    public function __construct(
        public int $quoteId,
        public string $name,
        public string $quoteNumber,
        public string $validUntil,
        public array $breakdown,
        public ?int $senderId = null,
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Informação do orçamento para proposta',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.quotes.summary',
        );
    }

    /**
     * O PDF é gerado só no envio (no worker, com o tenant restaurado pelo
     * QueueTenancyBootstrapper): a fila carrega apenas o id do orçamento, e não
     * o binário do arquivo.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(function (): string {
                $sender = $this->senderId !== null ? User::find($this->senderId) : null;

                return app(QuotePdfService::class)->make((string) $this->quoteId, $sender, tenant())->output();
            }, 'orcamento-'.$this->quoteNumber.'.pdf')->withMime('application/pdf'),
        ];
    }
}
