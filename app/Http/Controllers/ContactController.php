<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\MessageLog;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $query = Contact::orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('phone_number', 'like', "%{$q}%")
                    ->orWhere('label', 'like', "%{$q}%");
            });
        }

        if ($request->filled('label')) {
            $query->where('label', $request->label);
        }

        $contacts = $query->paginate(10);
        $contacts->appends($request->all());

        // Get dynamic segmentation list (labels with their counts)
        $segments = Contact::select('label', \DB::raw('count(*) as total'))
            ->groupBy('label')
            ->whereNotNull('label')
            ->where('label', '<>', '')
            ->orderBy('total', 'desc')
            ->get();

        return view('contacts', compact('contacts', 'segments'));
    }

    public function exportCsv(Request $request)
    {
        $query = Contact::orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('phone_number', 'like', "%{$q}%")
                    ->orWhere('label', 'like', "%{$q}%");
            });
        }

        if ($request->filled('label')) {
            $query->where('label', $request->label);
        }

        $contacts = $query->get();

        return StreamedResponse::create(function () use ($contacts) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Nomor', 'Nama', 'Label']);

            foreach ($contacts as $contact) {
                fputcsv($handle, [
                    $contact->phone_number,
                    $contact->name,
                    $contact->label ?? '',
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="contacts-export-'.now()->format('Y-m-d-His').'.csv"',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'label' => 'nullable|string|max:255',
        ]);

        // Clean phone number format
        $number = trim($validated['phone_number']);
        if (str_starts_with($number, '0')) {
            $number = '62'.substr($number, 1);
        } elseif (str_starts_with($number, '+')) {
            $number = substr($number, 1);
        } elseif (str_starts_with($number, '8')) {
            $number = '628'.substr($number, 1);
        }
        $validated['phone_number'] = $number;

        Contact::create($validated);

        return redirect()->route('contacts')->with('success', 'Kontak berhasil ditambahkan.');
    }

    public function destroy(Contact $contact)
    {
        $contact->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Kontak berhasil dihapus.',
            ]);
        }

        return redirect()->route('contacts')->with('success', 'Kontak berhasil dihapus.');
    }

    public function update(Request $request, Contact $contact)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'label' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $number = trim($validated['phone_number']);
        if (str_starts_with($number, '0')) {
            $number = '62'.substr($number, 1);
        } elseif (str_starts_with($number, '+')) {
            $number = substr($number, 1);
        } elseif (str_starts_with($number, '8')) {
            $number = '628'.substr($number, 1);
        }
        $validated['phone_number'] = $number;

        $contact->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Kontak berhasil diperbarui.',
            'contact' => $contact,
        ]);
    }

    public function toggleActive(Contact $contact)
    {
        $contact->update(['is_active' => ! $contact->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $contact->is_active,
            'message' => $contact->is_active ? 'Kontak diaktifkan.' : 'Kontak dinonaktifkan.',
        ]);
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids');
        if (is_array($ids) && count($ids) > 0) {
            Contact::whereIn('id', $ids)->delete();

            return response()->json([
                'success' => true,
                'message' => count($ids).' kontak berhasil dihapus.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Tidak ada kontak yang dipilih.',
        ], 400);
    }

    public function importCsv(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('excel_file');
        $handle = fopen($file->getRealPath(), 'r');
        if (! $handle) {
            return redirect()->route('contacts')->with('error', 'Gagal membuka file.');
        }

        // Remove BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $count = 0;
        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            // Delimiter fallback
            if (count($row) === 1) {
                $possibleRow = explode(';', $row[0]);
                if (count($possibleRow) > 1) {
                    $row = $possibleRow;
                } else {
                    $possibleRow = explode("\t", $row[0]);
                    if (count($possibleRow) > 1) {
                        $row = $possibleRow;
                    }
                }
            }

            $number = trim($row[0] ?? '');
            if (empty($number)) {
                continue;
            }

            // Clean phone number format
            if (str_starts_with($number, '0')) {
                $number = '62'.substr($number, 1);
            } elseif (str_starts_with($number, '+')) {
                $number = substr($number, 1);
            } elseif (str_starts_with($number, '8')) {
                $number = '628'.substr($number, 1);
            }

            $name = trim($row[1] ?? 'Contact '.$number);
            $label = trim($row[2] ?? 'Imported');

            Contact::updateOrCreate(
                ['phone_number' => $number],
                ['name' => $name, 'label' => $label]
            );
            $count++;
        }
        fclose($handle);

        return redirect()->route('contacts')->with('success', "$count kontak berhasil diimpor.");
    }

    public function bulkAddGroup(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'label' => 'required|string|max:255',
        ]);

        Contact::whereIn('id', $request->ids)->update(['label' => $request->label]);

        return response()->json([
            'success' => true,
            'message' => 'Berhasil menambahkan kontak ke grup '.$request->label,
        ]);
    }

    public function bulkBroadcast(Request $request, WhatsAppService $whatsapp)
    {
        $request->validate([
            'ids' => 'required|array',
            'message' => 'required|string',
            'attachment' => 'nullable|file|max:16384',
        ]);

        $attachmentUrl = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = time().'_'.$file->getClientOriginalName();
            $file->move(public_path('uploads/attachments'), $filename);
            $attachmentUrl = asset('uploads/attachments/'.$filename);
        }

        $contacts = Contact::whereIn('id', $request->ids)->get();
        $successCount = 0;

        foreach ($contacts as $contact) {
            $response = $whatsapp->sendMessage($contact->phone_number, $request->message, $attachmentUrl);
            $status = 'failed';
            if (isset($response['status']) && $response['status'] == true) {
                $status = 'sent';
                $successCount++;
            }

            MessageLog::create([
                'campaign_id' => null,
                'contact_id' => $contact->id,
                'message_body' => $request->message,
                'status' => $status,
                'sent_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "Broadcast terkirim ke $successCount dari ".$contacts->count().' kontak.',
        ]);
    }

    public function sendMessage(Request $request, Contact $contact, WhatsAppService $whatsapp)
    {
        $validated = $request->validate([
            'message' => 'required|string',
            'attachment' => 'nullable|file|max:16384',
        ]);

        $attachmentUrl = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = time().'_'.$file->getClientOriginalName();
            $file->move(public_path('uploads/attachments'), $filename);
            $attachmentUrl = asset('uploads/attachments/'.$filename);
        }

        $response = $whatsapp->sendMessage($contact->phone_number, $validated['message'], $attachmentUrl);

        // Record log
        $status = 'failed';
        if (isset($response['status']) && $response['status'] == true) {
            $status = 'sent';
        }

        MessageLog::create([
            'campaign_id' => null,
            'contact_id' => $contact->id,
            'message_body' => $validated['message'],
            'status' => $status,
            'sent_at' => now(),
        ]);

        if ($status == 'sent') {
            return redirect()->route('contacts')->with('success', 'Pesan langsung berhasil dikirim.');
        } else {
            return redirect()->route('contacts')->with('error', 'Gagal mengirim pesan langsung: WhatsApp Gateway Error.');
        }
    }

    public function duplicates()
    {
        $duplicates = Contact::select('phone_number', DB::raw('GROUP_CONCAT(id) as ids, GROUP_CONCAT(name) as names, COUNT(*) as count'))
            ->groupBy('phone_number')
            ->having('count', '>', 1)
            ->get();

        return view('contacts', [
            'contacts' => Contact::orderBy('created_at', 'desc')->paginate(10),
            'segments' => Contact::select('label', DB::raw('count(*) as total'))
                ->groupBy('label')->whereNotNull('label')->where('label', '<>', '')
                ->orderBy('total', 'desc')->get(),
            'duplicates' => $duplicates,
        ]);
    }

    public function mergeDuplicates(Request $request)
    {
        $phoneNumber = $request->input('phone_number');
        $contacts = Contact::where('phone_number', $phoneNumber)->orderBy('created_at', 'asc')->get();
        if ($contacts->count() < 2) {
            return response()->json(['success' => false, 'message' => 'Tidak ada duplikat.']);
        }
        $primary = $contacts->first();
        $mergedName = $primary->name.' / '.$contacts->skip(1)->pluck('name')->implode(' / ');
        $mergedLabel = collect([$primary->label, ...$contacts->skip(1)->pluck('label')])
            ->filter()->unique()->implode(', ');
        $primary->update(['name' => $mergedName, 'label' => $mergedLabel]);
        $idsToDelete = $contacts->skip(1)->pluck('id')->toArray();
        MessageLog::whereIn('contact_id', $idsToDelete)->update(['contact_id' => $primary->id]);
        Contact::whereIn('id', $idsToDelete)->delete();

        return response()->json(['success' => true, 'message' => 'Duplikat berhasil digabung.']);
    }
}
