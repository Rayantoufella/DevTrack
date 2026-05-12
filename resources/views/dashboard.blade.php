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
    $firstName    = Str::before($user->name, ' ') ?: $user->name;

    // Palette de couleurs déterministe pour les avatars
    $avatarPalette = [
        ['bg' => '#06B6D4', 'fg' => '#0A0A0A'],
        ['bg' => '#EC4899', 'fg' => '#FFFFFF'],
        ['bg' => '#84CC16', 'fg' => '#0A0A0A'],
        ['bg' => '#F59E0B', 'fg' => '#0A0A0A'],
        ['bg' => '#2563EB', 'fg' => '#FFFFFF'],
        ['bg' => '#EF4444', 'fg' => '#FFFFFF'],
    ];
    $avatarColor = fn ($id) => $avatarPalette[((int) $id) % count($avatarPalette)];

    // Choix de la couleur de la barre de progression selon le %
    $progClass = function ($pct, $isUrgent) {
        if ($isUrgent) return 'red';
        if ($pct >= 90) return ''; // lime/striped par défaut
        if ($pct >= 60) return 'blue';
        if ($pct >= 30) return 'blue';
        return 'amber';
    };
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>DevTrack — Dashboard</title>

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
          'brutal-sm':    '4px 4px 0 0 #0A0A0A',
          'brutal':       '6px 6px 0 0 #0A0A0A',
          'brutal-lg':    '8px 8px 0 0 #0A0A0A',
          'brutal-blue':  '6px 6px 0 0 #2563EB',
          'brutal-pink':  '6px 6px 0 0 #EC4899',
          'brutal-lime':  '6px 6px 0 0 #84CC16',
          'brutal-red':   '6px 6px 0 0 #EF4444',
          'brutal-press': '2px 2px 0 0 #0A0A0A',
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
    transition: transform 180ms cubic-bezier(.2,.8,.2,1),
                box-shadow 180ms cubic-bezier(.2,.8,.2,1);
    will-change: transform, box-shadow;
  }
  .brutal-tap:hover  { transform: translate(-2px,-2px); box-shadow: 8px 8px 0 0 #0A0A0A; }
  .brutal-tap:active { transform: translate(2px,2px); box-shadow: 2px 2px 0 0 #0A0A0A; transition-duration: 80ms; }
  .brutal-tap-blue:hover { box-shadow: 8px 8px 0 0 #2563EB; }

  .dot-grid {
    background-image: radial-gradient(circle, rgba(10,10,10,.10) 1.2px, transparent 1.2px);
    background-size: 22px 22px;
  }

  .nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 11px 14px;
    border-radius: 10px;
    border: 3px solid transparent;
    font-family: 'Space Grotesk', sans-serif;
    font-weight: 600;
    font-size: 14px;
    color: #0A0A0A;
    cursor: pointer;
    transition: all 160ms ease;
    position: relative;
    text-decoration: none;
  }
  .nav-item:hover {
    background: #FFFFFF;
    border-color: #0A0A0A;
    box-shadow: 4px 4px 0 0 #0A0A0A;
    transform: translate(-1px, -1px);
  }
  .nav-item.active {
    background: #2563EB;
    color: #FFFFFF;
    border-color: #0A0A0A;
    box-shadow: 4px 4px 0 0 #0A0A0A;
  }
  .nav-item .badge {
    margin-left: auto;
    font-family: 'JetBrains Mono', monospace;
    font-size: 11px;
    font-weight: 700;
    background: #FFFBEB;
    color: #0A0A0A;
    border: 2px solid #0A0A0A;
    border-radius: 6px;
    padding: 1px 6px;
  }
  .nav-item.active .badge { background: #FFFFFF; }

  .stat-card {
    background: #FFFFFF;
    border: 3px solid #0A0A0A;
    border-radius: 12px;
    padding: 18px;
    box-shadow: 6px 6px 0 0 #0A0A0A;
    position: relative;
    overflow: hidden;
  }

  .project-card {
    background: #FFFFFF;
    border: 3px solid #0A0A0A;
    border-radius: 12px;
    padding: 22px;
    box-shadow: 6px 6px 0 0 #0A0A0A;
    position: relative;
    overflow: hidden;
    transition: transform 200ms cubic-bezier(.2,.8,.2,1), box-shadow 200ms cubic-bezier(.2,.8,.2,1);
  }
  .project-card:hover {
    transform: translate(-3px, -3px);
    box-shadow: 9px 9px 0 0 #2563EB;
  }
  .project-card.cream    { background: #FFFBEB; }
  .project-card.archived { background: #F5F5F5; }
  .project-card.archived .desc,
  .project-card.archived .title { opacity: 0.55; }

  .archived-stamp {
    position: absolute;
    top: 22px;
    right: -42px;
    transform: rotate(34deg);
    background: #EF4444;
    color: #FFFFFF;
    border: 2.5px solid #0A0A0A;
    box-shadow: 3px 3px 0 0 #0A0A0A;
    font-family: 'JetBrains Mono', monospace;
    font-weight: 700;
    font-size: 11px;
    letter-spacing: 0.16em;
    padding: 4px 44px;
  }

  .prog-track {
    height: 12px;
    background: #FFFBEB;
    border: 2.5px solid #0A0A0A;
    border-radius: 6px;
    overflow: hidden;
    position: relative;
  }
  .prog-fill {
    height: 100%;
    background: #84CC16;
    border-right: 2.5px solid #0A0A0A;
    background-image: repeating-linear-gradient(
      45deg,
      transparent, transparent 6px,
      rgba(10,10,10,0.18) 6px, rgba(10,10,10,0.18) 7px
    );
  }
  .prog-fill.amber { background-color: #F59E0B; }
  .prog-fill.blue  { background-color: #2563EB; }
  .prog-fill.red   { background-color: #EF4444; }

  @keyframes urgent-pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(239,68,68,.6); }
    50%      { box-shadow: 0 0 0 6px rgba(239,68,68,0); }
  }
  .urgent-dot {
    width: 9px; height: 9px;
    background: #EF4444;
    border: 2px solid #0A0A0A;
    border-radius: 9999px;
    animation: urgent-pulse 1.6s ease-in-out infinite;
  }

  .filter-tab {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #FFFFFF;
    border: 3px solid #0A0A0A;
    border-radius: 10px;
    padding: 10px 16px;
    font-family: 'Space Grotesk', sans-serif;
    font-weight: 700;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    box-shadow: 4px 4px 0 0 #0A0A0A;
    cursor: pointer;
    transition: all 180ms cubic-bezier(.2,.8,.2,1);
  }
  .filter-tab:hover { transform: translate(-2px,-2px); box-shadow: 6px 6px 0 0 #0A0A0A; }
  .filter-tab.active {
    background: #2563EB;
    color: #FFFFFF;
    border-color: #0A0A0A;
  }
  .filter-tab .count {
    font-family: 'JetBrains Mono', monospace;
    background: #FFFBEB;
    color: #0A0A0A;
    border: 2px solid #0A0A0A;
    border-radius: 6px;
    padding: 0px 6px;
    font-size: 11px;
  }
  .filter-tab.active .count { background: #FFFFFF; }

  .search-input {
    width: 100%;
    background: #FFFFFF;
    border: 3px solid #0A0A0A;
    border-radius: 10px;
    padding: 10px 14px 10px 42px;
    font-family: 'Inter', sans-serif;
    font-weight: 500;
    font-size: 14px;
    transition: box-shadow 160ms ease, transform 160ms ease, border-color 160ms ease;
  }
  .search-input:focus {
    outline: none;
    border-color: #2563EB;
    box-shadow: 4px 4px 0 0 #2563EB;
    transform: translate(-2px, -2px);
  }

  .stagger > * {
    opacity: 0;
    transform: translateY(12px);
    animation: rise 520ms cubic-bezier(.2,.8,.2,1) forwards;
  }
  .stagger > *:nth-child(1) { animation-delay: 50ms; }
  .stagger > *:nth-child(2) { animation-delay: 100ms; }
  .stagger > *:nth-child(3) { animation-delay: 150ms; }
  .stagger > *:nth-child(4) { animation-delay: 200ms; }
  .stagger > *:nth-child(5) { animation-delay: 250ms; }
  .stagger > *:nth-child(6) { animation-delay: 300ms; }
  .stagger > *:nth-child(7) { animation-delay: 350ms; }
  .stagger > *:nth-child(8) { animation-delay: 400ms; }
  @keyframes rise { to { opacity: 1; transform: translateY(0); } }

  .avatar {
    width: 32px; height: 32px;
    border-radius: 9999px;
    border: 2.5px solid #0A0A0A;
    display: grid;
    place-items: center;
    font-family: 'Space Grotesk', sans-serif;
    font-weight: 700;
    font-size: 11px;
  }
  .avatar.lg { width: 40px; height: 40px; font-size: 13px; }

  .notif-dot {
    position: absolute;
    top: -3px; right: -3px;
    width: 14px; height: 14px;
    border-radius: 9999px;
    background: #EF4444;
    border: 2.5px solid #FFFBEB;
  }

  .nice-scroll::-webkit-scrollbar { width: 8px; }
  .nice-scroll::-webkit-scrollbar-thumb { background: #0A0A0A; border-radius: 8px; }

  @media (max-width: 1023px) {
    .sidebar { display: none !important; }
    .main-pad { padding-left: 16px !important; padding-right: 16px !important; }
  }

  .icon-btn {
    width: 44px; height: 44px;
    background: #FFFFFF;
    border: 3px solid #0A0A0A;
    border-radius: 10px;
    display: grid; place-items: center;
    box-shadow: 4px 4px 0 0 #0A0A0A;
    cursor: pointer;
    position: relative;
    transition: all 180ms cubic-bezier(.2,.8,.2,1);
  }
  .icon-btn:hover { transform: translate(-2px,-2px); box-shadow: 6px 6px 0 0 #0A0A0A; }

  .btn-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: #2563EB;
    color: #FFFFFF;
    border: 3px solid #0A0A0A;
    border-radius: 10px;
    padding: 12px 20px;
    font-family: 'Space Grotesk', sans-serif;
    font-weight: 700;
    font-size: 14px;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    box-shadow: 6px 6px 0 0 #0A0A0A;
    cursor: pointer;
    text-decoration: none;
  }
  .btn-secondary {
    background: #FFFFFF;
    color: #0A0A0A;
  }

  .global-prog {
    height: 18px;
    background: #FFFFFF;
    border: 3px solid #0A0A0A;
    border-radius: 8px;
    overflow: hidden;
    position: relative;
  }
  .global-prog .fill {
    height: 100%;
    background: linear-gradient(90deg, #2563EB 0%, #06B6D4 100%);
    border-right: 3px solid #0A0A0A;
    position: relative;
  }
  .global-prog .fill::after {
    content: '';
    position: absolute;
    inset: 0;
    background-image: repeating-linear-gradient(
      45deg,
      transparent, transparent 8px,
      rgba(10,10,10,0.18) 8px, rgba(10,10,10,0.18) 9px
    );
  }

  .meta-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #FFFBEB;
    color: #0A0A0A;
    border: 2px solid #0A0A0A;
    border-radius: 9999px;
    padding: 3px 10px;
    font-family: 'JetBrains Mono', monospace;
    font-weight: 600;
    font-size: 11px;
    box-shadow: 2px 2px 0 0 #0A0A0A;
  }
  .meta-chip.urgent { background: #EF4444; color: #FFFFFF; }
  .meta-chip.lime   { background: #84CC16; }
  .meta-chip.amber  { background: #F59E0B; }
  .meta-chip.cyan   { background: #06B6D4; color: #FFFFFF; }

  .lead-stamp {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #0A0A0A;
    color: #FFFFFF;
    border-radius: 6px;
    padding: 2px 8px;
    font-family: 'JetBrains Mono', monospace;
    font-weight: 700;
    font-size: 10px;
    letter-spacing: 0.12em;
  }
  .lead-stamp.dev {
    background: #FFFFFF;
    color: #0A0A0A;
    border: 2px solid #0A0A0A;
  }

  .logout-btn {
    width: 32px; height: 32px;
    display: grid; place-items: center;
    border: 2px solid #0A0A0A;
    border-radius: 6px;
    background: #FFFFFF;
    cursor: pointer;
    transition: background-color 140ms ease, color 140ms ease;
  }
  .logout-btn:hover { background: #EF4444; color: #FFFFFF; }
</style>
</head>

<body>

<div class="flex min-h-screen">

  {{-- SIDEBAR --}}
  <aside class="sidebar w-[260px] flex-shrink-0 bg-cream border-r-[3px] border-ink flex flex-col sticky top-0 h-screen">

    <div class="px-5 py-5 border-b-[3px] border-ink">
      <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group">
        <div class="w-11 h-11 grid place-items-center bg-electric-blue border-[3px] border-ink rounded-[10px] shadow-brutal-sm transition-transform group-hover:-rotate-6">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="white" stroke="white" stroke-width="3" stroke-linejoin="round">
            <path d="M13 2 L4 14 H11 L11 22 L20 10 H13 Z"/>
          </svg>
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
          <div class="font-mono text-[10px] text-ink/60">{{ $stats['crew'] }} {{ $stats['crew'] === 1 ? 'builder' : 'builders' }}</div>
        </div>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0A0A0A" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
      </div>
    </div>

    <nav class="px-4 pt-5 flex-1 overflow-y-auto nice-scroll">
      <div class="font-mono text-[10px] font-semibold tracking-[0.18em] text-ink/50 uppercase mb-2 px-2">Menu</div>
      <ul class="space-y-1.5">
        <li>
          <a href="{{ route('dashboard') }}" class="nav-item active">
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
          <a href="{{ route('projects.archives') }}" class="nav-item">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8v13H3V8"/><path d="M1 3h22v5H1z"/><path d="M10 12h4"/></svg>
            Archives
            @if ($sidebarBadges['archives'] > 0)
              <span class="badge">{{ $sidebarBadges['archives'] }}</span>
            @endif
          </a>
        </li>
      </ul>

      <div class="font-mono text-[10px] font-semibold tracking-[0.18em] text-ink/50 uppercase mt-7 mb-2 px-2">Account</div>
      <ul class="space-y-1.5">
        <li>
          <a href="{{ route('profile.edit') }}" class="nav-item">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            Settings
          </a>
        </li>
        <li>
          <a href="#" class="nav-item">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>
            Help &amp; Docs
          </a>
        </li>
      </ul>

      <div class="mt-6 p-4 bg-amber-accent border-[3px] border-ink rounded-[12px] shadow-brutal-sm relative overflow-hidden">
        <div class="absolute -top-4 -right-4 w-14 h-14 bg-pink-pop border-[3px] border-ink rounded-full"></div>
        <div class="relative">
          <div class="font-mono text-[10px] font-bold tracking-widest mb-1">⚡ PRO TIP</div>
          <div class="font-display font-bold text-[14px] leading-tight mb-2">Press <span class="font-mono bg-white border-2 border-ink rounded px-1.5 py-0.5 text-[11px]">⌘K</span> to search anything.</div>
        </div>
      </div>
    </nav>

    <div class="p-4 border-t-[3px] border-ink">
      <div class="flex items-center gap-3 bg-white border-[3px] border-ink rounded-[10px] p-2.5 shadow-brutal-sm">
        <div class="avatar lg" style="background:#06B6D4;color:#0A0A0A;">{{ $userInitials }}</div>
        <div class="flex-1 min-w-0">
          <div class="font-display font-bold text-[13px] truncate">{{ $user->name }}</div>
          <div class="font-mono text-[10px] text-ink/60 truncate">{{ $isLead ? 'team lead' : 'developer' }}</div>
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
        <div class="hidden md:flex items-center gap-2 font-mono text-[11px] font-semibold tracking-widest uppercase text-ink/60">
          <span>// dashboard</span>
          <span class="text-ink/30">/</span>
          <span class="text-ink">overview</span>
        </div>

        <div class="relative flex-1 max-w-[460px] ml-auto">
          <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="#0A0A0A" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
          <input type="text" placeholder="Search projects, tasks, members…" class="search-input" />
          <span class="absolute right-3 top-1/2 -translate-y-1/2 font-mono text-[10px] font-bold bg-cream border-2 border-ink rounded px-1.5 py-0.5 text-ink/60">⌘K</span>
        </div>

        <button class="icon-btn" title="Notifications">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0A0A0A" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
          @if ($stats['urgent'] > 0)
            <span class="notif-dot"></span>
          @endif
        </button>

        <button class="icon-btn" title="Inbox">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0A0A0A" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11Z"/></svg>
        </button>

        <div class="hidden md:flex items-center gap-2 bg-white border-[3px] border-ink rounded-[10px] py-1 px-2 shadow-brutal-sm">
          <div class="avatar" style="background:#06B6D4;color:#0A0A0A;">{{ $userInitials }}</div>
          <div class="leading-none mr-1">
            <div class="font-display font-bold text-[12px]">{{ Str::limit($user->name, 14, '') }}</div>
            <div class="font-mono text-[10px] text-ink/60">{{ $isLead ? 'lead' : 'dev' }}</div>
          </div>
        </div>
      </div>
    </header>

    {{-- CONTENT --}}
    <div class="main-pad relative px-8 py-8 stagger">

      @if (session('success'))
        <div class="mb-6 px-4 py-3 border-[3px] border-ink rounded-[10px] bg-lime-accent/40 font-mono text-[12px] font-semibold shadow-brutal-sm">
          {{ session('success') }}
        </div>
      @endif

      {{-- HERO --}}
      <section class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4 mb-8">
        <div>
          <div class="flex items-center gap-2 mb-3">
            <span class="font-mono text-[11px] font-semibold tracking-[0.18em] text-electric-blue uppercase">// today · {{ now()->translatedFormat('l, F j') }}</span>
          </div>
          <h1 class="font-display font-bold text-[44px] sm:text-[56px] leading-[0.98] text-ink tracking-tight">
            Hey, <span class="relative inline-block">{{ $firstName }}
              <svg class="absolute -bottom-2 left-0 w-full" viewBox="0 0 200 12" preserveAspectRatio="none" height="10">
                <path d="M2 8 Q 60 2, 120 7 T 198 6" stroke="#84CC16" stroke-width="6" fill="none" stroke-linecap="round"/>
              </svg>
            </span> 👋
          </h1>
          <p class="text-ink/70 mt-3 text-[16px] max-w-[560px]">
            @if ($stats['done'] > 0)
              Your crew shipped <span class="font-bold text-ink">{{ $stats['done'] }} {{ $stats['done'] === 1 ? 'task' : 'tasks' }}</span> this week
              @if ($weekDelta !== 0)
                — {{ $weekDelta > 0 ? 'beating' : 'behind' }} last week by
                <span class="font-bold {{ $weekDelta > 0 ? 'text-lime-accent' : 'text-red-accent' }}">{{ $weekDelta > 0 ? '+' : '' }}{{ $weekDelta }}%</span>.
              @else
                — keep the streak going.
              @endif
            @else
              No tasks shipped yet this week.
            @endif
            @if ($stats['urgent'] > 0)
              <span class="font-bold text-red-accent">{{ $stats['urgent'] }} urgent</span> on the board.
            @endif
            Let's keep moving.
          </p>
        </div>

        <div class="flex items-center gap-3">
          <a href="{{ route('projects.archives') }}" class="btn-primary btn-secondary brutal-tap">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8v13H3V8"/><path d="M1 3h22v5H1z"/><path d="M10 12h4"/></svg>
            Archives
          </a>
          <a href="{{ route('projects.create') }}" class="btn-primary brutal-tap">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
            New Project
          </a>
        </div>
      </section>

      {{-- STATS --}}
      <section class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-8">

        <div class="stat-card">
          <div class="flex items-center justify-between">
            <div class="font-mono text-[10px] font-bold tracking-widest text-ink/60 uppercase">Total projects</div>
            <span class="w-7 h-7 grid place-items-center bg-electric-blue border-2 border-ink rounded-md">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
            </span>
          </div>
          <div class="font-display font-bold text-[44px] leading-none mt-3 text-ink">{{ $stats['projects'] }}</div>
          <div class="flex items-center gap-2 mt-3">
            <span class="meta-chip {{ $filters['lead'] > 0 ? 'lime' : '' }}">★ {{ $filters['lead'] }} lead</span>
            <span class="font-mono text-[11px] text-ink/60">· {{ $filters['dev'] }} dev</span>
          </div>
        </div>

        <div class="stat-card">
          <div class="flex items-center justify-between">
            <div class="font-mono text-[10px] font-bold tracking-widest text-ink/60 uppercase">Active tasks</div>
            <span class="w-7 h-7 grid place-items-center bg-amber-accent border-2 border-ink rounded-md">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0A0A0A" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v10l4 4"/><circle cx="12" cy="12" r="10"/></svg>
            </span>
          </div>
          <div class="font-display font-bold text-[44px] leading-none mt-3 text-ink">{{ $stats['active'] }}</div>
          <div class="flex items-center gap-2 mt-3">
            @if ($stats['urgent'] > 0)<div class="urgent-dot"></div>@endif
            <span class="font-mono text-[11px] text-ink/60">
              @if ($stats['urgent'] > 0)
                <span class="text-red-accent font-bold">{{ $stats['urgent'] }} urgent</span> ·
              @endif
              {{ $stats['due_week'] }} due this week
            </span>
          </div>
        </div>

        <div class="stat-card">
          <div class="flex items-center justify-between">
            <div class="font-mono text-[10px] font-bold tracking-widest text-ink/60 uppercase">Done this week</div>
            <span class="w-7 h-7 grid place-items-center bg-lime-accent border-2 border-ink rounded-md">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0A0A0A" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            </span>
          </div>
          <div class="font-display font-bold text-[44px] leading-none mt-3 text-ink">{{ $stats['done'] }}</div>
          <div class="flex items-end gap-1 mt-3 h-6">
            <div class="flex-1 bg-ink/15 border-2 border-ink rounded-sm" style="height:30%"></div>
            <div class="flex-1 bg-ink/25 border-2 border-ink rounded-sm" style="height:55%"></div>
            <div class="flex-1 bg-ink/40 border-2 border-ink rounded-sm" style="height:40%"></div>
            <div class="flex-1 bg-ink/60 border-2 border-ink rounded-sm" style="height:70%"></div>
            <div class="flex-1 bg-electric-blue border-2 border-ink rounded-sm" style="height:90%"></div>
            <div class="flex-1 bg-lime-accent border-2 border-ink rounded-sm" style="height:100%"></div>
          </div>
        </div>

        <div class="stat-card">
          <div class="flex items-center justify-between">
            <div class="font-mono text-[10px] font-bold tracking-widest text-ink/60 uppercase">Crew</div>
            <span class="w-7 h-7 grid place-items-center bg-pink-pop border-2 border-ink rounded-md">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </span>
          </div>
          <div class="flex items-center gap-3 mt-3">
            <div class="font-display font-bold text-[44px] leading-none text-ink">{{ $stats['crew'] }}</div>
            <div class="font-display font-bold text-[20px] leading-none text-ink/40">/{{ $stats['crew'] }}</div>
          </div>
          <div class="flex -space-x-2 mt-3">
            @php
              $seenUsers = collect();
              foreach ($projects as $p) {
                  foreach ($p->users as $u) {
                      if (! $seenUsers->contains('id', $u->id)) $seenUsers->push($u);
                  }
              }
              $crewPreview = $seenUsers->take(4);
              $extra = max(0, $seenUsers->count() - 4);
            @endphp
            @foreach ($crewPreview as $u)
              @php $c = $avatarColor($u->id); @endphp
              <div class="avatar" style="background:{{ $c['bg'] }};color:{{ $c['fg'] }};" title="{{ $u->name }}">{{ $initials($u->name) }}</div>
            @endforeach
            @if ($extra > 0)
              <div class="avatar" style="background:#FFFFFF;color:#0A0A0A;">+{{ $extra }}</div>
            @endif
          </div>
        </div>
      </section>

      {{-- SECTION HEADER + FILTERS --}}
      <section class="flex flex-col lg:flex-row lg:items-end gap-4 mb-6">
        <div class="flex-1">
          <div class="font-mono text-[11px] font-semibold tracking-[0.18em] text-ink/60 uppercase mb-1">// Section</div>
          <h2 class="font-display font-bold text-[28px] tracking-tight">Your projects</h2>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <button class="filter-tab active">All <span class="count">{{ $filters['all'] }}</span></button>
          <button class="filter-tab">As Lead <span class="count">{{ $filters['lead'] }}</span></button>
          <button class="filter-tab">As Developer <span class="count">{{ $filters['dev'] }}</span></button>
        </div>
      </section>

      {{-- PROJECTS GRID --}}
      <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 stagger">

        @forelse ($projects as $project)
          @php
              $role     = $project->pivot->role ?? 'developer';
              $total    = (int) $project->tasks_count;
              $done     = (int) $project->completed_tasks_count;
              $urgent   = (int) ($project->urgent_tasks_count ?? 0);
              $pct      = $total > 0 ? (int) round($done / $total * 100) : 0;
              $deadline = \Illuminate\Support\Carbon::parse($project->deadline);
              $isUrgent = $urgent > 0 || ($deadline->isFuture() && $deadline->diffInHours(now(), false) >= -48 && $deadline->diffInHours(now()) <= 48 && $pct < 100);
              $isOverdue = $deadline->isPast() && $pct < 100;
              $cardClass = $pct >= 90 ? 'cream' : '';
              $progClassName = $progClass($pct, $isUrgent || $isOverdue);
              $hoursLeft = $deadline->diffInHours(now(), false);
          @endphp

          <article class="project-card {{ $cardClass }}" @if ($isUrgent) style="border-left: 8px solid #EF4444;" @endif>
            <div class="flex items-start justify-between mb-3">
              <div class="flex items-center gap-2 flex-wrap">
                @if ($role === 'lead')
                  <span class="lead-stamp">★ LEAD</span>
                @else
                  <span class="lead-stamp dev">DEV</span>
                @endif

                @if ($isUrgent)
                  <span class="meta-chip urgent">
                    <span class="urgent-dot" style="background:#FFFFFF;border-color:#0A0A0A;"></span>
                    URGENT
                  </span>
                @elseif ($pct >= 90)
                  <span class="meta-chip lime">SHIP-READY</span>
                @elseif ($pct === 0)
                  <span class="meta-chip">FRESH</span>
                @else
                  <span class="meta-chip cyan">IN PROGRESS</span>
                @endif
              </div>

              @can('update', $project)
                <a href="{{ route('projects.edit', $project) }}" class="w-7 h-7 grid place-items-center hover:bg-ink/10 rounded-md" title="Edit">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0A0A0A" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                </a>
              @endcan
            </div>

            <a href="{{ route('projects.show', $project) }}" class="block">
              <h3 class="title font-display font-bold text-[22px] leading-tight text-ink mb-2 hover:underline decoration-2 underline-offset-4">
                {{ $project->title }}
              </h3>
            </a>
            <p class="desc text-[14px] text-ink/70 line-clamp-2 mb-5">
              {{ Str::limit($project->description, 140) }}
            </p>

            <div class="flex items-center justify-between gap-2 mb-3 flex-wrap">
              <div class="meta-chip">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                {{ $done }}/{{ $total }} ✓
              </div>

              @if ($isOverdue)
                <div class="meta-chip urgent">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                  Overdue
                </div>
              @elseif ($isUrgent)
                <div class="meta-chip urgent">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                  In {{ max(0, (int) abs($hoursLeft)) }}h
                </div>
              @else
                <div class="meta-chip {{ $deadline->diffInDays(now(), false) >= -7 ? 'amber' : '' }}">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                  {{ $deadline->translatedFormat('M j') }}
                </div>
              @endif

              <div class="flex -space-x-2">
                @php
                    $members = $project->users->take(3);
                    $more    = max(0, $project->users->count() - 3);
                @endphp
                @foreach ($members as $m)
                  @php $c = $avatarColor($m->id); @endphp
                  <div class="avatar" style="background:{{ $c['bg'] }};color:{{ $c['fg'] }};" title="{{ $m->name }}">{{ $initials($m->name) }}</div>
                @endforeach
                @if ($more > 0)
                  <div class="avatar" style="background:#FFFFFF;color:#0A0A0A;">+{{ $more }}</div>
                @endif
              </div>
            </div>

            <div>
              <div class="flex items-center justify-between mb-1.5">
                <div class="font-mono text-[10px] font-bold tracking-widest text-ink/60 uppercase">Progress</div>
                <div class="font-mono text-[12px] font-bold {{ $isUrgent || $isOverdue ? 'text-red-accent' : ($pct >= 90 ? 'text-lime-accent' : 'text-ink') }}">{{ $pct }}%</div>
              </div>
              <div class="prog-track"><div class="prog-fill {{ $progClassName }}" style="width:{{ $pct }}%"></div></div>
            </div>
          </article>
        @empty
          <article class="project-card md:col-span-2 xl:col-span-3" style="text-align:center; padding: 48px 24px;">
            <div class="font-display font-bold text-[20px] mb-2">No projects yet</div>
            <p class="text-[14px] text-ink/60 mb-5">Spin up your first project to start tracking tasks with the crew.</p>
            <a href="{{ route('projects.create') }}" class="btn-primary brutal-tap" style="display:inline-flex;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
              Create your first project
            </a>
          </article>
        @endforelse

        {{-- Tile « New project » : visible seulement si l'utilisateur peut en créer --}}
        @can('create', App\Models\Project::class)
          <a href="{{ route('projects.create') }}" class="project-card brutal-tap-blue" style="background: repeating-linear-gradient(45deg, #FFFBEB, #FFFBEB 10px, #FFFFFF 10px, #FFFFFF 20px); display:flex; flex-direction:column; align-items:center; justify-content:center; min-height:280px; cursor:pointer; text-decoration:none;">
            <div class="w-16 h-16 grid place-items-center bg-electric-blue border-[3px] border-ink rounded-[12px] shadow-brutal-sm mb-4">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
            </div>
            <div class="font-display font-bold text-[18px] text-ink mb-1">Start a new project</div>
            <p class="text-[13px] text-ink/60 text-center max-w-[260px]">Spin up a fresh space, invite the crew, assign first tasks.</p>
            <div class="font-mono text-[10px] font-bold tracking-widest text-ink/40 mt-3">⌘ + N</div>
          </a>
        @endcan

      </section>

      {{-- FOOTER GLOBAL PROGRESS --}}
      <section class="mt-10 bg-white border-[3px] border-ink rounded-[12px] p-6 shadow-brutal">
        <div class="flex flex-col lg:flex-row lg:items-center gap-5">
          <div class="flex-shrink-0">
            <div class="font-mono text-[10px] font-bold tracking-widest text-ink/60 uppercase mb-1">// global progress</div>
            <div class="font-display font-bold text-[24px] leading-none">Crew velocity</div>
            <p class="text-[13px] text-ink/60 mt-1.5">Tasks completed across all your projects</p>
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between mb-2">
              <div class="font-mono text-[12px] font-bold text-ink">{{ $velocity['done'] }} / {{ $velocity['total'] }} tasks</div>
              <div class="font-display font-bold text-[18px] text-electric-blue">{{ $velocity['pct'] }}%</div>
            </div>
            <div class="global-prog">
              <div class="fill" style="width: {{ $velocity['pct'] }}%"></div>
            </div>
            <div class="flex items-center justify-between mt-3 text-[11px] font-mono text-ink/60">
              <span>● {{ $velocity['pct'] }}% done</span>
              <span>● {{ $velocity['pct_prog'] }}% in progress</span>
              <span>● {{ $velocity['pct_todo'] }}% todo</span>
            </div>
          </div>
        </div>
      </section>

      <div class="mt-8 flex flex-col md:flex-row items-center justify-between gap-3 font-mono text-[11px] text-ink/50">
        <div>&copy; {{ date('Y') }} DevTrack — Made in Agadir 🇲🇦</div>
        <div class="flex items-center gap-4">
          <span>API: operational</span>
          <span>Build: <span class="text-ink">{{ substr(md5(config('app.key') ?? 'devtrack'), 0, 6) }}</span></span>
          <span>Last sync: {{ now()->diffForHumans(null, true) }} ago</span>
        </div>
      </div>

    </div>
  </main>
</div>

</body>
</html>
