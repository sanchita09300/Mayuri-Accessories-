<!DOCTYPE html>

<html class="light" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>MAYURI | Admin Dashboard</title>
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=EB+Garamond:wght@400;500;600;700&amp;family=Source+Sans+3:wght@400;600;700&amp;display=swap" rel="stylesheet"/>
<!-- Material Symbols -->
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<!-- Tailwind CSS -->
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<!-- Style Guidance & Theme Config -->
<script id="tailwind-config">
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            "colors": {
                    "secondary-fixed": "#ebe0de",
                    "inverse-on-surface": "#f7efef",
                    "tertiary-container": "#8f6b70",
                    "surface-container-lowest": "#ffffff",
                    "error": "#ba1a1a",
                    "on-tertiary": "#ffffff",
                    "on-tertiary-fixed": "#2d1419",
                    "surface": "#fff8f7",
                    "surface-dim": "#e0d8d8",
                    "on-surface": "#1e1b1b",
                    "primary-fixed": "#ffd9de",
                    "on-tertiary-container": "#fffbff",
                    "tertiary": "#755358",
                    "on-primary-container": "#fffbff",
                    "background": "#fff8f7",
                    "tertiary-fixed-dim": "#e7bcc1",
                    "surface-container": "#f4ecec",
                    "outline": "#817475",
                    "on-primary": "#ffffff",
                    "on-primary-fixed": "#2c1519",
                    "on-secondary-container": "#6a6361",
                    "on-secondary-fixed-variant": "#4c4544",
                    "primary-fixed-dim": "#e5bdc2",
                    "surface-container-high": "#eee6e6",
                    "inverse-surface": "#332f30",
                    "on-tertiary-fixed-variant": "#5d3e43",
                    "primary": "#735459",
                    "outline-variant": "#d3c3c4",
                    "surface-container-highest": "#e9e1e0",
                    "error-container": "#ffdad6",
                    "on-background": "#1e1b1b",
                    "on-surface-variant": "#4f4445",
                    "tertiary-fixed": "#ffd9de",
                    "on-error": "#ffffff",
                    "inverse-primary": "#e5bdc2",
                    "surface-tint": "#76565b",
                    "secondary-fixed-dim": "#cec4c2",
                    "surface-variant": "#e9e1e0",
                    "on-secondary": "#ffffff",
                    "primary-container": "#8e6c71",
                    "on-error-container": "#93000a",
                    "secondary-container": "#ebe0de",
                    "on-secondary-fixed": "#1f1a1a",
                    "surface-container-low": "#faf2f1",
                    "on-primary-fixed-variant": "#5c3f44",
                    "secondary": "#645d5b",
                    "surface-bright": "#fff8f7"
            },
            "borderRadius": {
                    "DEFAULT": "0.25rem",
                    "lg": "0.5rem",
                    "xl": "0.75rem",
                    "full": "9999px"
            },
            "spacing": {
                    "gutter": "16px",
                    "sm": "16px",
                    "lg": "40px",
                    "xl": "64px",
                    "md": "24px",
                    "margin-mobile": "20px",
                    "margin-desktop": "80px",
                    "xs": "8px",
                    "base": "4px"
            },
            "fontFamily": {
                    "headline-sm": ["EB Garamond"],
                    "display-lg": ["EB Garamond"],
                    "button-text": ["Source Sans 3"],
                    "headline-md": ["EB Garamond"],
                    "display-lg-mobile": ["EB Garamond"],
                    "label-caps": ["Source Sans 3"],
                    "body-lg": ["Source Sans 3"],
                    "body-md": ["Source Sans 3"]
            },
            "fontSize": {
                    "headline-sm": ["24px", {"lineHeight": "32px", "fontWeight": "400"}],
                    "display-lg": ["48px", {"lineHeight": "56px", "letterSpacing": "-0.02em", "fontWeight": "400"}],
                    "button-text": ["14px", {"lineHeight": "20px", "letterSpacing": "0.1em", "fontWeight": "600"}],
                    "headline-md": ["32px", {"lineHeight": "40px", "fontWeight": "400"}],
                    "display-lg-mobile": ["36px", {"lineHeight": "42px", "letterSpacing": "-0.01em", "fontWeight": "400"}],
                    "label-caps": ["12px", {"lineHeight": "16px", "letterSpacing": "0.15em", "fontWeight": "600"}],
                    "body-lg": ["18px", {"lineHeight": "28px", "fontWeight": "400"}],
                    "body-md": ["16px", {"lineHeight": "24px", "fontWeight": "400"}]
            }
          },
        },
      }
    </script>
