<?php

namespace App\Http\Controllers;

use App\Models\FamilyDocument;
use App\Services\AI\RagService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DocumentController extends Controller
{
    private const ALLOWED_EXTENSIONS = ['pdf', 'docx', 'doc', 'txt'];
    private const MAX_MB = 10;

    public function __construct(private readonly RagService $rag) {}

    public function index(): View
    {
        $documents = active_group()
            ->familyDocuments()
            ->latest()
            ->get();

        return view('agent.documents', compact('documents'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'document' => [
                'required',
                'file',
                'max:' . (self::MAX_MB * 1024),
                'mimes:pdf,doc,docx,txt,plain',
            ],
        ], [
            'document.required' => 'Vui lòng chọn file.',
            'document.max'      => 'File tối đa ' . self::MAX_MB . 'MB.',
            'document.mimes'    => 'Chỉ chấp nhận PDF, Word (.doc/.docx), TXT.',
        ]);

        $file      = $request->file('document');
        $filename  = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, self::ALLOWED_EXTENSIONS)) {
            return back()->with('error', 'Định dạng file không được hỗ trợ.');
        }

        $path = $file->store("documents/" . active_group()->id, 'local');

        $doc = active_group()->familyDocuments()->create([
            'user_id'      => Auth::id(),
            'filename'     => $filename,
            'disk'         => 'local',
            'path'         => $path,
            'content_type' => $file->getMimeType(),
            'status'       => 'processing',
        ]);

        // Process async qua queue
        dispatch(fn () => $this->rag->processDocument($doc))->afterResponse();

        return back()->with('success', "Đã upload \"{$filename}\". Đang xử lý, vui lòng đợi vài giây...");
    }

    public function destroy(FamilyDocument $document): RedirectResponse
    {
        abort_if($document->user_id !== Auth::id(), 403);

        Storage::disk($document->disk)->delete($document->path);
        $document->delete();

        return back()->with('success', 'Đã xóa tài liệu.');
    }

    /** Re-process: extract + embed lại */
    public function reprocess(FamilyDocument $document): RedirectResponse
    {
        abort_if($document->user_id !== Auth::id(), 403);

        $document->update(['status' => 'processing']);
        dispatch(fn () => $this->rag->processDocument($document))->afterResponse();

        return back()->with('success', 'Đang xử lý lại tài liệu...');
    }
}
