<?php

namespace App\Jobs;

use App\Helpers\ServiceAccountHelper;
use App\Helpers\UserDeleteHelper;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeleteUser implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $userId;

    /**
     * Create a new job instance.
     *
     * @param int $userId
     */
    public function __construct(int $userId)
    {
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     *
     * @param ServiceAccountHelper $serviceAccountHelper
     * @return void
     */
    public function handle(ServiceAccountHelper $serviceAccountHelper)
    {
        $user = User::query()->find($this->userId);

        if (!$user) {
            Log::warning(sprintf("User with id = %d was not found. End job.", $this->userId));

            return;
        }

        (new UserDeleteHelper($user, $serviceAccountHelper->findOrCreate()))->saveDataOfUserInServiceAccount();
        $user->forceDelete();
    }
}
