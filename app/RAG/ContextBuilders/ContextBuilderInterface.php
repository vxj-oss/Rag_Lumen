<?php

namespace App\RAG\ContextBuilders;

use App\Models\Project;
use App\Models\Task;

interface ContextBuilderInterface
{
    
    public function build(string $query, array $retrieved, ?Project $project = null, ?Task $task = null): string;
}
