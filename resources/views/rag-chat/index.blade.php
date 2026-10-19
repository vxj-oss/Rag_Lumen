<x-app-layout>
    <x-slot name="header"><x-ui.page-header :title="__('Asistente del sistema')" :subtitle="__('Consulta tus registros y documentos con contexto, por texto o voz')" /></x-slot>
    <x-assistant.widget :embedded="true" :projects="$projects" />
</x-app-layout>
