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
     * Show all of the message threads to the user.
     *
     * @return mixed
     */
    public function index(Request $request)
    {
        // All threads, ignore deleted/archived participants
        $threads = Thread::getAllLatest()->get();

        // All threads that user is participating in
        // $threads = Thread::forUser(auth()->id())->latest('updated_at')->get();

        // All threads that user is participating in, with new messages
        // $threads = Thread::forUserWithNewMessages(auth()->id())->latest('updated_at')->get();

        return $threads;
    }

    /**
     * Shows a message thread.
     *
     * @param $id
     * @return mixed
     */
    public function show(Request $request, Model $thread)
    {
        $userId = auth()->id();
        $thread->markAsRead($userId);

        return $thread;
    }

    /**
     * Stores a new message thread.
     *
     * @return mixed
     */
    public function store(Request $request)
    {
        $thread = Thread::create([
            'subject' => $request->subject,
        ]);

        // Message
        Message::create([
            'thread_id' => $thread->id,
            'user_id' => auth()->id(),
            'body' => $request->message,
            'private' => $request->private,
            'product_id' => $request->product_id,
        ]);

        // Sender
        Participant::create([
            'thread_id' => $thread->id,
            'user_id' => auth()->id(),
            'last_read' => new Carbon,
        ]);

        // Recipients
        if ($request->recipients) {
            $thread->addParticipant($request->recipients);
        }

        return $thread;
    }
}
