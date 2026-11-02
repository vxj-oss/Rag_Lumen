<?php

namespace Tests\Feature\Access;

use App\Models\{Area, Client, Employee, Project, RagDocument, User};
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResponsiveListsTest extends TestCase
{
    use RefreshDatabase;

    private function fixtures(): User
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('administrator');
        Employee::factory()->count(7)->create(['especialidad' => 'backend']);
        User::factory()->count(7)->create();
        Project::factory()->count(7)->create();
        for ($i = 1; $i <= 7; $i++) {
            Area::create(['nombre' => 'Área móvil '.$i, 'activa' => true]);
            Client::create(['nombre' => 'Cliente móvil '.$i, 'estado' => 'active']);
            RagDocument::create(['subido_por' => $admin->id, 'titulo' => 'Documento móvil '.$i,
                'nombre_archivo' => 'movil.txt', 'ruta_archivo' => 'private/movil.txt',
                'tipo_mime' => 'text/plain', 'tipo_origen' => 'txt']);
        }
        return $admin;
    }

    public function test_mobile_lists_have_two_records_and_real_second_pages(): void
    {
        $this->actingAs($this->fixtures())->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) Mobile');
        foreach (['employees.index' => 'employees', 'projects.index' => 'projects', 'clients.index' => 'clients',
            'areas.index' => 'areas', 'users.index' => 'users', 'rag-documents.index' => 'documents'] as $route => $variable) {
            $first = $this->get(route($route))->assertOk()->assertViewHas($variable, fn ($page) => $page->count() === 2 && $page->perPage() === 2 && $page->hasMorePages());
            $firstIds = $first->viewData($variable)->pluck('id');
            $next = $this->get(route($route, ['page' => 2]))->assertOk();
            $this->assertCount(2, $next->viewData($variable));
            $this->assertTrue($firstIds->intersect($next->viewData($variable)->pluck('id'))->isEmpty(), $route.' must reach new records.');
        }
    }

    public function test_display_preference_is_bounded_and_explicit_size_takes_priority(): void
    {
        $this->actingAs($this->fixtures())->withUnencryptedCookie('workspace_list_size', '2');
        $this->get(route('employees.index'))->assertOk()->assertViewHas('employees', fn ($page) => $page->perPage() === 2);
        $this->get(route('employees.index', ['per_page' => 5]))->assertOk()->assertViewHas('employees', fn ($page) => $page->perPage() === 5);
        $this->get(route('employees.index', ['per_page' => 1000]))->assertOk()->assertViewHas('employees', fn ($page) => $page->perPage() === 5);
    }

    public function test_edit_dialogs_are_outside_tables_and_desktop_only_containers(): void
    {
        $this->actingAs($this->fixtures());
        foreach (['employees.index', 'projects.index', 'clients.index', 'areas.index'] as $route) {
            $html = $this->get(route($route))->assertOk()->getContent();
            $dom = new \DOMDocument();
            $previous = libxml_use_internal_errors(true);
            $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
            libxml_clear_errors(); libxml_use_internal_errors($previous);
            $xpath = new \DOMXPath($dom);
            $dialogs = $xpath->query('//*[@role="dialog" and starts-with(@aria-label,"Editar")]');
            $this->assertGreaterThan(0, $dialogs->length, $route);
            foreach ($dialogs as $dialog) {
                $this->assertSame(0, $xpath->query('ancestor::table | ancestor::*[contains(concat(" ", normalize-space(@class), " "), " hidden ")]', $dialog)->length, $route);
            }
        }
    }

    public function test_mobile_project_sections_and_document_sources_are_paginated(): void
    {
        $this->actingAs($this->fixtures())->withHeader('Sec-CH-UA-Mobile', '?1');
        $project = Project::first();
        \App\Models\Task::factory()->count(7)->create(['proyecto_id' => $project->id]);
        RagDocument::query()->update(['proyecto_id' => $project->id]);
        $response = $this->get(route('projects.show', $project))->assertOk();
        foreach (['workspaceTasks', 'workspaceDocuments', 'workspaceHistory'] as $variable) {
            $this->assertSame(2, $response->viewData($variable)->perPage());
        }
        $this->assertCount(2, $response->viewData('workspaceTasks'));
        $this->assertCount(2, $response->viewData('workspaceDocuments'));
        $next = $this->get(route('projects.show', ['project' => $project, 'documents_page' => 2, 'tab' => 'documents']))->assertOk();
        $this->assertSame(2, $next->viewData('workspaceDocuments')->currentPage());
        $this->assertStringContainsString('tab=documents', $next->viewData('workspaceDocuments')->nextPageUrl());
        $document = RagDocument::first();
        for ($i = 0; $i < 7; $i++) $document->chunks()->create(['indice_fragmento' => $i, 'contenido' => 'Fragmento '.$i]);
        $this->get(route('rag-documents.source', $document))->assertOk()->assertViewHas('chunks', fn ($page) => $page->count() === 2 && $page->total() === 7);
        $this->get(route('rag-documents.source', ['ragDocument' => $document, 'page' => 2]))->assertOk()->assertViewHas('chunks', fn ($page) => $page->first()->indice_fragmento === 2);
    }
}
