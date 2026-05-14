@php
    use Illuminate\Support\Str;
    use Illuminate\Support\Carbon;

    $user = auth()->user();

    $initials = fn ($name) => collect(explode(' ', trim((string) $name)))
        ->filter()->take(2)
        ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))
        ->implode('');

    $userInitials = $initials($user->name) ?: 'U';

    $palette = [
        ['bg' => '#06B6D4', 'fg' => '#0A0A0A'],
        ['bg' => '#EC4899', 'fg' => '#FFFFFF'],
        ['bg' => '#84CC16', 'fg' => '#0A0A0A'],
        ['bg' => '#F59E0B', 'fg' => '#0A0A0A'],
        ['bg' => '#2563EB', 'fg' => '#FFFFFF'],
        ['bg' => '#EF4444', 'fg' => '#FFFFFF'],
    ];
    $avatarColor = fn ($id) => $palette[((int) $id) % count($palette)];

    // Couleur de la barre de progression selon le %.
    $progClass = function ($pct, $isUrgent) {
        if ($isUrgent) return 'red';
        if ($pct >= 90) return '';
        if ($pct >= 30) return 'blue';
        return 'amber';
    };

    // Filtre courant (aucun JS) : ?role=lead | dev | all
    $roleFilter = in_array(request('role'), ['lead', 'dev']) ? request('role') : 'all';

    $filters = [
        'all'  => $projects->count(),
        'lead' => $projects->where('pivot.role', 'lead')->count(),
        'dev'  => $projects->where('pivot.role', 'developer')->count(),
    ];

    $visible = $projects->filter(function ($p) use ($roleFilter) {
        $role = $p->pivot->role ?? 'developer';
        if ($roleFilter === 'lead') return $role === 'lead';
        if ($roleFilter === 'dev')  return $role === 'developer';
        return true;
    });

    $isLead = $filters['lead'] > 0;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>DevTrack — Projects</title>

<script src="https://cdn.tailwindcss.com"></script>

<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet" />

<script>
  tailwind.config = { theme: { extend: {
    colors: { 'electric-blue':'#2563EB','electric-cyan':'#06B6D4','neon-blue':'#3B82F6','deep-blue':'#1E3A8A',
      'ink':'#0A0A0A','cream':'#FFFBEB','lime-accent':'#84CC16','amber-accent':'#F59E0B',
      'red-accent':'#EF4444','pink-pop':'#EC4899','gray-soft':'#F5F5F5','blue-mist':'#EFF6FF' },
    fontFamily: { display:['"Space Grotesk"','sans-serif'], body:['Inter','sans-serif'], mono:['"JetBrains Mono"','monospace'] },
    boxShadow: { 'brutal-sm':'4px 4px 0 0 #0A0A0A','brutal':'6px 6px 0 0 #0A0A0A','brutal-lg':'8px 8px 0 0 #0A0A0A' }
  } } }
</script>

