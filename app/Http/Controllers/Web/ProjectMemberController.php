<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProjectMember\StoreProjectMemberRequest;
use App\Models\Project;
use App\Models\ProjectMember;
use Illuminate\Http\RedirectResponse;

class ProjectMemberController extends Controller
{
    public function store(StoreProjectMemberRequest $request, Project $project): RedirectResponse
    {
        ProjectMember::updateOrCreate(
            [
                'project_id' => $project->id,
                'employee_id' => $request->validated('employee_id'),
            ],
            [
                'role_in_project' => $request->validated('role_in_project'),
                'assigned_at' => $request->validated('assigned_at'),
                'left_at' => null,
                'status' => 'active',
            ]
        );

        return redirect()
            ->route('projects.show', $project)
            ->with('status', 'Miembro agregado al proyecto.');
    }

    public function destroy(Project $project, ProjectMember $member): RedirectResponse
    {
        $this->authorize('update', $project);

        abort_if($member->project_id !== $project->id, 404);

        $member->update([
            'status' => 'inactive',
            'left_at' => now()->toDateString(),
        ]);

        return redirect()
            ->route('projects.show', $project)
            ->with('status', 'Miembro removido del proyecto.');
    }
}
