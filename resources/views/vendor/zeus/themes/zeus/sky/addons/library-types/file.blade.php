<x-filament::button tag="a" target="_blank" href="{{ $file->getFullUrl() }}" class="mx-auto">
    {{ $file->getFullUrl() }} {{ __('zeus-sky::cms.library.show_file') }}
</x-filament::button>