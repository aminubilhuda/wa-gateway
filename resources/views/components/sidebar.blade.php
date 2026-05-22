<!-- SIDE NAV BAR -->
<aside class="w-[240px] h-dvh overflow-y-auto fixed left-0 top-0 bg-secondary dark:bg-inverse-surface border-r border-outline-variant dark:border-outline flex flex-col px-md pb-md pt-0 space-y-base z-40 -translate-x-full peer-checked:translate-x-0 lg:translate-x-0 transition-transform duration-300 ease-in-out">
    <div class="mb-xl px-base pt-md flex items-center justify-between">
        <div>
            <h1 class="font-headline-md text-headline-md font-bold text-on-secondary">WA-Blast Pro</h1>
            <p class="text-label-md text-on-secondary/70">Enterprise SaaS</p>
        </div>
        <label for="sidebarToggle" class="lg:hidden text-on-secondary/70 hover:text-white transition-colors cursor-pointer p-1 -mr-1">
            <span class="material-symbols-outlined">close</span>
        </label>
    </div>
    <nav class="flex-1 space-y-xs">
        <a class="{{ request()->routeIs('dashboard') ? 'bg-primary-container text-on-primary-container' : 'text-on-secondary hover:bg-secondary-container/10' }} rounded-lg font-bold flex items-center gap-md p-md transition-transform duration-150 active:scale-95" href="{{ route('dashboard') }}">
            <span class="material-symbols-outlined" data-icon="dashboard">dashboard</span>
            <span class="font-body-md text-body-md">Dashboard</span>
        </a>
        <a class="{{ request()->routeIs('contacts') ? 'bg-primary-container text-on-primary-container font-bold' : 'text-on-secondary hover:bg-secondary-container/10' }} transition-colors flex items-center gap-md p-md rounded-lg active:scale-95" href="{{ route('contacts') }}">
            <span class="material-symbols-outlined" data-icon="group" {!! request()->routeIs('contacts') ? 'style="font-variation-settings: \'FILL\' 1;"' : '' !!}>group</span>
            <span class="font-body-md text-body-md">Contacts</span>
        </a>
        <a class="{{ request()->routeIs('campaigns') ? 'bg-primary-container text-on-primary-container font-bold' : 'text-on-secondary hover:bg-secondary-container/10' }} transition-colors flex items-center gap-md p-md rounded-lg active:scale-95" href="{{ route('campaigns') }}">
            <span class="material-symbols-outlined" data-icon="send" {!! request()->routeIs('campaigns') ? 'style="font-variation-settings: \'FILL\' 1;"' : '' !!}>send</span>
            <span class="font-body-md text-body-md">Campaigns</span>
        </a>
        <a class="{{ request()->routeIs('reports') ? 'bg-primary-container text-on-primary-container font-bold' : 'text-on-secondary hover:bg-secondary-container/10' }} transition-colors flex items-center gap-md p-md rounded-lg active:scale-95" href="{{ route('reports') }}">
            <span class="material-symbols-outlined" data-icon="history" {!! request()->routeIs('reports') ? 'style="font-variation-settings: \'FILL\' 1;"' : '' !!}>history</span>
            <span class="font-body-md text-body-md">Reports</span>
        </a>
        <a class="{{ request()->routeIs('auto-reply') ? 'bg-primary-container text-on-primary-container font-bold' : 'text-on-secondary hover:bg-secondary-container/10' }} transition-colors flex items-center gap-md p-md rounded-lg active:scale-95" href="{{ route('auto-reply') }}">
            <span class="material-symbols-outlined" data-icon="smart_toy" {!! request()->routeIs('auto-reply') ? 'style="font-variation-settings: \'FILL\' 1;"' : '' !!}>smart_toy</span>
            <span class="font-body-md text-body-md">Auto Reply</span>
        </a>
        <a class="{{ request()->routeIs('templates') ? 'bg-primary-container text-on-primary-container font-bold' : 'text-on-secondary hover:bg-secondary-container/10' }} transition-colors flex items-center gap-md p-md rounded-lg active:scale-95" href="{{ route('templates') }}">
            <span class="material-symbols-outlined" data-icon="description" {!! request()->routeIs('templates') ? 'style="font-variation-settings: \'FILL\' 1;"' : '' !!}>description</span>
            <span class="font-body-md text-body-md">Templates</span>
        </a>
        <a class="{{ request()->routeIs('blacklist') ? 'bg-primary-container text-on-primary-container font-bold' : 'text-on-secondary hover:bg-secondary-container/10' }} transition-colors flex items-center gap-md p-md rounded-lg active:scale-95" href="{{ route('blacklist') }}">
            <span class="material-symbols-outlined" data-icon="block" {!! request()->routeIs('blacklist') ? 'style="font-variation-settings: \'FILL\' 1;"' : '' !!}>block</span>
            <span class="font-body-md text-body-md">Blacklist</span>
        </a>
    </nav>
    <div class="mt-auto space-y-xs border-t border-white/10 pt-md">
        <a href="{{ route('campaigns') }}" class="w-full bg-primary-container text-on-primary-container p-md rounded-lg font-bold flex items-center justify-center gap-xs mb-md hover:brightness-105 transition-all text-center">
            <span class="material-symbols-outlined" data-icon="add">add</span>
            New Campaign
        </a>
        <a class="{{ request()->routeIs('profile.edit') ? 'bg-primary-container text-on-primary-container font-bold' : 'text-on-secondary hover:bg-secondary-container/10' }} transition-colors flex items-center gap-md p-md rounded-lg active:scale-95" href="{{ route('profile.edit') }}">
            <span class="material-symbols-outlined" data-icon="person" {!! request()->routeIs('profile.edit') ? 'style="font-variation-settings: \'FILL\' 1;"' : '' !!}>person</span>
            <span class="font-body-md text-body-md">Profile</span>
        </a>
        <a class="{{ request()->routeIs('settings') ? 'bg-primary-container text-on-primary-container font-bold' : 'text-on-secondary hover:bg-secondary-container/10' }} transition-colors flex items-center gap-md p-md rounded-lg active:scale-95" href="{{ route('settings') }}">
            <span class="material-symbols-outlined" data-icon="settings" {!! request()->routeIs('settings') ? 'style="font-variation-settings: \'FILL\' 1;"' : '' !!}>settings</span>
            <span class="font-body-md text-body-md">Settings</span>
        </a>
        <button onclick="toggleSupportModal(true)" class="w-full text-left text-on-secondary hover:bg-secondary-container/10 transition-colors flex items-center gap-md p-md rounded-lg">
            <span class="material-symbols-outlined" data-icon="help_outline">help_outline</span>
            <span class="font-body-md text-body-md">Support</span>
        </button>
        <form action="{{ route('logout') }}" method="POST" class="w-full">
            @csrf
            <button type="submit" class="w-full text-left text-on-secondary hover:bg-red-500/10 hover:text-red-400 transition-colors flex items-center gap-md p-md rounded-lg">
                <span class="material-symbols-outlined" data-icon="logout">logout</span>
                <span class="font-body-md text-body-md">Logout</span>
            </button>
        </form>
    </div>
</aside>
