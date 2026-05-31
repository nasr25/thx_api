<?php

namespace App\Jobs;

use App\Mail\AppreciationMail;
use App\Models\Appreciation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendAppreciationEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(protected Appreciation $appreciation) {}

    public function handle(): void
    {
        $this->appreciation->loadMissing(['sender', 'receiver']);

        $receiver = $this->appreciation->receiver;

        if (!$receiver || !$receiver->email) {
            return;
        }

        try {
            Mail::to($receiver->email)->send(
                new AppreciationMail($this->appreciation)
            );
        } catch (\Exception $e) {
            Log::error("Failed to send appreciation email: " . $e->getMessage(), [
                'appreciation_id' => $this->appreciation->id,
                'receiver_email'  => $receiver->email,
            ]);
            $this->fail($e);
        }
    }
}
