<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendMessage extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */

    public $namaPengaju;
    public $pesan;
    public $namaApprover;
    public $linkTanggapan;
    public $emailPengaju;
    public $attachmentPath;

    public function __construct($namaPengaju, $namaApprover, $linkTanggapan, $emailPengaju, $attachmentPath = null)
    {
        $this->namaPengaju = $namaPengaju;
        $this->namaApprover = $namaApprover;
        $this->linkTanggapan = $linkTanggapan;
        $this->emailPengaju = $emailPengaju;
        $this->attachmentPath = $attachmentPath;
    }

    public function build()
    {
        $email = $this->subject('Hai ' . $this->namaApprover . ', ada request baru dari ' . $this->namaPengaju . '!')
            ->view('emails.message');

            // Jika ada file lampiran
        if ($this->attachmentPath && file_exists(storage_path('app/public/' . $this->attachmentPath))) {
            $email->attach(storage_path('app/public/' . $this->attachmentPath));
        }

        return $email;
    }
}