<style>
  html, body { background:#FFFBEB; color:#0A0A0A; }
  body { font-family:'Inter',sans-serif; -webkit-font-smoothing:antialiased; }
  .font-display { font-family:'Space Grotesk',sans-serif; letter-spacing:-0.02em; }
  .font-mono    { font-family:'JetBrains Mono',monospace; }

  .brutal-tap { transition: transform 180ms cubic-bezier(.2,.8,.2,1), box-shadow 180ms cubic-bezier(.2,.8,.2,1); }
  .brutal-tap:hover  { transform: translate(-2px,-2px); box-shadow: 8px 8px 0 0 #0A0A0A; }
  .brutal-tap:active { transform: translate(2px,2px); box-shadow: 2px 2px 0 0 #0A0A0A; transition-duration:80ms; }
  .brutal-tap-blue:hover { box-shadow: 8px 8px 0 0 #2563EB; }

  .dot-grid { background-image: radial-gradient(circle, rgba(10,10,10,.10) 1.2px, transparent 1.2px); background-size: 22px 22px; }

  .nav-item { display:flex; align-items:center; gap:12px; padding:11px 14px; border-radius:10px; border:3px solid transparent;
    font-family:'Space Grotesk',sans-serif; font-weight:600; font-size:14px; color:#0A0A0A; cursor:pointer;
    transition:all 160ms ease; text-decoration:none; }
  .nav-item:hover { background:#FFFFFF; border-color:#0A0A0A; box-shadow:4px 4px 0 0 #0A0A0A; transform:translate(-1px,-1px); }
  .nav-item.active { background:#2563EB; color:#FFFFFF; border-color:#0A0A0A; box-shadow:4px 4px 0 0 #0A0A0A; }
  .nav-item .badge { margin-left:auto; font-family:'JetBrains Mono',monospace; font-size:11px; font-weight:700;
    background:#FFFBEB; color:#0A0A0A; border:2px solid #0A0A0A; border-radius:6px; padding:1px 6px; }
  .nav-item.active .badge { background:#FFFFFF; }

  .avatar { width:32px; height:32px; border-radius:9999px; border:2.5px solid #0A0A0A;
    display:grid; place-items:center; font-family:'Space Grotesk',sans-serif; font-weight:700; font-size:11px; }
  .avatar.lg { width:40px; height:40px; font-size:13px; }

  .project-card { background:#FFFFFF; border:3px solid #0A0A0A; border-radius:12px; padding:22px;
    box-shadow:6px 6px 0 0 #0A0A0A; position:relative; overflow:hidden;
    transition:transform 200ms cubic-bezier(.2,.8,.2,1), box-shadow 200ms cubic-bezier(.2,.8,.2,1); }
  .project-card:hover { transform:translate(-3px,-3px); box-shadow:9px 9px 0 0 #2563EB; }
  .project-card.cream { background:#FFFBEB; }

  .prog-track { height:12px; background:#FFFBEB; border:2.5px solid #0A0A0A; border-radius:6px; overflow:hidden; }
  .prog-fill { height:100%; background:#84CC16; border-right:2.5px solid #0A0A0A;
    background-image:repeating-linear-gradient(45deg, transparent, transparent 6px, rgba(10,10,10,0.18) 6px, rgba(10,10,10,0.18) 7px); }
  .prog-fill.amber { background-color:#F59E0B; }
  .prog-fill.blue  { background-color:#2563EB; }
  .prog-fill.red   { background-color:#EF4444; }

  @keyframes urgent-pulse { 0%,100% { box-shadow:0 0 0 0 rgba(239,68,68,.6); } 50% { box-shadow:0 0 0 6px rgba(239,68,68,0); } }
  .urgent-dot { width:9px; height:9px; background:#EF4444; border:2px solid #0A0A0A; border-radius:9999px;
    animation:urgent-pulse 1.6s ease-in-out infinite; }

  .filter-tab { display:inline-flex; align-items:center; gap:8px; background:#FFFFFF; border:3px solid #0A0A0A;
    border-radius:10px; padding:10px 16px; font-family:'Space Grotesk',sans-serif; font-weight:700;
    font-size:13px; text-transform:uppercase; letter-spacing:0.04em; box-shadow:4px 4px 0 0 #0A0A0A;
    cursor:pointer; text-decoration:none; transition:all 180ms cubic-bezier(.2,.8,.2,1); }
  .filter-tab:hover { transform:translate(-2px,-2px); box-shadow:6px 6px 0 0 #0A0A0A; }
  .filter-tab.active { background:#2563EB; color:#FFFFFF; border-color:#0A0A0A; }
  .filter-tab .count { font-family:'JetBrains Mono',monospace; background:#FFFBEB; color:#0A0A0A;
    border:2px solid #0A0A0A; border-radius:6px; padding:0 6px; font-size:11px; }
  .filter-tab.active .count { background:#FFFFFF; }

  .meta-chip { display:inline-flex; align-items:center; gap:6px; background:#FFFBEB; color:#0A0A0A;
    border:2px solid #0A0A0A; border-radius:9999px; padding:3px 10px; font-family:'JetBrains Mono',monospace;
    font-weight:600; font-size:11px; box-shadow:2px 2px 0 0 #0A0A0A; }
  .meta-chip.urgent { background:#EF4444; color:#FFFFFF; }
  .meta-chip.lime   { background:#84CC16; }
  .meta-chip.amber  { background:#F59E0B; }
  .meta-chip.cyan   { background:#06B6D4; color:#FFFFFF; }

  .lead-stamp { display:inline-flex; align-items:center; gap:4px; background:#0A0A0A; color:#FFFFFF;
    border-radius:6px; padding:2px 8px; font-family:'JetBrains Mono',monospace; font-weight:700;
    font-size:10px; letter-spacing:0.12em; }
  .lead-stamp.dev { background:#FFFFFF; color:#0A0A0A; border:2px solid #0A0A0A; }

  .btn-primary { display:inline-flex; align-items:center; justify-content:center; gap:8px;
    background:#2563EB; color:#FFFFFF; border:3px solid #0A0A0A; border-radius:10px;
    padding:12px 20px; font-family:'Space Grotesk',sans-serif; font-weight:700; font-size:14px;
    letter-spacing:0.02em; text-transform:uppercase; box-shadow:6px 6px 0 0 #0A0A0A;
    cursor:pointer; text-decoration:none; }
  .btn-secondary { background:#FFFFFF; color:#0A0A0A; }

  .stagger > * { opacity:0; transform:translateY(12px); animation:rise 520ms cubic-bezier(.2,.8,.2,1) forwards; }
  .stagger > *:nth-child(1) { animation-delay:50ms; }
  .stagger > *:nth-child(2) { animation-delay:100ms; }
  .stagger > *:nth-child(3) { animation-delay:150ms; }
  .stagger > *:nth-child(4) { animation-delay:200ms; }
  .stagger > *:nth-child(5) { animation-delay:250ms; }
  .stagger > *:nth-child(6) { animation-delay:300ms; }
  @keyframes rise { to { opacity:1; transform:translateY(0); } }

  .logout-btn { width:32px; height:32px; display:grid; place-items:center; border:2px solid #0A0A0A;
    border-radius:6px; background:#FFFFFF; cursor:pointer; transition:background-color 140ms ease, color 140ms ease; }
  .logout-btn:hover { background:#EF4444; color:#FFFFFF; }

  .nice-scroll::-webkit-scrollbar { width:8px; }
  .nice-scroll::-webkit-scrollbar-thumb { background:#0A0A0A; border-radius:8px; }

  @media (max-width: 1023px) {
    .sidebar { display:none !important; }
    .main-pad { padding-left:16px !important; padding-right:16px !important; }
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
          <div class="font-display font-bold text-[19px]">DevTrack</div>
          <div class="font-mono text-[9px] text-ink/60 mt-0.5 tracking-widest">v1.0 · TECHNOPARK</div>
        </div>
      </a>
    </div>

    <nav class="px-4 pt-5 flex-1 overflow-y-auto nice-scroll">
      <div class="font-mono text-[10px] font-semibold tracking-[0.18em] text-ink/50 uppercase mb-2 px-2">Menu</div>
      <ul class="space-y-1.5">
        <li><a href="{{ route('dashboard') }}" class="nav-item">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
          Dashboard
        </a></li>
        <li><a href="{{ route('projects.index') }}" class="nav-item active">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
          Projects
          @if ($sidebarBadges['projects'] > 0)<span class="badge">{{ $sidebarBadges['projects'] }}</span>@endif
        </a></li>
        <li><a href="{{ route('projects.archives') }}" class="nav-item">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8v13H3V8"/><path d="M1 3h22v5H1z"/><path d="M10 12h4"/></svg>
          Archives
          @if ($sidebarBadges['archives'] > 0)<span class="badge">{{ $sidebarBadges['archives'] }}</span>@endif
        </a></li>
        <li><a href="{{ route('profile.edit') }}" class="nav-item">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
          Settings
        </a></li>
      </ul>
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
        <nav class="flex items-center gap-2 font-mono text-[12px] font-semibold">
          <a href="{{ route('dashboard') }}" class="text-ink/60 hover:text-ink">Dashboard</a>
          <span class="text-ink/30">/</span>
          <span class="text-ink">Projects</span>
        </nav>

        <div class="ml-auto flex items-center gap-3">
          @can('create', App\Models\Project::class)
            <a href="{{ route('projects.create') }}" class="btn-primary brutal-tap" style="padding:8px 14px; font-size:12px; box-shadow:4px 4px 0 0 #0A0A0A;">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
              New Project
            </a>
          @endcan
          <div class="hidden md:flex items-center gap-2 bg-white border-[3px] border-ink rounded-[10px] py-1 px-2 shadow-brutal-sm">
            <div class="avatar" style="background:#06B6D4;color:#0A0A0A;">{{ $userInitials }}</div>
            <div class="leading-none mr-1">
              <div class="font-display font-bold text-[12px]">{{ Str::limit($user->name, 14, '') }}</div>
              <div class="font-mono text-[10px] text-ink/60">{{ $isLead ? 'lead' : 'dev' }}</div>
            </div>
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
          <div class="font-mono text-[11px] font-semibold tracking-[0.18em] text-electric-blue uppercase mb-2">// workspace</div>
          <h1 class="font-display font-bold text-[44px] sm:text-[56px] leading-[0.98] tracking-tight">
            Your <span class="relative inline-block">projects
              <svg class="absolute -bottom-2 left-0 w-full" viewBox="0 0 200 12" preserveAspectRatio="none" height="10">
                <path d="M2 8 Q 60 2, 120 7 T 198 6" stroke="#84CC16" stroke-width="6" fill="none" stroke-linecap="round"/>
              </svg>
            </span>
          </h1>
          <p class="text-ink/70 mt-3 text-[16px] max-w-[560px]">
            @if ($filters['all'] > 0)
              {{ $filters['all'] }} {{ $filters['all'] === 1 ? 'projet' : 'projets' }} sur le board —
              <span class="font-bold text-ink">{{ $filters['lead'] }} en tant que lead</span>,
              {{ $filters['dev'] }} en tant que developer.
            @else
              Aucun projet pour l'instant. Lancez le premier pour démarrer.
            @endif
          </p>
        </div>

        <div class="flex items-center gap-3">
          <a href="{{ route('projects.archives') }}" class="btn-primary btn-secondary brutal-tap">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8v13H3V8"/><path d="M1 3h22v5H1z"/><path d="M10 12h4"/></svg>
            Archives
          </a>
          @can('create', App\Models\Project::class)
            <a href="{{ route('projects.create') }}" class="btn-primary brutal-tap">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
              New Project
            </a>
          @endcan
        </div>
      </section>

      {{-- SECTION HEADER + FILTERS --}}
      <section class="flex flex-col lg:flex-row lg:items-end gap-4 mb-6">
        <div class="flex-1">
          <div class="font-mono text-[11px] font-semibold tracking-[0.18em] text-ink/60 uppercase mb-1">// Section</div>
          <h2 class="font-display font-bold text-[28px] tracking-tight">All projects</h2>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <a href="{{ route('projects.index') }}" class="filter-tab {{ $roleFilter === 'all' ? 'active' : '' }}">All <span class="count">{{ $filters['all'] }}</span></a>
          <a href="{{ route('projects.index', ['role' => 'lead']) }}" class="filter-tab {{ $roleFilter === 'lead' ? 'active' : '' }}">As Lead <span class="count">{{ $filters['lead'] }}</span></a>
          <a href="{{ route('projects.index', ['role' => 'dev']) }}" class="filter-tab {{ $roleFilter === 'dev' ? 'active' : '' }}">As Developer <span class="count">{{ $filters['dev'] }}</span></a>
        </div>
      </section>

      {{-- PROJECTS GRID --}}
      <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 stagger">

        @forelse ($visible as $project)
          @php
            $role      = $project->pivot->role ?? 'developer';
            $total     = (int) $project->tasks_count;
            $done      = (int) $project->completed_tasks_count;
            $urgent    = (int) ($project->urgent_tasks_count ?? 0);
            $pct       = $total > 0 ? (int) round($done / $total * 100) : 0;
            $deadline  = Carbon::parse($project->deadline);
            $isOverdue = $deadline->isPast() && $pct < 100;
            $isUrgent  = $urgent > 0 || $isOverdue;
            $cardClass = $pct >= 90 && ! $isUrgent ? 'cream' : '';
            $progName  = $progClass($pct, $isUrgent);
          @endphp

          <article class="project-card {{ $cardClass }}" @if ($isUrgent) style="border-left:8px solid #EF4444;" @endif>
            <div class="flex items-start justify-between mb-3">
              <div class="flex items-center gap-2 flex-wrap">
                @if ($role === 'lead')
                  <span class="lead-stamp">★ LEAD</span>
                @else
                  <span class="lead-stamp dev">DEV</span>
                @endif

                @if ($isOverdue)
                  <span class="meta-chip urgent">⚠ EN RETARD</span>
                @elseif ($urgent > 0)
                  <span class="meta-chip urgent"><span class="urgent-dot" style="background:#FFFFFF;border-color:#0A0A0A;"></span> URGENT</span>
                @elseif ($pct >= 90)
                  <span class="meta-chip lime">SHIP-READY</span>
                @elseif ($total === 0)
                  <span class="meta-chip">FRESH</span>
                @else
                  <span class="meta-chip cyan">IN PROGRESS</span>
                @endif
              </div>

              @can('update', $project)
                <a href="{{ route('projects.edit', $project) }}" class="w-7 h-7 grid place-items-center hover:bg-ink/10 rounded-md" title="Éditer">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0A0A0A" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                </a>
              @endcan
            </div>

            <a href="{{ route('projects.show', $project) }}" class="block">
              <h3 class="font-display font-bold text-[22px] leading-tight text-ink mb-2 hover:underline decoration-2 underline-offset-4">
                {{ $project->title }}
              </h3>
            </a>
            <p class="text-[14px] text-ink/70 line-clamp-2 mb-5">
              {{ Str::limit($project->description, 140) }}
            </p>

            <div class="flex items-center justify-between gap-2 mb-3 flex-wrap">
              <div class="meta-chip">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                {{ $done }}/{{ $total }} ✓
              </div>

              <div class="meta-chip {{ $isOverdue ? 'urgent' : ($deadline->diffInDays(now(), false) >= -7 ? 'amber' : '') }}">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                {{ $deadline->translatedFormat('M j') }}
              </div>

              <div class="flex -space-x-2">
                @foreach ($project->users->take(3) as $m)
                  @php $c = $avatarColor($m->id); @endphp
                  <div class="avatar" style="background:{{ $c['bg'] }};color:{{ $c['fg'] }};" title="{{ $m->name }}">{{ $initials($m->name) }}</div>
                @endforeach
                @if ($project->users->count() > 3)
                  <div class="avatar" style="background:#FFFFFF;color:#0A0A0A;">+{{ $project->users->count() - 3 }}</div>
                @endif
              </div>
            </div>

            <div>
              <div class="flex items-center justify-between mb-1.5">
                <div class="font-mono text-[10px] font-bold tracking-widest text-ink/60 uppercase">Progress</div>
                <div class="font-mono text-[12px] font-bold {{ $isUrgent ? 'text-red-accent' : ($pct >= 90 ? 'text-lime-accent' : 'text-ink') }}">{{ $pct }}%</div>
              </div>
              <div class="prog-track"><div class="prog-fill {{ $progName }}" style="width:{{ $pct }}%"></div></div>
            </div>
          </article>
        @empty
          <article class="project-card md:col-span-2 xl:col-span-3" style="text-align:center; padding:48px 24px;">
            <div class="font-display font-bold text-[20px] mb-2">
              {{ $filters['all'] === 0 ? 'Aucun projet' : 'Aucun projet pour ce filtre' }}
            </div>
            <p class="text-[14px] text-ink/60 mb-5">
              {{ $filters['all'] === 0 ? 'Lancez votre premier projet pour commencer à suivre les tâches.' : 'Essayez un autre filtre.' }}
            </p>
            @if ($filters['all'] === 0)
              @can('create', App\Models\Project::class)
                <a href="{{ route('projects.create') }}" class="btn-primary brutal-tap" style="display:inline-flex;">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                  Créer mon premier projet
                </a>
              @endcan
            @else
              <a href="{{ route('projects.index') }}" class="btn-primary btn-secondary brutal-tap" style="display:inline-flex;">Voir tous les projets</a>
            @endif
          </article>
        @endforelse

        @if ($visible->isNotEmpty())
          @can('create', App\Models\Project::class)
            <a href="{{ route('projects.create') }}" class="project-card brutal-tap-blue" style="background:repeating-linear-gradient(45deg, #FFFBEB, #FFFBEB 10px, #FFFFFF 10px, #FFFFFF 20px); display:flex; flex-direction:column; align-items:center; justify-content:center; min-height:280px; cursor:pointer; text-decoration:none;">
              <div class="w-16 h-16 grid place-items-center bg-electric-blue border-[3px] border-ink rounded-[12px] shadow-brutal-sm mb-4">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
              </div>
              <div class="font-display font-bold text-[18px] text-ink mb-1">Start a new project</div>
              <p class="text-[13px] text-ink/60 text-center max-w-[260px]">Spin up a fresh space, invite the crew, assign first tasks.</p>
            </a>
          @endcan
        @endif

      </section>

      <div class="mt-10 flex flex-col md:flex-row items-center justify-between gap-3 font-mono text-[11px] text-ink/50">
        <div>&copy; {{ date('Y') }} DevTrack — Made in Agadir 🇲🇦</div>
        <a href="{{ route('dashboard') }}" class="hover:text-ink">← Retour au dashboard</a>
      </div>

    </div>
  </main>
</div>

</body>
</html>