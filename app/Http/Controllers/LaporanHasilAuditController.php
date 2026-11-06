<?php

namespace App\Http\Controllers;

use App\Models\JwbKasus;
use App\Models\LaporanHasilAudit\LaporanHasilAudit;
use App\Models\LaporanHasilAudit\LaporanHasilAuditFile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LaporanHasilAuditController extends Controller
{
    private const MAX_FILE_SIZE_KB = 16383;

    private const FILE_METADATA_COLUMNS = [
        'id',
        'laporan_hasil_audit_id',
        'jenis_file',
        'tipe_input',
        'file_name',
        'mime_type',
        'link',
    ];

    private const ALLOWED_MIME_EXTENSIONS = [
        'pdf',
        'doc',
        'docx',
        'xls',
        'xlsx',
        'jpg',
        'jpeg',
        'png',
    ];

    public function index(Request $request): JsonResponse
    {
        $reports = LaporanHasilAudit::query()
            ->whereHas('jwbKasus', fn (Builder $query) => $query->forUser($request->user()))
            ->with(['files' => fn (Builder $query) => $query->select(self::FILE_METADATA_COLUMNS)])
            ->get()
            ->map(fn (LaporanHasilAudit $report) => $this->reportData($report));

        return response()->json([
            'success' => true,
            'data' => $reports,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'JwbKasusID' => ['required', 'integer', 'exists:jwb_kasus,JwbKasusID'],
        ]);

        $jwbKasus = JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $request->integer('JwbKasusID'))
            ->firstOrFail();
        $report = $jwbKasus->laporanHasilAudit()
            ->with(['files' => fn (Builder $query) => $query->select(self::FILE_METADATA_COLUMNS)])
            ->first();

        $validated = $this->validateReport($request, $report);
        $wasCreated = $report === null;

        $report = $this->saveReport($request, $jwbKasus, $report, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Laporan Hasil Audit berhasil disimpan.',
            'data' => $this->reportData($report),
        ], $wasCreated ? 201 : 200);
    }

    public function show(Request $request, int $laporanHasilAudit): JsonResponse
    {
        $report = $this->accessibleReport($request, $laporanHasilAudit);

        return response()->json([
            'success' => true,
            'data' => $this->reportData($report),
        ]);
    }

    public function update(Request $request, int $laporanHasilAudit): JsonResponse
    {
        $report = $this->accessibleReport($request, $laporanHasilAudit);
        $jwbKasus = JwbKasus::forUser($request->user())
            ->where('JwbKasusID', $report->JwbKasusID)
            ->firstOrFail();

        $validated = $this->validateReport($request, $report);
        $report = $this->saveReport($request, $jwbKasus, $report, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Laporan Hasil Audit berhasil disimpan.',
            'data' => $this->reportData($report),
        ]);
    }

    public function destroy(Request $request, int $laporanHasilAudit): JsonResponse
    {
        $report = $this->accessibleReport($request, $laporanHasilAudit);
        $report->delete();

        return response()->json([
            'success' => true,
            'message' => 'Laporan Hasil Audit berhasil dihapus.',
        ]);
    }

    public function showFile(
        Request $request,
        int $laporanHasilAudit,
        string $jenisFile
    ) {
        $report = $this->accessibleReport($request, $laporanHasilAudit);
        $document = $report->files()
            ->where('jenis_file', $jenisFile)
            ->first();

        abort_if(
            ! $document || $document->tipe_input !== 'file' || $document->file_data === null,
            404,
            'File tidak ditemukan.'
        );

        $filename = preg_replace(
            '/[^A-Za-z0-9._-]/',
            '_',
            $document->file_name ?: 'dokumen'
        ) ?: 'dokumen';
        $disposition = str_starts_with($document->mime_type ?? '', 'image/')
            || $document->mime_type === 'application/pdf'
            ? 'inline'
            : 'attachment';

        return response($document->file_data, 200, [
            'Content-Type' => $document->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function accessibleReport(
        Request $request,
        int $laporanHasilAuditId
    ): LaporanHasilAudit {
        return LaporanHasilAudit::query()
            ->whereHas('jwbKasus', fn (Builder $query) => $query->forUser($request->user()))
            ->with(['files' => fn (Builder $query) => $query->select(self::FILE_METADATA_COLUMNS)])
            ->findOrFail($laporanHasilAuditId);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateReport(
        Request $request,
        ?LaporanHasilAudit $report
    ): array {
        $rules = [
            'opini_audit' => [
                'required',
                'string',
                Rule::in(LaporanHasilAudit::OPINI_AUDIT),
            ],
            'files' => ['required', 'array', 'size:8'],
            'files.*.jenis_file' => [
                'required',
                'string',
                'distinct',
                Rule::in(LaporanHasilAuditFile::JENIS_FILE),
            ],
            'files.*.tipe_input' => [
                'required',
                'string',
                Rule::in(LaporanHasilAuditFile::TIPE_INPUT),
            ],
        ];

        $documents = $request->input('files', []);
        if (is_array($documents)) {
            foreach ($documents as $index => $document) {
                if (! is_array($document)) {
                    continue;
                }

                $hasExistingFile = $report
                    ? $report->files()
                        ->where('jenis_file', $document['jenis_file'] ?? null)
                        ->whereNotNull('file_data')
                        ->exists()
                    : false;

                if (($document['tipe_input'] ?? null) === 'file') {
                    $fileRules = [
                        'sometimes',
                        'file',
                        'mimes:'.implode(',', self::ALLOWED_MIME_EXTENSIONS),
                        'max:'.self::MAX_FILE_SIZE_KB,
                    ];

                    if (! $hasExistingFile) {
                        array_unshift($fileRules, 'required');
                    }

                    $rules["files.$index.file"] = $fileRules;
                    $rules["files.$index.link"] = ['prohibited'];
                } elseif (($document['tipe_input'] ?? null) === 'link') {
                    $rules["files.$index.file"] = ['prohibited'];
                    $rules["files.$index.link"] = [
                        'required',
                        'string',
                        'url',
                        'max:5000',
                    ];
                }
            }
        }

        $validated = $request->validate($rules);

        if (count(array_unique(array_column($validated['files'], 'jenis_file'))) !== count(LaporanHasilAuditFile::JENIS_FILE)
            || array_diff(LaporanHasilAuditFile::JENIS_FILE, array_column($validated['files'], 'jenis_file'))) {
            throw ValidationException::withMessages([
                'files' => ['Kirim tepat satu dokumen untuk setiap jenis file LHA.'],
            ]);
        }

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function saveReport(
        Request $request,
        JwbKasus $jwbKasus,
        ?LaporanHasilAudit $report,
        array $validated
    ): LaporanHasilAudit {
        return DB::transaction(function () use ($request, $jwbKasus, $report, $validated) {
            $lockedCase = JwbKasus::forUser($request->user())
                ->where('JwbKasusID', $jwbKasus->JwbKasusID)
                ->lockForUpdate()
                ->firstOrFail();

            $report ??= $lockedCase->laporanHasilAudit()->first()
                ?? new LaporanHasilAudit();
            $report->JwbKasusID = $lockedCase->JwbKasusID;
            $report->opini_audit = $validated['opini_audit'];
            $report->save();

            foreach ($validated['files'] as $index => $document) {
                $values = [
                    'tipe_input' => $document['tipe_input'],
                    'link' => null,
                ];

                if ($document['tipe_input'] === 'link') {
                    $values['file_data'] = null;
                    $values['file_name'] = null;
                    $values['mime_type'] = null;
                    $values['link'] = $document['link'];
                } elseif ($uploadedFile = $request->file("files.$index.file")) {
                    $values['file_data'] = $uploadedFile->get();
                    $values['file_name'] = $uploadedFile->getClientOriginalName();
                    $values['mime_type'] = $uploadedFile->getMimeType();
                }

                $report->files()->updateOrCreate(
                    ['jenis_file' => $document['jenis_file']],
                    $values
                );
            }

            return $report->load([
                'files' => fn (Builder $query) => $query->select(self::FILE_METADATA_COLUMNS),
            ]);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function reportData(LaporanHasilAudit $report): array
    {
        return [
            'id' => $report->id,
            'JwbKasusID' => $report->JwbKasusID,
            'opini_audit' => $report->opini_audit,
            'files' => $report->files->map(fn (LaporanHasilAuditFile $file) => [
                'id' => $file->id,
                'jenis_file' => $file->jenis_file,
                'tipe_input' => $file->tipe_input,
                'file_name' => $file->file_name,
                'mime_type' => $file->mime_type,
                'link' => $file->link,
                'file_url' => $file->tipe_input === 'file'
                    ? route('laporan-hasil-audit.files.show', [
                        $report->id,
                        $file->jenis_file,
                    ])
                    : null,
            ])->values(),
        ];
    }
}