<style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 300, 'GRAD' 0, 'opsz' 24;
        }
        ::-webkit-scrollbar {
            width: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: #d3c3c4;
            border-radius: 10px;
        }
        .active-nav-bg {
            background: linear-gradient(90deg, rgba(158, 123, 128, 0.1) 0%, transparent 100%);
        }
    </style>
<style>
    body {
      min-height: max(884px, 100dvh);
    }
  </style>
  </head>
<body class="bg-background text-on-background font-body-md overflow-x-hidden">
<!-- Top Navigation Bar (Mobile & Desktop Global Context) -->
<header class="fixed top-0 left-0 w-full z-50 flex justify-between items-center px-margin-mobile md:px-margin-desktop h-16 bg-background/95 backdrop-blur-md border-b border-outline-variant/30">
<div class="flex items-center gap-4">
<button class="md:hidden text-primary" onclick="toggleSidebar()">
<span class="material-symbols-outlined" data-icon="menu">menu</span>
</button>
<h1 class="text-headline-md font-headline-md tracking-widest text-primary uppercase">MAYURI</h1>
</div>
<div class="flex items-center gap-sm md:gap-md">
<button class="hover:opacity-80 transition-opacity duration-300">
<span class="material-symbols-outlined text-primary" data-icon="search">search</span>
</button>
<button class="hover:opacity-80 transition-opacity duration-300">
<span class="material-symbols-outlined text-primary" data-icon="notifications">notifications</span>
</button>
<div class="h-8 w-8 rounded-full overflow-hidden border border-outline-variant">
<img alt="Profile" class="h-full w-full object-cover" data-alt="A professional headshot of a boutique manager with soft cinematic lighting. She wears elegant minimalist silver jewelry that reflects the boutique's sophisticated aesthetic. The background is a clean, blurred office space with neutral tones of cream and dusty rose, maintaining a high-end editorial feel." src="https://lh3.googleusercontent.com/aida-public/AB6AXuACLR7zx77Fpg9M9T99iutUDaCeQ7vD1WVoBBxO7l0-Bzo8y2I75j4g-f_VonbTTHdYAc3IcNVwjLivqItnyxmdkq5n4OV1NsWjbGte1RGawNhyaAamTS0MzVNfCzbRK9dusisq5K15cKXblJJpsU_6AwFg42k_lVADsvm2oUx8sQmerSn6qJEyiLgy2Jys-5ZpqoRI-PRoozdxCHI8jKDMeXgqBRs3UwKT6Xta7Aiikh3StjEig9TquomZ63LszttbK5-rpRgVS80"/>
</div>
</div>
</header>
<div class="flex pt-16 min-h-screen">
<!-- Sidebar Navigation -->
<aside class="fixed inset-y-0 left-0 z-40 w-64 pt-16 bg-surface-container-low border-r border-outline-variant/30 transition-transform -translate-x-full md:translate-x-0" id="sidebar">
<nav class="mt-lg px-sm space-y-base">
<a class="group flex items-center gap-md px-md py-sm rounded-xl active-nav-bg text-primary font-bold transition-all duration-200" href="#">
<span class="material-symbols-outlined" data-icon="dashboard">dashboard</span>
<span class="text-label-caps font-label-caps">DASHBOARD</span>
</a>
<a class="group flex items-center gap-md px-md py-sm rounded-xl text-on-surface-variant hover:text-primary hover:bg-surface-container-high transition-all duration-200" href="#">
<span class="material-symbols-outlined" data-icon="inventory_2">inventory_2</span>
<span class="text-label-caps font-label-caps">PRODUCTS</span>
</a>
<a class="group flex items-center gap-md px-md py-sm rounded-xl text-on-surface-variant hover:text-primary hover:bg-surface-container-high transition-all duration-200" href="#">
<span class="material-symbols-outlined" data-icon="add_circle">add_circle</span>
<span class="text-label-caps font-label-caps">ADD PRODUCT</span>
</a>
<a class="group flex items-center gap-md px-md py-sm rounded-xl text-on-surface-variant hover:text-primary hover:bg-surface-container-high transition-all duration-200" href="#">
<span class="material-symbols-outlined" data-icon="shopping_cart">shopping_cart</span>
<span class="text-label-caps font-label-caps">ORDERS</span>
</a>
<a class="group flex items-center gap-md px-md py-sm rounded-xl text-on-surface-variant hover:text-primary hover:bg-surface-container-high transition-all duration-200" href="#">
<span class="material-symbols-outlined" data-icon="group">group</span>
<span class="text-label-caps font-label-caps">CUSTOMERS</span>
</a>
<div class="pt-xl mt-xl border-t border-outline-variant/20">
<a class="group flex items-center gap-md px-md py-sm rounded-xl text-on-surface-variant hover:text-primary hover:bg-surface-container-high transition-all duration-200" href="#">
<span class="material-symbols-outlined" data-icon="settings">settings</span>
<span class="text-label-caps font-label-caps">SETTINGS</span>
</a>
</div>
</nav>
</aside>
<!-- Main Content Area -->
<main class="flex-1 md:ml-64 p-margin-mobile md:p-lg bg-background">
<!-- Dashboard Header -->
<div class="mb-lg">
<h2 class="text-headline-sm font-headline-sm text-primary">Overview</h2>
<p class="text-body-md text-on-surface-variant italic">Handcrafted elegance at a glance.</p>
</div>
<!-- Stats Bento Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-sm mb-lg">
<div class="bg-surface-container p-md border border-outline-variant/30 rounded-lg hover:scale-[1.02] transition-transform duration-300">
<div class="flex items-center justify-between mb-xs">
<span class="text-label-caps font-label-caps text-on-surface-variant">TOTAL PRODUCTS</span>
<span class="material-symbols-outlined text-primary" data-icon="grid_view">grid_view</span>
</div>
<p class="text-headline-sm font-headline-sm text-primary">1,284</p>
<p class="text-xs text-secondary mt-base">+4 this week</p>
</div>
<div class="bg-surface-container p-md border border-outline-variant/30 rounded-lg hover:scale-[1.02] transition-transform duration-300">
<div class="flex items-center justify-between mb-xs">
<span class="text-label-caps font-label-caps text-on-surface-variant">TOTAL ORDERS</span>
<span class="material-symbols-outlined text-primary" data-icon="receipt_long">receipt_long</span>
</div>
<p class="text-headline-sm font-headline-sm text-primary">856</p>
<p class="text-xs text-secondary mt-base">+12% vs last month</p>
</div>
<div class="bg-surface-container p-md border border-outline-variant/30 rounded-lg hover:scale-[1.02] transition-transform duration-300">
<div class="flex items-center justify-between mb-xs">
<span class="text-label-caps font-label-caps text-on-surface-variant">TOTAL CUSTOMERS</span>
<span class="material-symbols-outlined text-primary" data-icon="person_outline">person_outline</span>
</div>
<p class="text-headline-sm font-headline-sm text-primary">3,102</p>
<p class="text-xs text-secondary mt-base">89% retention rate</p>
</div>
<div class="bg-surface-container p-md border border-outline-variant/30 rounded-lg hover:scale-[1.02] transition-transform duration-300">
<div class="flex items-center justify-between mb-xs">
<span class="text-label-caps font-label-caps text-on-surface-variant">TOTAL REVENUE</span>
<span class="material-symbols-outlined text-primary" data-icon="payments">payments</span>
</div>
<p class="text-headline-sm font-headline-sm text-primary">$42,900</p>
<p class="text-xs text-secondary mt-base">Expected $50k this quarter</p>
</div>
</div>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-lg">
<!-- Recent Orders Table -->
<div class="lg:col-span-2 bg-white/50 backdrop-blur-sm p-md rounded-lg border border-outline-variant/30">
<div class="flex items-center justify-between mb-md">
<h3 class="text-headline-sm font-headline-sm text-primary">Recent Orders</h3>
<button class="text-label-caps font-label-caps text-primary hover:underline underline-offset-4">VIEW ALL</button>
</div>
<div class="overflow-x-auto">
<table class="w-full text-left">
<thead>
<tr class="border-b border-outline-variant/20">
<th class="py-sm text-label-caps font-label-caps text-on-surface-variant">ORDER ID</th>
<th class="py-sm text-label-caps font-label-caps text-on-surface-variant">CUSTOMER</th>
<th class="py-sm text-label-caps font-label-caps text-on-surface-variant">AMOUNT</th>
<th class="py-sm text-label-caps font-label-caps text-on-surface-variant">STATUS</th>
</tr>
</thead>
<tbody class="divide-y divide-outline-variant/10">
<tr>
<td class="py-md text-body-md font-bold text-primary">#MY-9021</td>
<td class="py-md text-body-md">Eleanor Vance</td>
<td class="py-md text-body-md">$245.00</td>
<td class="py-md">
<span class="px-xs py-1 text-[10px] font-semibold border border-primary text-primary rounded-full">SHIPPED</span>
</td>
</tr>
<tr>
<td class="py-md text-body-md font-bold text-primary">#MY-9022</td>
<td class="py-md text-body-md">Julianna Moore</td>
<td class="py-md text-body-md">$112.50</td>
<td class="py-md">
<span class="px-xs py-1 text-[10px] font-semibold border border-tertiary-container text-tertiary-container rounded-full">PENDING</span>
</td>
</tr>
<tr>
<td class="py-md text-body-md font-bold text-primary">#MY-9023</td>
<td class="py-md text-body-md">Sofia Richards</td>
<td class="py-md text-body-md">$560.00</td>
<td class="py-md">
<span class="px-xs py-1 text-[10px] font-semibold border border-primary text-primary rounded-full">SHIPPED</span>
</td>
</tr>
<tr>
<td class="py-md text-body-md font-bold text-primary">#MY-9024</td>
<td class="py-md text-body-md">Claire Bennet</td>
<td class="py-md text-body-md">$89.00</td>
<td class="py-md">
<span class="px-xs py-1 text-[10px] font-semibold border border-error text-error rounded-full uppercase">REFUNDED</span>
</td>
</tr>
</tbody>
</table>
</div>
</div>
<!-- Low Stock Alerts -->
<div class="space-y-md">
<div class="bg-surface-container-high p-md rounded-lg border border-outline-variant/50">
<div class="flex items-center gap-sm mb-md">
<span class="material-symbols-outlined text-error" data-icon="warning">warning</span>
<h3 class="text-label-caps font-label-caps text-on-surface">LOW STOCK ALERT</h3>
</div>
<div class="space-y-md">
<div class="flex items-center gap-md">
<div class="h-12 w-12 bg-white rounded flex-shrink-0 overflow-hidden border border-outline-variant/30">
<img alt="Product" data-alt="A close-up shot of a delicate, handcrafted silver necklace with a single pearl pendant, resting on a textured light mauve linen surface. The lighting is soft and directional, highlighting the metallic sheen and the organic curves of the jewelry. The aesthetic is clean, minimalist, and luxury-focused, echoing the MAYURI brand identity." src="https://lh3.googleusercontent.com/aida-public/AB6AXuBbWK0ESYlWzOxVFKwVt1kxKCA-qwTUFPVxV0HshW0djf6ppjhbrupgCuI5-ap0XnJwENphXcsUW_EvNc934YfkICRzt2YlDciwCPnrNOi-Qjiv5ZwhuF_0VT7FgSfu4QjnnstjdDQxNWcqiWkws_Le0gpz8YUw1LYiikSYCYxiWqj4KQqUChW0D8sQg88rcXBpolIVKVpmlQqepsJ_f5rJRjMGCDEhBwZzOao8T5EGmzDjH-eTr2aTF0XTy7hLxCVygr5QeExlrx4"/>
</div>
<div>
<p class="text-body-md font-bold text-primary">Lumina Pearl Drop</p>
<p class="text-xs text-error font-semibold">2 units remaining</p>
</div>
</div>
<div class="flex items-center gap-md">
<div class="h-12 w-12 bg-white rounded flex-shrink-0 overflow-hidden border border-outline-variant/30">
<img alt="Product" data-alt="A pair of minimalist gold hoop earrings styled on a white marble slab. The lighting is bright and airy with high-key whites and soft mauve shadows, creating a serene and premium boutique atmosphere. The focus is sharp on the intricate texture of the gold, conveying handcrafted quality and elegance." src="https://lh3.googleusercontent.com/aida-public/AB6AXuBY5e3wsfwQvIgvR07IKGKYTgZtCXUyfrsKdqBLV9LGQe42Nyi-cBR1tp7iAh5t4sqnTFiXgwsDMiC_sWY_aNcq-rnZkz7RXTD5hfIULDg_ep-I5XaDc4pHHX50g0yZHXkFCyT249rv66MUVnebNeLrK2F81_unSj9zhRfZjRxb4zirLu6MDBe30vcO8OJlRp8_vAd-zIdxegCTbR1_qCw4rqm6bBdBfokIYlkkQKlEd5SX3sxVDvwaCSncFfO-0cEu9jCP-bWOrwk"/>
</div>
<div>
<p class="text-body-md font-bold text-primary">Aura Gold Hoops</p>
<p class="text-xs text-error font-semibold">5 units remaining</p>
</div>
</div>
</div>
<button class="w-full mt-lg py-sm border border-primary text-primary text-label-caps font-label-caps hover:bg-primary hover:text-white transition-colors duration-300">
                            MANAGE INVENTORY
                        </button>
