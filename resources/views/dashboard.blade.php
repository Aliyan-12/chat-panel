<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <h2 class="text-2xl font-bold mb-6">Chat</h2>
                <div id="chat-app"></div>
            </div>
        </div>
    </div>
</x-app-layout>

@push('scripts')
<!-- No need to add script tag here as app.js is already loaded by Vite in the app layout -->
@endpush
