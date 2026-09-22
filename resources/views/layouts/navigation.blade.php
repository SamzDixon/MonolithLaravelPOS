{{-- Mobile backdrop --}}
<div x-cloak
     x-show="sidebarOpen"
     @click="sidebarOpen = false"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-40 bg-slate-900/50 lg:hidden"></div>

{{-- Sidebar --}}
<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
       class="fixed inset-y-0 left-0 z-50 w-64 bg-blue-900 text-blue-100 flex flex-col transform transition-transform duration-200 ease-in-out lg:translate-x-0">

    {{-- Brand --}}
    <div class="flex items-center h-16 px-5 border-b border-blue-800">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
            <x-application-logo class="h-8 w-auto fill-current text-white" />
            <span class="font-semibold text-white tracking-tight">RetailPay</span>
        </a>
    </div>

    {{-- Nav --}}
    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1 text-sm">

        {{-- Dashboard --}}
        <a href="{{ route('dashboard') }}"
           class="flex items-center gap-3 px-3 py-2 rounded transition {{ request()->routeIs('dashboard') ? 'bg-blue-800 text-white' : 'text-blue-100 hover:bg-blue-800/60 hover:text-white' }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            <span>Dashboard</span>
        </a>

        {{-- Stock (expandable) --}}
        @if (auth()->user()->isAdmin() || auth()->user()->isBranchManager())
            <div>
                <button type="button" @click="openStock = !openStock"
                        class="w-full flex items-center justify-between gap-3 px-3 py-2 rounded transition {{ request()->routeIs('stock-*') ? 'bg-blue-800 text-white' : 'text-blue-100 hover:bg-blue-800/60 hover:text-white' }}">
                    <span class="flex items-center gap-3">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <span>Stock</span>
                    </span>
                    <svg :class="openStock ? 'rotate-90' : ''"
                         class="w-4 h-4 transition-transform duration-150"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>

                <div x-show="openStock" x-collapse class="mt-1 ml-4 pl-3 border-l border-blue-800 space-y-1">
                    <a href="{{ route('stock-levels.index') }}"
                       class="block px-3 py-1.5 rounded text-sm transition {{ request()->routeIs('stock-levels.*') ? 'bg-blue-800 text-white' : 'text-blue-200 hover:bg-blue-800/60 hover:text-white' }}">
                        Current Levels
                    </a>
                    <a href="{{ route('stock-movements.index') }}"
                       class="block px-3 py-1.5 rounded text-sm transition {{ request()->routeIs('stock-movements.*') ? 'bg-blue-800 text-white' : 'text-blue-200 hover:bg-blue-800/60 hover:text-white' }}">
                        Movement History
                    </a>
                </div>
            </div>
        @endif

        {{-- Sales (expandable) --}}
        <div>
            <button type="button" @click="openSales = !openSales"
                    class="w-full flex items-center justify-between gap-3 px-3 py-2 rounded transition {{ request()->routeIs('sales.*') ? 'bg-blue-800 text-white' : 'text-blue-100 hover:bg-blue-800/60 hover:text-white' }}">
                <span class="flex items-center gap-3">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                    <span>Sales</span>
                </span>
                <svg :class="openSales ? 'rotate-90' : ''"
                    class="w-4 h-4 transition-transform duration-150"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>

            <div x-show="openSales" x-collapse class="mt-1 ml-4 pl-3 border-l border-blue-800 space-y-1">
                @can('create', App\Models\Sale::class)
                    <a href="{{ route('sales.create') }}"
                    class="block px-3 py-1.5 rounded text-sm transition {{ request()->routeIs('sales.create') ? 'bg-blue-800 text-white' : 'text-blue-200 hover:bg-blue-800/60 hover:text-white' }}">
                        POS
                    </a>
                @endcan
                <a href="{{ route('sales.index') }}"
                class="block px-3 py-1.5 rounded text-sm transition {{ request()->routeIs('sales.index') || request()->routeIs('sales.show') ? 'bg-blue-800 text-white' : 'text-blue-200 hover:bg-blue-800/60 hover:text-white' }}">
                    Sales History
                </a>
            </div>
        </div>

        {{-- Transfers --}}
        <a href="{{ route('transfers.index') }}"
           class="flex items-center gap-3 px-3 py-2 rounded transition {{ request()->routeIs('transfers.*') ? 'bg-blue-800 text-white' : 'text-blue-100 hover:bg-blue-800/60 hover:text-white' }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
            </svg>
            <span>Transfers</span>
        </a>

        {{-- Setup (expandable, admin only) --}}
        @if (auth()->user()->isAdmin())
            <div>
                <button type="button" @click="openSetup = !openSetup"
                        class="w-full flex items-center justify-between gap-3 px-3 py-2 rounded transition {{ request()->routeIs('branches.*') || request()->routeIs('stores.*') || request()->routeIs('products.*') || request()->routeIs('users.*') ? 'bg-blue-800 text-white' : 'text-blue-100 hover:bg-blue-800/60 hover:text-white' }}">
                    <span class="flex items-center gap-3">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span>Setup</span>
                    </span>
                    <svg :class="openSetup ? 'rotate-90' : ''"
                         class="w-4 h-4 transition-transform duration-150"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>

                <div x-show="openSetup" x-collapse class="mt-1 ml-4 pl-3 border-l border-blue-800 space-y-1">
                    <a href="{{ route('branches.index') }}"
                       class="block px-3 py-1.5 rounded text-sm transition {{ request()->routeIs('branches.*') ? 'bg-blue-800 text-white' : 'text-blue-200 hover:bg-blue-800/60 hover:text-white' }}">
                        Branches
                    </a>
                    <a href="{{ route('stores.index') }}"
                       class="block px-3 py-1.5 rounded text-sm transition {{ request()->routeIs('stores.*') ? 'bg-blue-800 text-white' : 'text-blue-200 hover:bg-blue-800/60 hover:text-white' }}">
                        Stores
                    </a>
                    <a href="{{ route('users.index') }}"
                       class="block px-3 py-1.5 rounded text-sm transition {{ request()->routeIs('users.*') ? 'bg-blue-800 text-white' : 'text-blue-200 hover:bg-blue-800/60 hover:text-white' }}">
                        Users
                    </a>
                    <a href="{{ route('products.index') }}"
                       class="block px-3 py-1.5 rounded text-sm transition {{ request()->routeIs('products.*') ? 'bg-blue-800 text-white' : 'text-blue-200 hover:bg-blue-800/60 hover:text-white' }}">
                        Products
                    </a>
                </div>
            </div>
        @endif

    </nav>

    {{-- User footer --}}
    <div class="border-t border-blue-800 p-3">
        <div class="flex items-center justify-between gap-2">
            <div class="min-w-0">
                <div class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</div>
                <div class="text-xs text-blue-300 truncate">{{ ucfirst(str_replace('_', ' ', auth()->user()->role)) }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="p-2 text-blue-200 hover:text-white hover:bg-blue-800/60 rounded"
                        title="Log out">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </button>
            </form>
        </div>
    </div>
</aside>