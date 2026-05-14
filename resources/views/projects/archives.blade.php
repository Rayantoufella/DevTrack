@php
    use Illuminate\Support\Str;

    $user = auth()->user();

    $initials = function ($name) {
        return collect(explode(' ', trim((string) $name)))
            ->filter()
            ->take(2)
            ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))
            ->implode('');
    };

    $userInitials = $initials($user->name) ?: 'U';

    // Filtre courant : all | shipped | paused
    $filter = in_array(request('filter'), ['shipped', 'paused']) ? request('filter') : 'all';

    // Calcul du % et du statut de chaque projet archivé
    $rows = $projects->map(function ($project) {
        $total = (int) $project->tasks_count;
        $done  = (int) $project->completed_tasks_count;
        $pct   = $total > 0 ? (int) round($done / $total * 100) : 0;

        return [
            'project' => $project,
            'total'   => $total,
            'done'    => $done,
            'pct'     => $pct,
            'shipped' => $total > 0 && $pct === 100,
        ];
    });

    $totalCount   = $rows->count();
    $shippedCount = $rows->where('shipped', true)->count();
    $pausedCount  = $totalCount - $shippedCount;

    // Application du filtre
    $visible = $rows->filter(function ($r) use ($filter) {
        if ($filter === 'shipped') return $r['shipped'];
        if ($filter === 'paused')  return ! $r['shipped'];
        return true;
    });
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>DevTrack — Archives</title>

<script src="https://cdn.tailwindcss.com"></script>

<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet" />

<script>
  tailwind.config = {
    theme: {
      extend: {
        colors: {
          'electric-blue': '#2563EB',
          'electric-cyan': '#06B6D4',
          'neon-blue':     '#3B82F6',
          'deep-blue':     '#1E3A8A',
          'ink':           '#0A0A0A',
          'cream':         '#FFFBEB',
          'lime-accent':   '#84CC16',
          'amber-accent':  '#F59E0B',
          'red-accent':    '#EF4444',
          'pink-pop':      '#EC4899',
          'gray-soft':     '#F5F5F5',
          'blue-mist':     '#EFF6FF',
        },
        fontFamily: {
          display: ['"Space Grotesk"', 'sans-serif'],
          body:    ['Inter', 'sans-serif'],
          mono:    ['"JetBrains Mono"', 'monospace'],
        },
        boxShadow: {
          'brutal-sm': '4px 4px 0 0 #0A0A0A',
          'brutal':    '6px 6px 0 0 #0A0A0A',
          'brutal-lg': '8px 8px 0 0 #0A0A0A',
        }
      }
    }
  }
</script>

