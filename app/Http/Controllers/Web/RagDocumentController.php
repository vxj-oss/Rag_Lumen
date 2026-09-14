<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\RagDocument\StoreRagDocumentRequest;
use App\Jobs\ProcessRagDocument;
use App\Models\Project;
use App\Models\RagDocument;
use App\Support\Enums\RagDocumentStatus;
use App\Support\Enums\RagSourceType;
use App\Support\Enums\RoleName;
use App\Support\RagAccessScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class RagDocumentController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', RagDocument::class);

        $documents = $this->visibleDocumentsQuery()
            ->with(['project', 'uploader'])
            ->orderByDesc('created_at')
            ->paginate(5)
            ->withQueryString();

        return view('rag.html.documents', [
            'documents' => $documents,
            'projects' => $this->availableProjects(),
        ]);
    }

    public function store(StoreRagDocumentRequest $request): RedirectResponse
    {
        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        $sourceType = match ($extension) {
            'pdf' => RagSourceType::Pdf,
            'docx' => RagSourceType::Docx,
            'txt' => RagSourceType::Txt,
            'md' => RagSourceType::Markdown,
            'csv' => RagSourceType::Csv,
            default => RagSourceType::Other,
        };

        $path = $file->store(config('rag.storage_path'), config('rag.storage_disk'));

        $document = RagDocument::create([
            'project_id' => $request->validated('project_id'),
            'uploaded_by' => $request->user()->id,
            'title' => $request->validated('title'),
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'source_type' => $sourceType->value,
            'status' => RagDocumentStatus::Pending->value,
        ]);

        ProcessRagDocument::dispatch($document->id);

        return redirect()
            ->route('rag-documents.index')
            ->with('status', 'Documento subido correctamente. Está pendiente de procesamiento.');
    }

    public function retry(RagDocument $ragDocument): RedirectResponse
    {
        $this->authorize('update', $ragDocument);

        $ragDocument->update(['status' => RagDocumentStatus::Pending->value, 'failure_reason' => null]);

        ProcessRagDocument::dispatch($ragDocument->id);

        return redirect()
            ->route('rag-documents.index')
            ->with('status', 'Documento enviado a reprocesar.');
    }

    public function destroy(RagDocument $ragDocument): RedirectResponse
    {
        $this->authorize('delete', $ragDocument);

        Storage::disk(config('rag.storage_disk'))->delete($ragDocument->file_path);
        $ragDocument->delete();

        return redirect()
            ->route('rag-documents.index')
            ->with('status', 'Documento eliminado correctamente.');
    }

    private function visibleDocumentsQuery()
    {
        $projectIds = RagAccessScope::accessibleProjectIds(auth()->user());

        return RagDocument::query()->when(
            $projectIds !== null,
            function ($query) use ($projectIds) {
                $query->where(function ($query) use ($projectIds) {
                    $query->whereNull('project_id');

                    if ($projectIds !== []) {
                        $query->orWhereIn('project_id', $projectIds);
                    }
                });
            }
        );
    }

    private function availableProjects()
    {
        $user = auth()->user();

        if ($user->hasRole(RoleName::Administrator->value)) {
            return Project::orderBy('name')->get();
        }

        return Project::where('responsible_employee_id', $user->employee?->id)->orderBy('name')->get();
    }
}
