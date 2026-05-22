<!DOCTYPE html>
<html class="light" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>{{ config('app.name', 'WA-Blast Pro') }} | Dashboard</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "surface-dim": "#cbdbf5",
                        "on-secondary": "#ffffff",
                        "on-tertiary-container": "#46494b",
                        "surface-variant": "#d3e4fe",
                        "inverse-surface": "#213145",
                        "on-primary-container": "#005523",
                        "on-background": "#0b1c30",
                        "on-error": "#ffffff",
                        "inverse-primary": "#3de273",
                        "on-secondary-fixed": "#111c2d",
                        "background": "#f8f9ff",
                        "secondary-fixed-dim": "#bcc7de",
                        "error": "#ba1a1a",
                        "inverse-on-surface": "#eaf1ff",
                        "on-surface-variant": "#3c4a3d",
                        "tertiary-fixed": "#e0e3e5",
                        "on-secondary-fixed-variant": "#3c475a",
                        "surface-container-lowest": "#ffffff",
                        "secondary": "#545f73",
                        "on-primary": "#ffffff",
                        "surface-container-highest": "#d3e4fe",
                        "on-tertiary": "#ffffff",
                        "primary": "#006d2f",
                        "primary-fixed": "#66ff8e",
                        "secondary-fixed": "#d8e3fb",
                        "surface-container": "#e5eeff",
                        "surface": "#f8f9ff",
                        "on-primary-fixed-variant": "#005322",
                        "surface-tint": "#006d2f",
                        "on-tertiary-fixed-variant": "#444749",
                        "outline-variant": "#bbcbb9",
                        "tertiary": "#5c5f61",
                        "error-container": "#ffdad6",
                        "on-tertiary-fixed": "#191c1e",
                        "outline": "#6c7b6b",
                        "on-surface": "#0b1c30",
                        "primary-container": "#25d366",
                        "secondary-container": "#d5e0f8",
                        "tertiary-fixed-dim": "#c4c7c9",
                        "surface-container-high": "#dce9ff",
                        "surface-bright": "#f8f9ff",
                        "surface-container-low": "#eff4ff",
                        "tertiary-container": "#b6b8ba",
                        "on-secondary-container": "#586377",
                        "on-error-container": "#93000a",
                        "primary-fixed-dim": "#3de273",
                        "on-primary-fixed": "#002109"
                    },
                    "borderRadius": {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                    "spacing": {
                        "md": "16px",
                        "base": "8px",
                        "xl": "32px",
                        "gutter": "16px",
                        "container-margin": "24px",
                        "sm": "12px",
                        "lg": "24px",
                        "xs": "4px"
                    },
                    "fontFamily": {
                        "headline-lg": ["Inter"],
                        "label-md": ["Inter"],
                        "headline-lg-mobile": ["Inter"],
                        "headline-md": ["Inter"],
                        "display": ["Inter"],
                        "label-sm": ["Inter"],
                        "body-lg": ["Inter"],
                        "body-md": ["Inter"]
                    },
                    "fontSize": {
                        "headline-lg": ["28px", {"lineHeight": "36px", "letterSpacing": "-0.01em", "fontWeight": "600"}],
                        "label-md": ["12px", {"lineHeight": "16px", "letterSpacing": "0.01em", "fontWeight": "500"}],
                        "headline-lg-mobile": ["24px", {"lineHeight": "32px", "fontWeight": "600"}],
                        "headline-md": ["20px", {"lineHeight": "28px", "fontWeight": "600"}],
                        "display": ["36px", {"lineHeight": "44px", "letterSpacing": "-0.02em", "fontWeight": "700"}],
                        "label-sm": ["11px", {"lineHeight": "14px", "fontWeight": "600"}],
                        "body-lg": ["16px", {"lineHeight": "24px", "fontWeight": "400"}],
                        "body-md": ["14px", {"lineHeight": "20px", "fontWeight": "400"}]
                    }
                }
            }
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        body {
            background-color: #f8f9ff;
            font-family: 'Inter', sans-serif;
        }
        .bento-card {
            background: #ffffff;
            border: 1px solid #E2E8F0;
            border-radius: 0.75rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .bento-card:hover {
            box-shadow: 0 4px 12px rgba(30, 41, 59, 0.04);
            transform: translateY(-2px);
        }
        .status-chip {
            padding: 2px 10px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 600;
        }
        .chart-bar {
            transition: height 1s ease-out;
        }
    </style>
    @stack('styles')
</head>
<body class="text-on-background">

    <!-- SIDEBAR TOGGLE (hidden checkbox) -->
    <input type="checkbox" id="sidebarToggle" class="hidden peer" autocomplete="off">

    <!-- OVERLAY for mobile sidebar -->
    <label for="sidebarToggle" class="fixed inset-0 bg-black/50 z-30 hidden peer-checked:block lg:hidden cursor-pointer transition-opacity duration-300"></label>

    @include('components.sidebar')

    <!-- MAIN CONTENT WRAPPER -->
    <main class="lg:ml-[240px] min-h-screenlg:pt-0">

        @include('components.header')

        <div class="p-3 sm:p-4 md:p-md lg:p-lg space-y-3 sm:space-y-4 md:space-y-md lg:space-y-lg">
            @yield('content')
        </div>
    </main>

    <!-- FLOATING ACTION BUTTON -->
    <button onclick="toggleSupportModal(true)" class="fixed bottom-4 right-4 sm:bottom-lg sm:right-lg w-12 h-12 sm:w-14 sm:h-14 bg-primary-container text-on-primary-container rounded-full shadow-lg flex items-center justify-center hover:scale-110 active:scale-95 transition-all z-50">
        <span class="material-symbols-outlined text-[24px] sm:text-[28px]" data-icon="bolt" style="font-variation-settings: 'FILL' 1;">bolt</span>
    </button>

    <!-- SUPPORT MODAL -->
    <div id="supportModal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-3 sm:p-base transition-opacity duration-300">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg overflow-hidden border border-outline-variant transform transition-transform duration-300 scale-95 mx-3 sm:mx-0">
            <div class="px-4 sm:px-lg py-md bg-secondary text-white flex justify-between items-center">
                <div class="flex items-center gap-xs">
                    <span class="material-symbols-outlined text-[24px]">support_agent</span>
                    <h3 class="font-headline-sm text-headline-sm font-bold">Help &amp; Customer Support</h3>
                </div>
                <button onclick="toggleSupportModal(false)" class="text-white/80 hover:text-white transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <div class="p-4 sm:p-lg space-y-4 sm:space-y-lg">
                <p class="font-body-md text-on-surface-variant">Butuh bantuan menggunakan WA-Blast Pro? Tim teknis kami siap membantu Anda menyelesaikan masalah apa pun.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-md">
                    <a href="https://wa.me/6281234567890" target="_blank" class="p-md border border-outline-variant hover:bg-surface-container rounded-xl flex flex-col items-center justify-center text-center gap-base transition-all group hover:border-primary">
                        <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center text-primary group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined text-[28px]" data-icon="chat">chat</span>
                        </div>
                        <span class="font-bold text-body-lg text-on-surface">WhatsApp Chat</span>
                        <span class="text-label-sm text-on-surface-variant">Fast response (08.00 - 21.00 WIB)</span>
                    </a>
                    <a href="mailto:support@wa-blast-pro.com" class="p-md border border-outline-variant hover:bg-surface-container rounded-xl flex flex-col items-center justify-center text-center gap-base transition-all group hover:border-primary">
                        <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center text-primary group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined text-[28px]" data-icon="mail">mail</span>
                        </div>
                        <span class="font-bold text-body-lg text-on-surface">Email Support</span>
                        <span class="text-label-sm text-on-surface-variant">For complex inquiries</span>
                    </a>
                </div>

                <div class="bg-surface-container-low p-md rounded-lg border border-outline-variant/30 space-y-base">
                    <h4 class="font-bold text-body-md text-on-surface">Panduan Pemecahan Masalah Cepat</h4>
                    <ul class="list-disc pl-md text-label-md text-on-surface-variant space-y-xs">
                        <li>Pastikan status koneksi WhatsApp di Dashboard adalah <strong>Connected</strong>.</li>
                        <li>Gunakan prefix nomor <code>628...</code> untuk format CSV/excel.</li>
                        <li>Atur cron job di hosting Anda jika scheduler pengiriman tertunda.</li>
                    </ul>
                </div>
            </div>
            <div class="px-4 sm:px-lg py-md border-t border-outline-variant bg-surface-container-lowest flex justify-end">
                <button type="button" onclick="toggleSupportModal(false)" class="px-md py-sm font-label-lg font-bold bg-primary text-white rounded-lg hover:shadow-md transition-shadow">Tutup</button>
            </div>
        </div>
    </div>

    <script>
        function toggleSupportModal(show) {
            const modal = document.getElementById('supportModal');
            if (show) {
                modal.classList.remove('hidden');
                setTimeout(() => {
                    modal.querySelector('.scale-95')?.classList.remove('scale-95');
                    modal.querySelector('.scale-95')?.classList.add('scale-100');
                }, 10);
            } else {
                const sub = modal.querySelector('.scale-100') || modal.firstElementChild;
                sub.classList.remove('scale-100');
                sub.classList.add('scale-95');
                setTimeout(() => {
                    modal.classList.add('hidden');
                }, 150);
            }
        }
    </script>

    @stack('scripts')
</body>
</html>
