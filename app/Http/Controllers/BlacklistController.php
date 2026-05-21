<?php

namespace App\Http\Controllers;

use App\Models\Blacklist;
use Illuminate\Http\Request;

class BlacklistController extends Controller
{
    public function index(Request $request)
    {
        $query = Blacklist::orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where('phone_number', 'like', "%{$q}%")
                ->orWhere('reason', 'like', "%{$q}%");
        }

        $blacklists = $query->paginate(10);
        $blacklists->appends($request->all());

        return view('blacklist', compact('blacklists'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'phone_number' => 'required|string|max:50',
            'reason' => 'nullable|string|max:255',
        ]);

        // Clean phone number format
        $number = trim($validated['phone_number']);
        // Remove non-digit characters except maybe starting '+'
        $number = preg_replace('/[^\d+]/', '', $number);

        if (str_starts_with($number, '0')) {
            $number = '62'.substr($number, 1);
        } elseif (str_starts_with($number, '+')) {
            $number = substr($number, 1);
        } elseif (str_starts_with($number, '8')) {
            $number = '628'.substr($number, 1);
        }
        $validated['phone_number'] = $number;

        // Prevent duplicate blacklist entry
        Blacklist::firstOrCreate(
            ['phone_number' => $validated['phone_number']],
            ['reason' => $validated['reason'] ?? 'Manual Blacklist']
        );

        return redirect()->route('blacklist')->with('success', 'Nomor berhasil ditambahkan ke daftar hitam.');
    }

    public function destroy(Blacklist $blacklist)
    {
        $blacklist->delete();

        return redirect()->route('blacklist')->with('success', 'Nomor berhasil dihapus dari daftar hitam.');
    }
}
