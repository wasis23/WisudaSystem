<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PeriodeWisuda;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ManualBookController extends Controller
{
    /**
     * Upload or update manual book PDF for the active wisuda period.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'manual_book' => 'required|file|mimes:pdf|max:20480', // max 20MB
            'periode_id' => 'nullable|exists:periode_wisuda,id',
        ], [
            'manual_book.required' => 'File berkas manual book PDF wajib dipilih.',
            'manual_book.mimes' => 'Format berkas harus berupa dokumen PDF (.pdf).',
            'manual_book.max' => 'Ukuran berkas manual book maksimal 20 MB.',
        ]);

        $periode = null;
        if ($request->filled('periode_id')) {
            $periode = PeriodeWisuda::find($request->periode_id);
        }
        if (!$periode) {
            $periode = PeriodeWisuda::getActive() ?? PeriodeWisuda::latest()->first();
        }

        if (!$periode) {
            return redirect()->back()->with('error', 'Belum ada periode wisuda aktif yang terdaftar.');
        }

        // Delete old file if exists
        if ($periode->manual_book_pdf && Storage::disk('public')->exists($periode->manual_book_pdf)) {
            Storage::disk('public')->delete($periode->manual_book_pdf);
        }

        $file = $request->file('manual_book');
        $filename = 'manual_book_periode_' . $periode->id . '_' . time() . '.pdf';
        $path = $file->storeAs('manual_book', $filename, 'public');

        $periode->update([
            'manual_book_pdf' => $path,
        ]);

        return redirect()->back()->with('success', 'Buku Panduan Manual Book PDF berhasil diunggah dan otomatis aktif di dashboard wisudawan.');
    }

    /**
     * Remove manual book PDF.
     */
    public function destroy(Request $request)
    {
        $periode = null;
        if ($request->filled('periode_id')) {
            $periode = PeriodeWisuda::find($request->periode_id);
        }
        if (!$periode) {
            $periode = PeriodeWisuda::getActive() ?? PeriodeWisuda::latest()->first();
        }

        if ($periode && $periode->manual_book_pdf) {
            if (Storage::disk('public')->exists($periode->manual_book_pdf)) {
                Storage::disk('public')->delete($periode->manual_book_pdf);
            }
            $periode->update(['manual_book_pdf' => null]);
        }

        return redirect()->back()->with('success', 'Buku Panduan Manual Book PDF berhasil dihapus dari sistem.');
    }

    /**
     * Download or view manual book PDF.
     */
    public function download(Request $request)
    {
        $periode = PeriodeWisuda::getActive() ?? PeriodeWisuda::latest()->first();

        if ($periode && $periode->manual_book_pdf && Storage::disk('public')->exists($periode->manual_book_pdf)) {
            $fullPath = Storage::disk('public')->path($periode->manual_book_pdf);
            return response()->file($fullPath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="Manual_Book_Wisuda_' . ($periode->nomor_periode ? 'Ke_' . $periode->nomor_periode : 'Politeknik_Indonusa') . '.pdf"',
            ]);
        }

        // Fallback check if static file exists in public directory
        if (file_exists(public_path('manual_book.pdf'))) {
            return response()->file(public_path('manual_book.pdf'), [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="Manual_Book_Wisuda_Politeknik_Indonusa.pdf"',
            ]);
        }

        return redirect()->back()->with('error', 'Dokumen Buku Panduan Manual Book belum diunggah oleh panitia.');
    }
}
