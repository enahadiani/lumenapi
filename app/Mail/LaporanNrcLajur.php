<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LaporanNrcLajur extends Mailable
{
    use Queueable, SerializesModels;

    public $data_array;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data_array)
    {
        $this->data_array = $data_array;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('toko.rptNrcLajur');
    }
}
