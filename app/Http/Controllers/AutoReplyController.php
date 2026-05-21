<?php

namespace App\Http\Controllers;

use App\Models\AutoReply;
use App\Models\MessageLog;
use Illuminate\Http\Request;

class AutoReplyController extends Controller
{
    public function index()
    {
        $rules = AutoReply::with('parent', 'children')
            ->whereNull('parent_id')
            ->orderBy('created_at', 'desc')
            ->get()
            ->flatMap(function ($parent) {
                $result = collect([$parent]);
                foreach ($parent->children as $child) {
                    $result->push($child);
                    foreach ($child->children as $grandchild) {
                        $result->push($grandchild);
                    }
                }

                return $result;
            });

        $totalRules = AutoReply::count();
        $activeRules = AutoReply::where('is_active', true)->count();
        $totalTriggers = AutoReply::sum('trigger_count');

        $topRule = AutoReply::orderBy('trigger_count', 'desc')->first();
        $topKeyword = $topRule && $topRule->trigger_count > 0 ? '"'.$topRule->keyword.'"' : '-';

        $totalLogs = MessageLog::whereNull('campaign_id')->count();
        $successLogs = MessageLog::whereNull('campaign_id')->where('status', 'sent')->count();
        $successRate = $totalLogs > 0 ? round(($successLogs / $totalLogs) * 100, 1) : 100.0;

        $parentMenus = AutoReply::all();

        return view('auto-reply', compact(
            'rules',
            'totalRules',
            'activeRules',
            'totalTriggers',
            'topKeyword',
            'successRate',
            'parentMenus'
        ));
    }

    public function toggle(AutoReply $autoReply)
    {
        $autoReply->update(['is_active' => ! $autoReply->is_active]);

        return response()->json(['status' => 'success', 'is_active' => $autoReply->is_active]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'keyword' => 'required|string|max:255',
            'response_message' => 'required|string',
            'match_type' => 'required|in:exact,contains',
            'attachment' => 'nullable|file|max:16384',
            'parent_id' => 'nullable|exists:auto_replies,id',
        ]);

        $validated['is_active'] = true;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $mimeType = $file->getMimeType();
            $filename = time().'_'.$file->getClientOriginalName();
            $file->move(public_path('uploads/attachments'), $filename);
            $validated['attachment_path'] = asset('uploads/attachments/'.$filename);

            if (str_starts_with($mimeType, 'image/')) {
                $validated['attachment_type'] = 'image';
            } elseif (str_starts_with($mimeType, 'video/')) {
                $validated['attachment_type'] = 'video';
            } elseif (str_starts_with($mimeType, 'audio/')) {
                $validated['attachment_type'] = 'audio';
            } else {
                $validated['attachment_type'] = 'file';
            }
        }

        unset($validated['attachment']);
        AutoReply::create($validated);

        return redirect()->route('auto-reply')->with('success', 'Aturan auto reply berhasil ditambahkan.');
    }

    public function destroy(AutoReply $autoReply)
    {
        $autoReply->delete();

        return redirect()->route('auto-reply')->with('success', 'Aturan berhasil dihapus.');
    }

    public function update(Request $request, AutoReply $autoReply)
    {
        $validated = $request->validate([
            'keyword' => 'required|string|max:255',
            'response_message' => 'required|string',
            'match_type' => 'required|in:exact,contains',
            'attachment' => 'nullable|file|max:16384',
            'parent_id' => 'nullable|exists:auto_replies,id',
        ]);

        if ($request->hasFile('attachment')) {
            if ($autoReply->attachment_path) {
                $oldPath = str_replace(asset('uploads/attachments/'), public_path('uploads/attachments/'), $autoReply->attachment_path);
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }

            $file = $request->file('attachment');
            $mimeType = $file->getMimeType();
            $filename = time().'_'.$file->getClientOriginalName();
            $file->move(public_path('uploads/attachments'), $filename);
            $validated['attachment_path'] = asset('uploads/attachments/'.$filename);

            if (str_starts_with($mimeType, 'image/')) {
                $validated['attachment_type'] = 'image';
            } elseif (str_starts_with($mimeType, 'video/')) {
                $validated['attachment_type'] = 'video';
            } elseif (str_starts_with($mimeType, 'audio/')) {
                $validated['attachment_type'] = 'audio';
            } else {
                $validated['attachment_type'] = 'file';
            }
        }

        unset($validated['attachment']);
        $autoReply->update($validated);

        return redirect()->route('auto-reply')->with('success', 'Aturan auto reply berhasil diperbarui.');
    }
}
