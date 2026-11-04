<?php

namespace App\Http\Controllers;

use App\Models\LaporanHasilAudit;
use App\Models\LaporanHasilAuditFile;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LaporanHasilAuditFileController extends Controller
{
    public function store(
        Request $request,
        LaporanHasilAudit $laporanHasilAudit,
        string $jenisFile
    ) {
        abort_unless(
            in_array($jenisFile, LaporanHasilAuditFile::JENIS_FILE, true),
            404
        );

        $tipeInput = $request->input('tipe_input');

        if ($tipeInput === 'file') {
            $validated = $request->validate([
                'tipe_input' => ['required', Rule::in(['file', 'link'])],
                'file' => [
                    'required',
                    'file',
                    'max:2048',
                    'mimes:pdf,doc,docx,txt,xlsx,csv',
                ],
            ]);

            $file = $request->file('file');

            $document = $laporanHasilAudit->files()->updateOrCreate(
                ['jenis_file' => $jenisFile],
                [
                    'tipe_input' => 'file',
                    'file_data' => $file->get(),
                    'file_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'link' => null,
                ]
            );
        } elseif ($tipeInput === 'link') {
            $validated = $request->validate([
                'tipe_input' => ['required', Rule::in(['file', 'link'])],
                'link' => ['required', 'url'],
            ]);

            $document = $laporanHasilAudit->files()->updateOrCreate(
                ['jenis_file' => $jenisFile],
                [
                    'tipe_input' => 'link',
                    'file_data' => null,
                    'file_name' => null,
                    'mime_type' => null,
                    'link' => $validated['link'],
                ]
            );
        } else {
            return response()->json([
                'message' => 'Tipe input harus berupa file atau link.',
            ], 422);
        }

        return response()->json([
            'message' => 'Dokumen berhasil disimpan.',
            'data' => $document,
        ]);
    }

    public function show(
        LaporanHasilAudit $laporanHasilAudit,
        string $jenisFile
    ) {
        abort_unless(
            in_array($jenisFile, LaporanHasilAuditFile::JENIS_FILE, true),
            404
        );

        $document = $laporanHasilAudit->files()
            ->where('jenis_file', $jenisFile)
            ->firstOrFail();

        if ($document->tipe_input === 'link') {
            return response()->json([
                'jenis_file' => $document->jenis_file,
                'tipe_input' => $document->tipe_input,
                'link' => $document->link,
            ]);
        }

        return response($document->file_data, 200, [
            'Content-Type' => $document->mime_type,
            'Content-Disposition' => "inline; filename*=UTF-8''" . rawurlencode($document->file_name),
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function destroy(
        LaporanHasilAudit $laporanHasilAudit,
        string $jenisFile
    ) {
        abort_unless(
            in_array($jenisFile, LaporanHasilAuditFile::JENIS_FILE, true),
            404
        );

        $document = LaporanHasilAuditFile::where('laporan_hasil_audit_id', $laporanHasilAudit->id)
            ->where('jenis_file', $jenisFile)
            ->firstOrFail();

        $document->delete();

        return response()->json([
            'message' => 'Dokumen berhasil dihapus.',
        ]);
    }
}