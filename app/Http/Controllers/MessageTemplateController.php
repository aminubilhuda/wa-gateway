<?php

namespace App\Http\Controllers;

use App\Models\MessageTemplate;
use Illuminate\Http\Request;

class MessageTemplateController extends Controller
{
    public function index()
    {
        $templates = MessageTemplate::orderBy('created_at', 'desc')->get();

        return view('templates', compact('templates'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message_body' => 'required|string',
        ]);

        MessageTemplate::create($validated);

        return redirect()->route('templates')->with('success', 'Templat pesan berhasil dibuat.');
    }

    public function update(Request $request, MessageTemplate $template)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message_body' => 'required|string',
        ]);

        $template->update($validated);

        return redirect()->route('templates')->with('success', 'Templat pesan berhasil diperbarui.');
    }

    public function destroy(MessageTemplate $template)
    {
        $template->delete();

        return redirect()->route('templates')->with('success', 'Templat pesan berhasil dihapus.');
    }
}