</div>
<!-- Quick Actions -->
<div class="bg-primary p-md rounded-lg text-white">
<h3 class="text-label-caps font-label-caps mb-md tracking-wider">QUICK ACTIONS</h3>
<div class="grid grid-cols-2 gap-sm">
<button class="flex flex-col items-center justify-center p-sm bg-white/10 hover:bg-white/20 rounded transition-colors gap-xs">
<span class="material-symbols-outlined" data-icon="add">add</span>
<span class="text-[10px] font-bold">NEW PRODUCT</span>
</button>
<button class="flex flex-col items-center justify-center p-sm bg-white/10 hover:bg-white/20 rounded transition-colors gap-xs">
<span class="material-symbols-outlined" data-icon="mail">mail</span>
<span class="text-[10px] font-bold">SEND CAMPAIGN</span>
</button>
</div>
</div>
</div>
</div>
</main>
</div>
<!-- Footer Component -->
<footer class="w-full px-margin-mobile py-lg flex flex-col items-center space-y-md text-center bg-surface-container-low border-t border-outline-variant/50 relative md:ml-64 md:w-[calc(100%-16rem)]">
<div class="text-headline-sm font-headline-sm tracking-widest text-primary">MAYURI</div>
<div class="flex flex-wrap justify-center gap-md">
<a class="text-label-caps font-label-caps text-on-surface-variant hover:text-primary transition-colors duration-300" href="#">COLLECTIONS</a>
<a class="text-label-caps font-label-caps text-on-surface-variant hover:text-primary transition-colors duration-300" href="#">OUR STORY</a>
<a class="text-label-caps font-label-caps text-on-surface-variant hover:text-primary transition-colors duration-300" href="#">SHIPPING</a>
<a class="text-label-caps font-label-caps text-on-surface-variant hover:text-primary transition-colors duration-300" href="#">CONTACT</a>
</div>
<p class="text-label-caps font-label-caps text-on-surface-variant/70 uppercase">Â© 2024 MAYURI ACCESSORIES. HANDCRAFTED ELEGANCE.</p>
</footer>
<!-- Mobile Navigation Shell Overlay -->
<div class="fixed inset-0 bg-black/50 z-30 hidden" id="sidebar-overlay" onclick="toggleSidebar()"></div>
<script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            const isOpen = sidebar.classList.contains('translate-x-0');

            if (isOpen) {
                sidebar.classList.remove('translate-x-0');
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            } else {
                sidebar.classList.add('translate-x-0');
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
            }
        }

        // Dashboard atmospheric effect: Subtle mouse-follow on cards
        document.querySelectorAll('.bg-surface-container').forEach(card => {
            card.addEventListener('mousemove', (e) => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                card.style.setProperty('--mouse-x', `${x}px`);
                card.style.setProperty('--mouse-y', `${y}px`);
                card.style.background = `radial-gradient(circle at ${x}px ${y}px, rgba(158, 123, 128, 0.05) 0%, #f4ecec 60%)`;
            });
            card.addEventListener('mouseleave', () => {
                card.style.background = '#f4ecec';
            });
        });
    </script>
</body></html>