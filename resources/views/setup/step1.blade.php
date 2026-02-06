<x-setup-layout>
    <div class="mb-4">
        <h2 class="text-xl font-bold">Database Configuration</h2>
        <p class="text-sm text-gray-600">Please enter your database connection details.</p>
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

    <form method="POST" action="{{ route('setup.step2') }}">
        @csrf
        
        <div class="mb-3">
            <label class="block text-sm font-medium text-gray-700">DB Host</label>
            <input type="text" name="db_host" value="{{ old('db_host', '127.0.0.1') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border">
        </div>

        <div class="mb-3">
            <label class="block text-sm font-medium text-gray-700">DB Port</label>
            <input type="text" name="db_port" value="{{ old('db_port', '3306') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border">
        </div>

        <div class="mb-3">
            <label class="block text-sm font-medium text-gray-700">DB Database Name</label>
            <input type="text" name="db_database" value="{{ old('db_database', 'gearent') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border">
        </div>

        <div class="mb-3">
            <label class="block text-sm font-medium text-gray-700">DB Username</label>
            <input type="text" name="db_username" value="{{ old('db_username', 'root') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border">
        </div>

        <div class="mb-3">
            <label class="block text-sm font-medium text-gray-700">DB Password</label>
            <input type="password" name="db_password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border">
        </div>

        <div class="mt-6 flex justify-between">
             <a href="{{ route('setup.index') }}" class="text-gray-600 underline">Back</a>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                Save & Test Connection
            </button>
        </div>
    </form>
</x-setup-layout>
