<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ClientsExport;
use App\Exports\EmprestimosExport;
use SimpleSoftwareIO\QrCode\Facades\QrCode;


class EmailCobrancaPlataforma extends Mailable
{
    use Queueable, SerializesModels;

    public $details;
    public $locacao;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($details, $locacao)
    {
        $this->details = $details;
        $this->locacao = $locacao;
    }


    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // Transformar os dados dos clientes em uma coleção
        $locacao = [
            "valor" => $this->locacao->valor,
            "chave_pix" => $this->locacao->chave_pix,
            "type" => $this->locacao->type,
            "data_vencimento" => $this->locacao->data_vencimento,
        ];

         // Gerar o QR code baseado em uma string
        $qrCode = QrCode::format('png')->size(200)->generate($this->locacao->chave_pix);

        return $this->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'))
                    ->subject('Cobrança da Plataforma')
                    ->view('emails.cobrancaplataforma', compact('qrCode'));
    }
}
