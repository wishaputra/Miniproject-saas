<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Project Management SaaS' }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased text-slate-900 selection:bg-brand-500 selection:text-white">
    <div class="min-h-full">
        @auth
        <nav class="bg-white border-b border-slate-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center">
                            <span class="text-xl font-bold tracking-tight text-brand-600">MiniSaaS</span>
                        </div>
                        <div class="hidden sm:-my-px sm:ml-8 sm:flex sm:space-x-8">
                            <a href="{{ route('home') }}" class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('home', 'projects.*') ? 'border-brand-500 text-slate-900' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }} text-sm font-medium transition-colors">
                                Dashboard
                            </a>
                            @if(auth()->user() && auth()->user()->isAdmin())
                            <a href="{{ route('users.index') }}" class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('users.*') ? 'border-brand-500 text-slate-900' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }} text-sm font-medium transition-colors">
                                Users
                            </a>
                            <a href="{{ route('activity-logs.index') }}" class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('activity-logs.*') ? 'border-brand-500 text-slate-900' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }} text-sm font-medium transition-colors">
                                Activity Logs
                            </a>
                            @endif
                        </div>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:items-center">
                        <div class="ml-3 relative group">
                            <div class="flex items-center gap-3 cursor-pointer">
                                <div class="flex flex-col text-right">
                                    <span class="text-sm font-medium text-slate-700">{{ auth()->user()->name }}</span>
                                    <span class="text-xs text-slate-500">{{ auth()->user()->company->name ?? '' }} ({{ ucfirst(auth()->user()->role) }})</span>
                                </div>
                                <div class="h-8 w-8 rounded-full bg-brand-100 flex items-center justify-center text-brand-700 font-bold">
                                    {{ substr(auth()->user()->name, 0, 1) }}
                                </div>
                            </div>
                        </div>
                        <form method="POST" action="/logout" class="ml-6">
                            @csrf
                            <button type="submit" class="text-sm font-medium text-slate-500 hover:text-slate-700 transition-colors">
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>
        @endauth

        <main class="py-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                {{ $slot }}
            </div>
        </main>
    </div>
</body>
</html>
