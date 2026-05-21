<!-- TOP NAV BAR -->
<header class="flex justify-between items-center px-lg h-16 w-full bg-surface dark:bg-background border-b border-outline-variant dark:border-outline sticky top-0 z-40">
    <div class="flex items-center gap-xl flex-1">
        <div class="relative w-full max-w-md">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant" data-icon="search">search</span>
            <input class="w-full pl-10 pr-4 py-2 bg-surface-container-low border border-outline-variant rounded-lg focus:ring-2 focus:ring-primary/20 outline-none transition-all font-body-md" placeholder="Search analytics..." type="text"/>
        </div>
    </div>
        <div class="flex items-center gap-lg">
            <div class="flex items-center gap-md px-md py-1.5 bg-primary/10 rounded-full">
                <div class="w-2 h-2 rounded-full bg-primary animate-pulse"></div>
                <span class="font-label-md text-primary font-bold">Connected</span>
            </div>
            <button class="material-symbols-outlined text-on-surface-variant hover:text-primary transition-colors" data-icon="notifications">notifications</button>
            <a href="{{ route('profile.edit') }}" class="w-8 h-8 rounded-full overflow-hidden border border-outline-variant hover:border-primary transition-colors" title="Edit Profile">
                <img alt="User Profile" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBsrQhdyAfPPsF0Kj89tLVaCZGEFKpIeT2L77AbibNcoWyjqBx5dVAYaObcf5MtAvSZESLwpOicv4M-UfMCI1KNvLUrKQsjfkYRrt_yT1cUKb-E8_3pn2LNyz59KWb_u4fxYNpsZ75EFHSUzkuelYW5k0VGoN69cIdrB1Iz3X21EjtPqgPoYz_iUw9atz0N4sfj91s6UrgidxhmxHdCudKsPahdzYEIZKQ1Rryt-w7yEjxAcvJ8_0xvrBKJI-1_dO8P5Wj8IPa99ww0"/>
            </a>
        </div>
</header>
