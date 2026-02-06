<x-setup-layout>
    <div class="mb-4">
        <h2 class="text-xl font-bold">Installation</h2>
        <p class="text-sm text-gray-600">We are ready to install the application tables and data.</p>
    </div>

    @if ($errors->any())
        <div class="mb-4 p-2 bg-red-100 border border-red-400 text-red-700 rounded">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-blue-50 border border-blue-200 rounded p-4 mb-4 text-sm text-blue-800">
        Clicking the button below will run database migrations and seed default data. This may take a few seconds.
    </div>

    <div class="mt-6 flex justify-center">
        <a href="{{ route('setup.step4') }}" class="px-6 py-3 bg-indigo-600 text-white rounded hover:bg-indigo-700 font-bold">
            Run Installation
        </a>
    </div>
</x-setup-layout>
