<?php


namespace App\Helpers;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserDeleteHelper
{
    private User $userForDeleting;
    private User $serviceAccount;

    public function __construct(User $userForDeleting, User $serviceAccount)
    {
        $this->userForDeleting = $userForDeleting;
        $this->serviceAccount = $serviceAccount;
    }

    public function saveDataOfUserInServiceAccount(): void
    {
        $this->saveTasks();
        $this->saveTimeIntervals();
        $this->saveTasksComments();
    }

    private function saveTasks(): void
    {
        DB::table('tasks_users')->where('user_id', '=', $this->userForDeleting->id)
            ->update(['user_id' => $this->serviceAccount->id]);
    }

    private function saveTimeIntervals(): void
    {
        DB::table('time_intervals')->where('user_id', '=', $this->userForDeleting->id)
            ->update(['user_id' => $this->serviceAccount->id]);
    }

    private function saveTasksComments(): void
    {
        DB::table('task_comment')->where('user_id', '=', $this->userForDeleting->id)
            ->update(['user_id' => $this->serviceAccount->id]);
    }
}
