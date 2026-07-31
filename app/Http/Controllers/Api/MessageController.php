<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMessageRequest;
use App\Models\Incident;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function index(Incident $incident)
    {
        $user = Auth::user();

        $incident->messages()
            ->where('author_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(
            $incident->messages()->with('author:id,name')->orderBy('created_at')->get()
        );
    }

    public function store(StoreMessageRequest $request, Incident $incident)
    {
        $user = Auth::user();

        $data = [
            'author_id' => $user->id,
            'content' => $request->content ?? '',
        ];

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('messages/' . $incident->id, 'public');

            $data['attachment_path'] = $path;
            $data['attachment_type'] = str_starts_with($file->getMimeType(), 'video') ? 'video' : 'image';
            $data['attachment_original_name'] = $file->getClientOriginalName();
        }

        $message = $incident->messages()->create($data);

        $recipient = $user->id === $incident->technician_id
            ? $incident->company->user
            : $incident->technician;

        if ($recipient) {
            Notification::create([
                'user_id' => $recipient->id,
                'type' => 'nouveau_message',
                'title' => 'Nouveau message',
                'message' => "Nouveau message sur l'incident {$incident->code}.",
            ]);
        }

        return response()->json($message->load('author:id,name'), 201);
    }
}