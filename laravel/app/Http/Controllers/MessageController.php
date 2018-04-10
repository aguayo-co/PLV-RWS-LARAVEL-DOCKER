<?php

namespace App\Http\Controllers;

use App\Thread;
use App\User;
use Carbon\Carbon;
use Cmgmyr\Messenger\Models\Message;
use Cmgmyr\Messenger\Models\Participant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ThreadController extends Controller
{
    /**
     * Adds a new message to a current thread.
     *
     * @param $id
     * @return mixed
     */
    public function create(Request $request, Model $thread)
    {
        $thread->activateAllParticipants();

        // Message
        Message::create([
            'thread_id' => $thread->id,
            'user_id' => auth()->id(),
            'body' => $request->message,
        ]);

        // Add replier as a participant
        $participant = Participant::firstOrCreate([
            'thread_id' => $thread->id,
            'user_id' => auth()->id(),
        ]);
        $participant->last_read = now();
        $participant->save();

        // Recipients
        if ($request->recipients) {
            $thread->addParticipant($request->recipients);
        }

        return $thread;
    }
}
