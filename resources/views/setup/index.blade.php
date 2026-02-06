<x-setup-layout>
    <div class="mb-4 text-sm text-gray-600">
        Welcome to the Gearent installation wizard. We will check your server environment to ensure everything is ready.
    </div>

    <div class="space-y-2">
        @foreach($requirements as $label => $met)
            <div class="flex justify-between items-center border-b pb-2">
                <span>{{ $label }}</span>
                @if($met)
                    <span class="text-green-600 font-bold">✔ OK</span>
                @else
                    <span class="text-red-600 font-bold">✘ Missing</span>
                @endif
            </div>
        @endforeach
    </div>

    <div class="mt-6 flex justify-end">
        @if($allMet)
            <a href="{{ route('setup.step1') }}" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                Next Step: Database
            </a>
        @else
            <button disabled class="px-4 py-2 bg-gray-400 text-white rounded cursor-not-allowed">
                Fix Requirements to Proceed
            </button>
        @endif
    </div>
</x-setup-layout>
