<div>
    <div class="sm:flex sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Projects</h1>
            <p class="mt-1 text-sm text-slate-600">Manage all projects within your company.</p>
        </div>
        
        @can('create', App\Models\Project::class)
        <div class="mt-4 sm:mt-0 bg-white p-4 rounded-xl shadow-sm border border-slate-200">
            <h3 class="text-sm font-semibold text-slate-900 mb-3">Create New Project</h3>
            <form wire:submit="createProject" class="flex items-start space-x-3">
                <div class="flex-1">
                    <input type="text" wire:model="name" placeholder="Project Name" required
                        class="appearance-none block w-full px-3 py-2 border border-slate-300 rounded-lg shadow-sm placeholder-slate-400 focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm">
                    @error('name') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div class="flex-1">
                    <input type="text" wire:model="description" placeholder="Description (optional)"
                        class="appearance-none block w-full px-3 py-2 border border-slate-300 rounded-lg shadow-sm placeholder-slate-400 focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm">
                    @error('description') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
                <button type="submit"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 transition-colors">
                    Create
                </button>
            </form>
            @if (session()->has('message'))
                <div class="mt-2 text-sm text-green-600">{{ session('message') }}</div>
            @endif
        </div>
        @endcan
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($projects as $project)
            <a href="/projects/{{ $project->id }}" class="block bg-white rounded-xl shadow-sm border border-slate-200 p-6 hover:shadow-md transition-shadow hover:border-brand-300">
                <div class="flex justify-between items-start">
                    <h3 class="text-lg font-semibold text-slate-900 truncate pr-4">{{ $project->name }}</h3>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-brand-100 text-brand-800">
                        {{ $project->tasks_count ?? $project->tasks()->count() }} Tasks
                    </span>
                </div>
                <p class="mt-2 text-sm text-slate-500 line-clamp-2">
                    {{ $project->description ?: 'No description provided.' }}
                </p>
                <div class="mt-4 flex items-center text-xs text-slate-400">
                    <svg class="mr-1.5 h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Created {{ $project->created_at->diffForHumans() }}
                </div>
            </a>
        @empty
            <div class="col-span-full bg-white rounded-xl shadow-sm border border-slate-200 p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-slate-900">No projects</h3>
                <p class="mt-1 text-sm text-slate-500">Get started by creating a new project.</p>
            </div>
        @endforelse
    </div>
</div>