<style>
  html, body { background: #FFFBEB; color: #0A0A0A; }
  body { font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; }
  .font-display { font-family: 'Space Grotesk', sans-serif; letter-spacing: -0.02em; }
  .font-mono    { font-family: 'JetBrains Mono', monospace; }

  .brutal-tap {
    transition: transform 180ms cubic-bezier(.2,.8,.2,1), box-shadow 180ms cubic-bezier(.2,.8,.2,1);
  }
  .brutal-tap:hover  { transform: translate(-2px,-2px); box-shadow: 8px 8px 0 0 #0A0A0A; }
  .brutal-tap:active { transform: translate(2px,2px); box-shadow: 2px 2px 0 0 #0A0A0A; transition-duration: 80ms; }

  .dot-grid {
    background-image: radial-gradient(circle, rgba(10,10,10,.10) 1.2px, transparent 1.2px);
    background-size: 22px 22px;
  }

  /* SIDEBAR */
  .nav-item {
    display: flex; align-items: center; gap: 12px;
    padding: 11px 14px; border-radius: 10px; border: 3px solid transparent;
    font-family: 'Space Grotesk', sans-serif; font-weight: 600; font-size: 14px;
    color: #0A0A0A; cursor: pointer; transition: all 160ms ease;
    position: relative; text-decoration: none;
  }
  .nav-item:hover {
    background: #FFFFFF; border-color: #0A0A0A;
    box-shadow: 4px 4px 0 0 #0A0A0A; transform: translate(-1px, -1px);
  }
  .nav-item.active {
    background: #2563EB; color: #FFFFFF; border-color: #0A0A0A;
    box-shadow: 4px 4px 0 0 #0A0A0A;
  }
  .nav-item .badge {
    margin-left: auto; font-family: 'JetBrains Mono', monospace;
    font-size: 11px; font-weight: 700; background: #FFFBEB; color: #0A0A0A;
    border: 2px solid #0A0A0A; border-radius: 6px; padding: 1px 6px;
  }
  .nav-item.active .badge { background: #FFFFFF; }

  .avatar {
    width: 32px; height: 32px; border-radius: 9999px; border: 2.5px solid #0A0A0A;
    display: grid; place-items: center;
    font-family: 'Space Grotesk', sans-serif; font-weight: 700; font-size: 11px;
  }
  .avatar.lg { width: 40px; height: 40px; font-size: 13px; }

  /* ARCHIVED PROJECT CARD — desaturated grayscale */
  .arch-card {
    background: #F5F5F5;
    border: 3px solid #0A0A0A;
    border-radius: 12px;
    padding: 22px;
    box-shadow: 6px 6px 0 0 #0A0A0A;
    position: relative;
    overflow: hidden;
    transition: transform 200ms cubic-bezier(.2,.8,.2,1), box-shadow 200ms cubic-bezier(.2,.8,.2,1), filter 200ms ease;
    filter: grayscale(0.4);
  }
  .arch-card:hover {
    transform: translate(-3px, -3px);
    box-shadow: 9px 9px 0 0 #0A0A0A;
    filter: grayscale(0);
  }

  /* Diagonal "ARCHIVED" sticker */
  .archived-stamp {
    position: absolute;
    top: 22px; right: -42px;
    transform: rotate(34deg);
    background: #0A0A0A; color: #FFFFFF;
    border: 2.5px solid #0A0A0A;
    box-shadow: 3px 3px 0 0 #FFFFFF, 6px 6px 0 0 #0A0A0A;
    font-family: 'JetBrains Mono', monospace; font-weight: 700;
    font-size: 11px; letter-spacing: 0.16em;
    padding: 4px 44px; z-index: 5;
  }
  .arch-card:hover .archived-stamp {
    background: #EF4444;
    transform: rotate(34deg) translateY(-2px);
  }
  .archived-stamp.paused { background: #F59E0B; color: #0A0A0A; }

  /* Progress bar */
  .arch-prog {
    height: 10px; background: #FFFFFF;
    border: 2.5px solid #0A0A0A; border-radius: 6px; overflow: hidden;
  }
  .arch-prog .fill {
    height: 100%;
    background-image: repeating-linear-gradient(45deg, #0A0A0A, #0A0A0A 6px, #6B7280 6px, #6B7280 7px);
  }
  .arch-prog.partial .fill {
    background-image: repeating-linear-gradient(45deg, #6B7280, #6B7280 6px, #9CA3AF 6px, #9CA3AF 7px);
  }

  /* CHIPS */
  .meta-chip {
    display: inline-flex; align-items: center; gap: 6px;
    background: #FFFFFF; color: #0A0A0A; border: 2px solid #0A0A0A;
    border-radius: 9999px; padding: 3px 10px;
    font-family: 'JetBrains Mono', monospace; font-weight: 600; font-size: 11px;
    box-shadow: 2px 2px 0 0 #0A0A0A;
  }
  .meta-chip.dark  { background: #0A0A0A; color: #FFFFFF; }
  .meta-chip.lime  { background: #84CC16; }
  .meta-chip.amber { background: #F59E0B; }

  /* MINI BUTTONS */
  .btn-mini {
    display: inline-flex; align-items: center; justify-content: center; gap: 6px;
    background: #FFFFFF; color: #0A0A0A;
    border: 2.5px solid #0A0A0A; border-radius: 8px;
    padding: 8px 12px;
    font-family: 'Space Grotesk', sans-serif; font-weight: 700; font-size: 11px;
    letter-spacing: 0.06em; text-transform: uppercase;
    box-shadow: 3px 3px 0 0 #0A0A0A; cursor: pointer;
    transition: all 160ms cubic-bezier(.2,.8,.2,1);
  }
  .btn-mini:hover { transform: translate(-2px,-2px); box-shadow: 5px 5px 0 0 #0A0A0A; }
  .btn-mini.success:hover { background: #84CC16; box-shadow: 5px 5px 0 0 #84CC16; }
  .btn-mini.danger:hover  { background: #EF4444; color: #FFFFFF; box-shadow: 5px 5px 0 0 #EF4444; }

  /* HEADER ICON BUTTON */
  .icon-btn {
    width: 44px; height: 44px;
    background: #FFFFFF; border: 3px solid #0A0A0A; border-radius: 10px;
    display: grid; place-items: center;
    box-shadow: 4px 4px 0 0 #0A0A0A; cursor: pointer;
    transition: all 180ms cubic-bezier(.2,.8,.2,1);
  }
  .icon-btn:hover { transform: translate(-2px,-2px); box-shadow: 6px 6px 0 0 #0A0A0A; }

  /* SEARCH */
  .search-input {
    width: 100%; background: #FFFFFF;
    border: 3px solid #0A0A0A; border-radius: 10px;
    padding: 10px 14px 10px 42px;
    font-family: 'Inter', sans-serif; font-weight: 500; font-size: 14px;
    transition: all 160ms ease;
  }
  .search-input:focus {
    outline: none; border-color: #2563EB;
    box-shadow: 4px 4px 0 0 #2563EB; transform: translate(-2px,-2px);
  }

  /* FILTER TAB */
  .filter-tab {
    display: inline-flex; align-items: center; gap: 8px;
    background: #FFFFFF; border: 3px solid #0A0A0A; border-radius: 10px;
    padding: 9px 14px;
    font-family: 'Space Grotesk', sans-serif; font-weight: 700; font-size: 12px;
    text-transform: uppercase; letter-spacing: 0.04em;
    box-shadow: 4px 4px 0 0 #0A0A0A; cursor: pointer; text-decoration: none;
    transition: all 180ms cubic-bezier(.2,.8,.2,1);
  }
  .filter-tab:hover { transform: translate(-2px,-2px); box-shadow: 6px 6px 0 0 #0A0A0A; }
  .filter-tab.active { background: #0A0A0A; color: #FFFFFF; }
  .filter-tab .count {
    font-family: 'JetBrains Mono', monospace;
    background: #FFFBEB; color: #0A0A0A; border: 2px solid #0A0A0A;
    border-radius: 6px; padding: 0 6px; font-size: 11px;
  }
  .filter-tab.active .count { background: #FFFFFF; }

  /* BUTTONS */
  .btn-primary {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    background: #2563EB; color: #FFFFFF;
    border: 3px solid #0A0A0A; border-radius: 10px;
    padding: 11px 18px;
    font-family: 'Space Grotesk', sans-serif; font-weight: 700; font-size: 13px;
    letter-spacing: 0.02em; text-transform: uppercase;
    box-shadow: 6px 6px 0 0 #0A0A0A; cursor: pointer; text-decoration: none;
  }

  /* STAGGER ENTRY */
  .stagger > * {
    opacity: 0; transform: translateY(12px);
    animation: rise 520ms cubic-bezier(.2,.8,.2,1) forwards;
  }
  .stagger > *:nth-child(1) { animation-delay: 60ms; }
  .stagger > *:nth-child(2) { animation-delay: 120ms; }
  .stagger > *:nth-child(3) { animation-delay: 180ms; }
  .stagger > *:nth-child(4) { animation-delay: 240ms; }
  .stagger > *:nth-child(5) { animation-delay: 300ms; }
  .stagger > *:nth-child(6) { animation-delay: 360ms; }
  @keyframes rise { to { opacity: 1; transform: translateY(0); } }

  .logout-btn {
    width: 32px; height: 32px;
    display: grid; place-items: center;
    border: 2px solid #0A0A0A; border-radius: 6px;
    background: #FFFFFF; cursor: pointer;
    transition: background-color 140ms ease, color 140ms ease;
  }
  .logout-btn:hover { background: #EF4444; color: #FFFFFF; }

  .nice-scroll::-webkit-scrollbar { width: 8px; }
  .nice-scroll::-webkit-scrollbar-thumb { background: #0A0A0A; border-radius: 8px; }

  @media (max-width: 1023px) {
    .sidebar { display: none !important; }
    .main-pad { padding-left: 16px !important; padding-right: 16px !important; }
  }
</style>
</head>

<body>

<div class="flex min-h-screen">

  {{-- SIDEBAR --}}
  <aside class="sidebar w-[260px] flex-shrink-0 bg-cream border-r-[3px] border-ink flex flex-col sticky top-0 h-screen">

    <div class="px-5 py-5 border-b-[3px] border-ink">
      <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group">
        <div class="w-11 h-11 grid place-items-center bg-electric-blue border-[3px] border-ink rounded-[10px] shadow-brutal-sm transition-transform group-hover:-rotate-6">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="white"><path d="M13 2 L4 14 H11 L11 22 L20 10 H13 Z"/></svg>
        </div>
        <div class="leading-none">
          <div class="font-display font-bold text-[19px] text-ink">DevTrack</div>
          <div class="font-mono text-[9px] text-ink/60 mt-0.5 tracking-widest">v1.0 · TECHNOPARK</div>
        </div>
      </a>
    </div>

    <div class="px-4 pt-4">
      <div class="font-mono text-[10px] font-semibold tracking-[0.18em] text-ink/50 uppercase mb-2 px-2">Workspace</div>
      <div class="w-full flex items-center gap-3 bg-white border-[3px] border-ink rounded-[10px] p-2.5 shadow-brutal-sm brutal-tap">
        <div class="w-8 h-8 grid place-items-center bg-pink-pop border-[2.5px] border-ink rounded-md text-white font-display font-bold text-[12px]">DT</div>
        <div class="flex-1 text-left">
          <div class="font-display font-bold text-[13px] leading-tight">DevTrack Crew</div>
          <div class="font-mono text-[10px] text-ink/60">pro plan</div>
        </div>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0A0A0A" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
      </div>
    </div>

    <nav class="px-4 pt-5 flex-1 overflow-y-auto nice-scroll">
      <div class="font-mono text-[10px] font-semibold tracking-[0.18em] text-ink/50 uppercase mb-2 px-2">Menu</div>
      <ul class="space-y-1.5">
        <li>
          <a href="{{ route('dashboard') }}" class="nav-item">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
            Dashboard
          </a>
        </li>
        <li>
          <a href="{{ route('projects.index') }}" class="nav-item">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
            Projects
            @if ($sidebarBadges['projects'] > 0)
              <span class="badge">{{ $sidebarBadges['projects'] }}</span>
            @endif
          </a>
        </li>
        <li>
          <a href="#" class="nav-item">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3 8-8"/><path d="M20 12v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h9"/></svg>
            My Tasks
            @if ($sidebarBadges['tasks'] > 0)
              <span class="badge">{{ $sidebarBadges['tasks'] }}</span>
            @endif
          </a>
        </li>
        <li>
          <a href="#" class="nav-item">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/></svg>
            Team
          </a>
        </li>
        <li>
          <a href="{{ route('projects.archives') }}" class="nav-item active">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8v13H3V8"/><path d="M1 3h22v5H1z"/><path d="M10 12h4"/></svg>
            Archives
            @if ($sidebarBadges['archives'] > 0)
              <span class="badge">{{ $sidebarBadges['archives'] }}</span>
            @endif
          </a>
        </li>
      </ul>

      {{-- Carte stockage archives --}}
      <div class="mt-7 p-4 bg-white border-[3px] border-ink rounded-[12px] shadow-brutal-sm">
        <div class="font-mono text-[10px] font-bold tracking-widest text-ink/60 uppercase mb-2">Archive storage</div>
        <div class="flex items-end justify-between mb-2">
          <div class="font-display font-bold text-[24px] leading-none">2.4 GB</div>
          <div class="font-mono text-[10px] text-ink/60">/ 10 GB</div>
        </div>
        <div class="h-2 bg-cream border-2 border-ink rounded-sm overflow-hidden">
          <div class="h-full w-[24%] bg-electric-blue border-r-2 border-ink"></div>
        </div>
        <div class="font-mono text-[10px] text-lime-accent font-bold mt-2">76% FREE</div>
      </div>
    </nav>

    <div class="p-4 border-t-[3px] border-ink">
      <div class="flex items-center gap-3 bg-white border-[3px] border-ink rounded-[10px] p-2.5 shadow-brutal-sm">
        <div class="avatar lg" style="background:#06B6D4;color:#0A0A0A;">{{ $userInitials }}</div>
        <div class="flex-1 min-w-0">
          <div class="font-display font-bold text-[13px] truncate">{{ $user->name }}</div>
          <div class="font-mono text-[10px] text-ink/60 truncate">team lead</div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="logout-btn" title="Log out">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
          </button>
        </form>
      </div>
    </div>
  </aside>


  {{-- MAIN --}}
  <main class="flex-1 min-w-0 relative">
    <div class="absolute inset-0 dot-grid opacity-50 pointer-events-none"></div>

    {{-- HEADER --}}
    <header class="sticky top-0 z-30 bg-cream/95 backdrop-blur border-b-[3px] border-ink">
      <div class="main-pad px-8 py-4 flex items-center gap-4">
        <nav class="flex items-center gap-2 font-mono text-[12px] font-semibold">
          <a href="{{ route('dashboard') }}" class="text-ink/60 hover:text-ink">Dashboard</a>
          <span class="text-ink/30">/</span>
          <span class="text-ink">Archives</span>
        </nav>

        <div class="ml-auto flex items-center gap-3">
          <div class="relative w-[300px] hidden md:block">
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="#0A0A0A" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <input type="text" placeholder="Search archived projects…" class="search-input" />
          </div>
          <div class="hidden md:flex items-center gap-2 bg-white border-[3px] border-ink rounded-[10px] py-1 px-2 shadow-brutal-sm">
            <div class="avatar" style="background:#06B6D4;color:#0A0A0A;">{{ $userInitials }}</div>
            <div class="leading-none mr-1">
              <div class="font-display font-bold text-[12px]">{{ Str::limit($user->name, 14, '') }}</div>
              <div class="font-mono text-[10px] text-ink/60">lead</div>
            </div>
          </div>
        </div>
      </div>
    </header>

    {{-- CONTENT --}}
    <div class="main-pad relative px-8 py-8">
      <div class="stagger">

        @if (session('success'))
          <div class="mb-6 px-4 py-3 border-[3px] border-ink rounded-[10px] bg-lime-accent/40 font-mono text-[12px] font-semibold shadow-brutal-sm">
            {{ session('success') }}
          </div>
        @endif

        {{-- HERO --}}
        <section class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6 mb-10">
          <div class="flex items-start gap-5">
            <div class="relative flex-shrink-0">
              <div class="absolute inset-0 bg-ink rounded-[14px]" style="transform: translate(8px, 8px)"></div>
              <div class="relative w-[88px] h-[88px] bg-amber-accent border-[3px] border-ink rounded-[14px] grid place-items-center">
                <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="#0A0A0A" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8v13H3V8"/><path d="M1 3h22v5H1z"/><path d="M10 12h4"/></svg>
              </div>
            </div>

            <div>
              <div class="font-mono text-[11px] font-semibold tracking-[0.18em] text-ink/60 uppercase mb-2">// archives</div>
              <h1 class="font-display font-bold text-[44px] sm:text-[52px] leading-[0.98] tracking-tight">The vault.</h1>
              <p class="text-ink/70 mt-3 text-[15px] max-w-[540px]">
                Projects you've completed, paused, or shelved. Restore them anytime, or send them to the void forever.
              </p>
            </div>
          </div>

          {{-- STATS --}}
          <div class="grid grid-cols-3 gap-3 lg:min-w-[420px]">
            <div class="bg-white border-[3px] border-ink rounded-[10px] p-3 shadow-brutal-sm">
              <div class="font-mono text-[10px] font-bold tracking-widest text-ink/60 uppercase">Total</div>
              <div class="font-display font-bold text-[26px] leading-none mt-1">{{ $totalCount }}</div>
            </div>
            <div class="bg-lime-accent border-[3px] border-ink rounded-[10px] p-3 shadow-brutal-sm">
              <div class="font-mono text-[10px] font-bold tracking-widest text-ink/80 uppercase">Shipped</div>
              <div class="font-display font-bold text-[26px] leading-none mt-1">{{ $shippedCount }}</div>
            </div>
            <div class="bg-amber-accent border-[3px] border-ink rounded-[10px] p-3 shadow-brutal-sm">
              <div class="font-mono text-[10px] font-bold tracking-widest text-ink/80 uppercase">Paused</div>
              <div class="font-display font-bold text-[26px] leading-none mt-1">{{ $pausedCount }}</div>
            </div>
          </div>
        </section>

        {{-- FILTER BAR --}}
        <section class="flex flex-col sm:flex-row sm:items-center gap-3 mb-6">
          <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('projects.archives') }}"
               class="filter-tab {{ $filter === 'all' ? 'active' : '' }}">
              All <span class="count">{{ $totalCount }}</span>
            </a>
            <a href="{{ route('projects.archives', ['filter' => 'shipped']) }}"
               class="filter-tab {{ $filter === 'shipped' ? 'active' : '' }}">
              Shipped <span class="count">{{ $shippedCount }}</span>
            </a>
            <a href="{{ route('projects.archives', ['filter' => 'paused']) }}"
               class="filter-tab {{ $filter === 'paused' ? 'active' : '' }}">
              Paused <span class="count">{{ $pausedCount }}</span>
            </a>
          </div>
          <div class="ml-auto font-mono text-[11px] text-ink/60">Sorted by <span class="text-ink font-bold">most recent</span></div>
        </section>

        {{-- GRID --}}
        @if ($visible->isNotEmpty())
          <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">

            @foreach ($visible as $row)
              @php
                  $project  = $row['project'];
                  $shipped  = $row['shipped'];
                  $archived = \Illuminate\Support\Carbon::parse($project->deleted_at);
              @endphp

              <article class="arch-card">
                <div class="archived-stamp {{ $shipped ? '' : 'paused' }}">{{ $shipped ? 'ARCHIVED' : 'PAUSED' }}</div>

                <div class="flex items-center gap-2 mb-3">
                  <span class="meta-chip dark">★ LEAD</span>
                  @if ($shipped)
                    <span class="meta-chip lime">SHIPPED</span>
                  @else
                    <span class="meta-chip amber">ON HOLD</span>
                  @endif
                </div>

                <h3 class="font-display font-bold text-[20px] leading-tight mb-2 pr-12">{{ $project->title }}</h3>
                <p class="text-[13px] text-ink/70 line-clamp-2 mb-5">
                  {{ Str::limit($project->description, 140) ?: 'No description.' }}
                </p>

                <div class="flex items-center justify-between mb-3 text-[11px] font-mono text-ink/60">
                  <span>📦 Archived <span class="text-ink font-bold">{{ $archived->translatedFormat('M d') }}</span></span>
                  <span>{{ $row['done'] }}/{{ $row['total'] }} ✓</span>
                </div>
                <div class="arch-prog {{ $shipped ? '' : 'partial' }} mb-5">
                  <div class="fill" style="width: {{ $row['pct'] }}%"></div>
                </div>

                <div class="flex items-center justify-between">
                  <div class="flex -space-x-2">
                    @foreach ($project->users->take(3) as $member)
                      <div class="avatar" style="background:#9CA3AF;color:#0A0A0A;" title="{{ $member->name }}">
                        {{ $initials($member->name) }}
                      </div>
                    @endforeach
                  </div>

                  <div class="flex gap-2">
                    @can('restore', $project)
                      <form method="POST" action="{{ route('projects.restore', $project) }}">
                        @csrf
                        <button type="submit" class="btn-mini success">
                          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                          Restore
                        </button>
                      </form>
                    @endcan

                    @can('forceDelete', $project)
                      <form method="POST" action="{{ route('projects.forceDelete', $project) }}"
                            onsubmit="return confirm('Supprimer définitivement « {{ $project->title }} » ? Cette action est irréversible.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-mini danger">
                          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
                          Delete
                        </button>
                      </form>
                    @endcan
                  </div>
                </div>
              </article>
            @endforeach

          </section>
        @else
          {{-- EMPTY STATE --}}
          <section class="bg-white border-[3px] border-dashed border-ink rounded-[14px] p-10 text-center">
            <div class="inline-block relative mb-5">
              <div class="absolute inset-0 bg-ink rounded-[14px]" style="transform: translate(6px, 6px)"></div>
              <div class="relative w-[100px] h-[80px] bg-cream border-[3px] border-ink rounded-[14px] flex flex-col items-center justify-center gap-1.5">
                <div class="flex gap-3">
                  <div class="w-3 h-4 bg-ink rounded-sm"></div>
                  <div class="w-3 h-4 bg-ink rounded-sm"></div>
                </div>
                <div class="w-5 h-1 bg-ink rounded-full mt-1"></div>
                <div class="absolute -top-2 left-1/2 -translate-x-1/2 flex">
                  <div class="w-8 h-2 bg-cream border-[3px] border-ink rounded-t-sm" style="transform: rotate(-12deg) translateX(-2px);"></div>
                  <div class="w-8 h-2 bg-cream border-[3px] border-ink rounded-t-sm" style="transform: rotate(12deg) translateX(2px);"></div>
                </div>
              </div>
            </div>
            <h3 class="font-display font-bold text-[22px] mb-2">
              @if ($totalCount > 0)
                Nothing in this filter.
              @else
                When the vault is empty…
              @endif
            </h3>
            <p class="text-[14px] text-ink/60 max-w-[420px] mx-auto mb-5">
              @if ($totalCount > 0)
                No archived project matches « {{ $filter }} ». Try another filter.
              @else
                Nothing in the vault yet. Archive a project from the dashboard when you're done with it — it'll land here.
              @endif
            </p>
            <a href="{{ route('dashboard') }}" class="btn-primary brutal-tap inline-flex">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
              Back to Dashboard
            </a>
          </section>
        @endif

        {{-- MICRO FOOTER --}}
        <div class="mt-8 flex flex-col md:flex-row items-center justify-between gap-3 font-mono text-[11px] text-ink/50">
          <div>&copy; {{ date('Y') }} DevTrack — Made in Agadir 🇲🇦</div>
          <div class="flex items-center gap-4">
            <span>Auto-purge: <span class="text-ink">365 days after archive</span></span>
          </div>
        </div>

      </div>
    </div>
  </main>
</div>

</body>
</html>