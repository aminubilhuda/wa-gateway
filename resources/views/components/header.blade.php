<!-- TOP NAV BAR -->
<header class="flex justify-between items-center px-3 sm:px-4 md:px-lg h-14 lg:h-16 w-full bg-surface dark:bg-background border-b border-outline-variant dark:border-outline sticky top-0 z-30">
    <div class="flex items-center gap-2 sm:gap-3 lg:gap-xl flex-1 min-w-0">
        <!-- Hamburger (mobile only) -->
        <button onclick="toggleSidebar(true)" class="lg:hidden text-on-surface-variant hover:text-primary transition-colors cursor-pointer p-1 -ml-1">
            <span class="material-symbols-outlined text-[24px]">menu</span>
        </label>
        <!-- Search (hidden on small mobile) -->
        <div class="relative flex-1 max-w-md hidden sm:block">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant" data-icon="search">search</span>
            <input class="w-full pl-10 pr-4 py-1.5 lg:py-2 bg-surface-container-low border border-outline-variant rounded-lg focus:ring-2 focus:ring-primary/20 outline-none transition-all font-body-md text-sm" placeholder="Search analytics..." type="text"/>
        </div>
    </div>
    <div class="flex items-center gap-2 sm:gap-3 lg:gap-lg flex-shrink-0">
        <div class="flex items-center gap-1 sm:gap-md px-2 sm:px-md py-1 sm:py-1.5 bg-primary/10 rounded-full">
            <div class="w-2 h-2 rounded-full bg-primary animate-pulse"></div>
            <span class="font-label-md text-primary font-bold hidden sm:inline">Connected</span>
        </div>
        <button id="themeToggle" class="material-symbols-outlined text-on-surface-variant hover:text-primary transition-colors text-[20px] sm:text-[24px]" data-icon="{{ session('theme') === 'dark' ? 'light_mode' : 'dark_mode' }}">{{ session('theme') === 'dark' ? 'light_mode' : 'dark_mode' }}</button>
        <button class="material-symbols-outlined text-on-surface-variant hover:text-primary transition-colors text-[20px] sm:text-[24px]" data-icon="notifications">notifications</button>
        <a href="{{ route('profile.edit') }}" class="w-7 h-7 sm:w-8 sm:h-8 rounded-full overflow-hidden border border-outline-variant hover:border-primary transition-colors flex-shrink-0" title="Edit Profile">
            <img alt="User Profile" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBsrQhdyAfPPsF0Kj89tLVaCZGEFKpIeT2L77AbibNcoWyjqBx5dVAYaObcf5MtAvSZESLwpOicv4M-UfMCI1KNvLUrKQsjfkYRrt_yT1cUKb-E8_3pn2LNyz59KWb_u4fxYNpsZ75EFHSUzkuelYW5k0VGoN69cIdrB1Iz3X21EjtPqgPoYz_iUw9atz0N4sfj91s6UrgidxhmxHdCudKsPahdzYEIZKQ1Rryt-w7yEjxAcvJ8_0xvrBKJI-1_dO8P5Wj8IPa99ww0"/>
        </a>
    </div>
</header>
