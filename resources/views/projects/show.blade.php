@php
    use Illuminate\Support\Str;
    use Illuminate\Support\Carbon;

    $user      = auth()->user();
    $isLead    = $role === 'lead';
    $deadline  = Carbon::parse($project->deadline);
    $isOverdue = $deadline->isPast() && $stats['pct'] < 100;
    $hoursLeft = $deadline->diffInHours(now(), false);

    $initials = fn ($name) => collect(explode(' ', trim((string) $name)))
        ->filter()->take(2)
        ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))
        ->implode('');

    $palette = [
        ['bg' => '#06B6D4', 'fg' => '#0A0A0A'],
        ['bg' => '#EC4899', 'fg' => '#FFFFFF'],
        ['bg' => '#84CC16', 'fg' => '#0A0A0A'],
        ['bg' => '#F59E0B', 'fg' => '#0A0A0A'],
        ['bg' => '#2563EB', 'fg' => '#FFFFFF'],
        ['bg' => '#EF4444', 'fg' => '#FFFFFF'],
    ];
    $avatarColor = fn ($id) => $palette[((int) $id) % count($palette)];

    $statusMeta = [
        'todo'        => ['label' => 'À faire',  'bg' => '#FFFBEB', 'fg' => '#0A0A0A'],
        'in_progress' => ['label' => 'En cours', 'bg' => '#06B6D4', 'fg' => '#FFFFFF'],
        'done'        => ['label' => 'Terminé',  'bg' => '#84CC16', 'fg' => '#0A0A0A'],
    ];

    $userInitials = $initials($user->name) ?: 'U';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>DevTrack — {{ $project->title }}</title>

<script src="https://cdn.tailwindcss.com"></script>

<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet" />

<script>
  tailwind.config = { theme: { extend: {
    colors: { 'electric-blue':'#2563EB','electric-cyan':'#06B6D4','ink':'#0A0A0A','cream':'#FFFBEB',
      'lime-accent':'#84CC16','amber-accent':'#F59E0B','red-accent':'#EF4444','pink-pop':'#EC4899','gray-soft':'#F5F5F5' },
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

  .card { background:#FFFFFF; border:3px solid #0A0A0A; border-radius:12px; box-shadow:6px 6px 0 0 #0A0A0A; }
  .stat-card { background:#FFFFFF; border:3px solid #0A0A0A; border-radius:12px; padding:18px;
    box-shadow:6px 6px 0 0 #0A0A0A; }

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

  .global-prog { height:18px; background:#FFFFFF; border:3px solid #0A0A0A; border-radius:8px;
    overflow:hidden; position:relative; }
  .global-prog .fill { height:100%; background:linear-gradient(90deg,#2563EB 0%,#06B6D4 100%);
    border-right:3px solid #0A0A0A; position:relative; }
  .global-prog .fill::after { content:''; position:absolute; inset:0;
    background-image:repeating-linear-gradient(45deg, transparent, transparent 8px, rgba(10,10,10,.18) 8px, rgba(10,10,10,.18) 9px); }

  .task-row { background:#FFFFFF; border:3px solid #0A0A0A; border-radius:12px; padding:16px 18px;
    box-shadow:4px 4px 0 0 #0A0A0A; transition:transform 180ms cubic-bezier(.2,.8,.2,1), box-shadow 180ms cubic-bezier(.2,.8,.2,1); }
  .task-row:hover { transform:translate(-2px,-2px); box-shadow:6px 6px 0 0 #2563EB; }
  .task-row.urgent { border-left-width:8px; border-left-color:#EF4444; }
  .task-row.done   { background:#F5F5F5; }
  .task-row.done .ttl { text-decoration:line-through; opacity:.65; }

  .status-pill { display:inline-flex; align-items:center; gap:6px; border:2.5px solid #0A0A0A; border-radius:9999px;
    padding:4px 12px; font-family:'JetBrains Mono',monospace; font-weight:700; font-size:11px; letter-spacing:.04em; }

  .btn { display:inline-flex; align-items:center; justify-content:center; gap:8px;
    border:3px solid #0A0A0A; border-radius:10px; padding:12px 20px;
    font-family:'Space Grotesk',sans-serif; font-weight:700; font-size:14px;
    letter-spacing:.02em; text-transform:uppercase; box-shadow:6px 6px 0 0 #0A0A0A;
    cursor:pointer; text-decoration:none; }
  .btn.primary { background:#2563EB; color:#FFFFFF; }
  .btn.ghost   { background:#FFFFFF; color:#0A0A0A; }
  .btn.danger  { background:#EF4444; color:#FFFFFF; }
  .btn.sm      { padding:8px 14px; font-size:12px; box-shadow:4px 4px 0 0 #0A0A0A; }

  .icon-btn { width:44px; height:44px; background:#FFFFFF; border:3px solid #0A0A0A; border-radius:10px;
    display:grid; place-items:center; box-shadow:4px 4px 0 0 #0A0A0A; cursor:pointer;
    transition:all 180ms cubic-bezier(.2,.8,.2,1); }
  .icon-btn:hover { transform:translate(-2px,-2px); box-shadow:6px 6px 0 0 #0A0A0A; }

  .field { width:100%; background:#FFFFFF; border:3px solid #0A0A0A; border-radius:10px;
    padding:10px 14px; font-family:'Inter',sans-serif; font-weight:500; font-size:14px;
    transition:box-shadow 160ms ease, transform 160ms ease, border-color 160ms ease; }
  .field:focus { outline:none; border-color:#2563EB; box-shadow:4px 4px 0 0 #2563EB; transform:translate(-2px,-2px); }

  .stagger > * { opacity:0; transform:translateY(12px); animation:rise 520ms cubic-bezier(.2,.8,.2,1) forwards; }
  .stagger > *:nth-child(1) { animation-delay:50ms; }
  .stagger > *:nth-child(2) { animation-delay:100ms; }
  .stagger > *:nth-child(3) { animation-delay:150ms; }
  .stagger > *:nth-child(4) { animation-delay:200ms; }
  .stagger > *:nth-child(5) { animation-delay:250ms; }
  .stagger > *:nth-child(6) { animation-delay:300ms; }
  @keyframes rise { to { opacity:1; transform:translateY(0); } }

  .nice-scroll::-webkit-scrollbar { width:8px; }
  .nice-scroll::-webkit-scrollbar-thumb { background:#0A0A0A; border-radius:8px; }

  .logout-btn { width:32px; height:32px; display:grid; place-items:center; border:2px solid #0A0A0A;
    border-radius:6px; background:#FFFFFF; cursor:pointer; transition:background-color 140ms ease, color 140ms ease; }
  .logout-btn:hover { background:#EF4444; color:#FFFFFF; }

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
          <svg width="22" height="22" viewBox="0 0 24 24" fill="white" stroke="white" stroke-width="3" stroke-linejoin="round"><path d="M13 2 L4 14 H11 L11 22 L20 10 H13 Z"/></svg>
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
        <div class="hidden md:flex items-center gap-2 font-mono text-[11px] font-semibold tracking-widest uppercase text-ink/60">
          <a href="{{ route('dashboard') }}" class="hover:text-ink">// dashboard</a>
          <span class="text-ink/30">/</span>
          <a href="{{ route('projects.index') }}" class="hover:text-ink">projects</a>
          <span class="text-ink/30">/</span>
          <span class="text-ink truncate max-w-[280px]">{{ $project->title }}</span>
        </div>

        <div class="ml-auto flex items-center gap-3">
          @can('update', $project)
            <a href="{{ route('projects.edit', $project) }}" class="btn ghost sm brutal-tap">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
              Modifier
            </a>
          @endcan
          @can('delete', $project)
            <form method="POST" action="{{ route('projects.destroy', $project) }}" onsubmit="return confirm('Archiver ce projet ?')">
              @csrf @method('DELETE')
              <button class="btn danger sm brutal-tap">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8v13H3V8"/><path d="M1 3h22v5H1z"/><path d="M10 12h4"/></svg>
                Archiver
              </button>
            </form>
          @endcan
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
      <section class="mb-8">
        <div class="flex items-center gap-2 mb-3 flex-wrap">
          @if ($isLead)
            <span class="lead-stamp">★ LEAD</span>
          @else
            <span class="lead-stamp dev">DEV</span>
          @endif

          @if ($isOverdue)
            <span class="meta-chip urgent">⚠ EN RETARD</span>
          @elseif ($stats['urgent'] > 0)
            <span class="meta-chip urgent">{{ $stats['urgent'] }} URGENT</span>
          @elseif ($stats['pct'] >= 90)
            <span class="meta-chip lime">SHIP-READY</span>
          @elseif ($stats['total'] === 0)
            <span class="meta-chip">FRESH</span>
          @else
            <span class="meta-chip cyan">IN PROGRESS</span>
          @endif

          <span class="font-mono text-[11px] font-semibold tracking-[0.18em] text-electric-blue uppercase ml-1">
            // deadline · {{ $deadline->translatedFormat('l d M Y') }}
          </span>
        </div>

        <h1 class="font-display font-bold text-[40px] sm:text-[52px] leading-[0.98] tracking-tight">
          {{ $project->title }}
        </h1>

        <p class="text-ink/70 mt-4 text-[16px] max-w-[760px] whitespace-pre-line">{{ $project->description }}</p>
      </section>

      {{-- STATS --}}
      <section class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <div class="stat-card">
          <div class="font-mono text-[10px] font-bold tracking-widest text-ink/60 uppercase">Total</div>
          <div class="font-display font-bold text-[44px] leading-none mt-3">{{ $stats['total'] }}</div>
          <div class="font-mono text-[11px] text-ink/60 mt-3">tasks on board</div>
        </div>
        <div class="stat-card">
          <div class="flex items-center justify-between">
            <div class="font-mono text-[10px] font-bold tracking-widest text-ink/60 uppercase">In progress</div>
            <span class="w-7 h-7 grid place-items-center bg-electric-cyan border-2 border-ink rounded-md">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </span>
          </div>
          <div class="font-display font-bold text-[44px] leading-none mt-3">{{ $stats['progress'] }}</div>
          <div class="font-mono text-[11px] text-ink/60 mt-3">{{ $stats['todo'] }} todo</div>
        </div>
        <div class="stat-card">
          <div class="flex items-center justify-between">
            <div class="font-mono text-[10px] font-bold tracking-widest text-ink/60 uppercase">Done</div>
            <span class="w-7 h-7 grid place-items-center bg-lime-accent border-2 border-ink rounded-md">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0A0A0A" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            </span>
          </div>
          <div class="font-display font-bold text-[44px] leading-none mt-3">{{ $stats['done'] }}</div>
          <div class="font-mono text-[11px] text-ink/60 mt-3">{{ $stats['pct'] }}% complete</div>
        </div>
        <div class="stat-card">
          <div class="flex items-center justify-between">
            <div class="font-mono text-[10px] font-bold tracking-widest text-ink/60 uppercase">Crew</div>
            <span class="w-7 h-7 grid place-items-center bg-pink-pop border-2 border-ink rounded-md">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            </span>
          </div>
          <div class="font-display font-bold text-[44px] leading-none mt-3">{{ $project->users->count() }}</div>
          <div class="flex -space-x-2 mt-3">
            @foreach ($project->users->take(4) as $m)
              @php $c = $avatarColor($m->id); @endphp
              <div class="avatar" style="background:{{ $c['bg'] }};color:{{ $c['fg'] }};" title="{{ $m->name }}">{{ $initials($m->name) }}</div>
            @endforeach
            @if ($project->users->count() > 4)
              <div class="avatar" style="background:#FFFFFF;color:#0A0A0A;">+{{ $project->users->count() - 4 }}</div>
            @endif
          </div>
        </div>
      </section>

      {{-- PROGRESS BAR --}}
      <section class="card p-6 mb-8">
        <div class="flex items-center justify-between mb-2">
          <div>
            <div class="font-mono text-[10px] font-bold tracking-widest text-ink/60 uppercase">// project velocity</div>
            <div class="font-display font-bold text-[20px] mt-1">{{ $stats['done'] }} / {{ $stats['total'] }} tasks shipped</div>
          </div>
          <div class="font-display font-bold text-[28px] {{ $isOverdue ? 'text-red-accent' : 'text-electric-blue' }}">{{ $stats['pct'] }}%</div>
        </div>
        <div class="global-prog"><div class="fill" style="width: {{ $stats['pct'] }}%"></div></div>
      </section>

      {{-- TWO COLUMNS : TASKS + MEMBERS --}}
      <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- TASKS COLUMN --}}
        <div class="lg:col-span-2 space-y-3">
          <div class="flex items-end justify-between mb-2">
            <div>
              <div class="font-mono text-[11px] font-semibold tracking-[0.18em] text-ink/60 uppercase">// Section</div>
              <h2 class="font-display font-bold text-[24px] tracking-tight">Tâches</h2>
            </div>
            @can('createTask', $project)
              <a href="{{ route('projects.tasks.create', $project) }}" class="btn primary sm brutal-tap">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                Nouvelle tâche
              </a>
            @endcan
          </div>

          @forelse ($project->tasks as $task)
            @php
              $td  = Carbon::parse($task->deadline);
              $diff = now()->diffInHours($td, false);
              $isUrgent = $task->status !== 'done' && $diff >= 0 && $diff <= 48;
              $isLate   = $task->status !== 'done' && $diff < 0;
              $sm = $statusMeta[$task->status] ?? $statusMeta['todo'];
              $cls = $task->status === 'done' ? 'done' : ($isUrgent || $isLate ? 'urgent' : '');
            @endphp

            <article class="task-row {{ $cls }}">
              <div class="flex items-start gap-3">
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2 flex-wrap mb-1">
                    <span class="status-pill" style="background:{{ $sm['bg'] }};color:{{ $sm['fg'] }};">
                      {{ $sm['label'] }}
                    </span>
                    @if ($isLate)
                      <span class="meta-chip urgent">⚠ EN RETARD</span>
                    @elseif ($isUrgent)
                      <span class="meta-chip urgent">URGENT · {{ (int) abs($diff) }}h</span>
                    @endif
                    <span class="meta-chip">PRIO · {{ Str::upper($task->priority) }}</span>
                  </div>

                  <a href="{{ route('projects.tasks.show', [$project, $task]) }}"
                     class="ttl block font-display font-bold text-[18px] leading-tight hover:underline decoration-2 underline-offset-4">
                    {{ $task->title }}
                  </a>

                  <div class="font-mono text-[11px] text-ink/60 mt-2 flex items-center gap-3 flex-wrap">
                    <span>📅 {{ $td->format('d/m/Y H:i') }}</span>
                    @if ($task->user)
                      @php $c = $avatarColor($task->user->id); @endphp
                      <span class="inline-flex items-center gap-1.5">
                        <span class="avatar" style="width:22px;height:22px;font-size:9px;background:{{ $c['bg'] }};color:{{ $c['fg'] }};">{{ $initials($task->user->name) }}</span>
                        {{ $task->user->name }}
                      </span>
                    @else
                      <span>Non assigné</span>
                    @endif
                  </div>
                </div>

                <div class="flex items-center gap-2 flex-shrink-0">
                  @can('updateStatus', $task)
                    @if ($task->status !== 'done')
                      <form method="POST" action="{{ route('projects.tasks.updateStatus', [$project, $task]) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="{{ $task->status === 'todo' ? 'in_progress' : 'done' }}">
                        <button class="btn ghost sm brutal-tap" title="Avancer le statut">
                          {{ $task->status === 'todo' ? '▶ Démarrer' : '✓ Terminer' }}
                        </button>
                      </form>
                    @endif
                  @endcan
                  @can('update', $task)
                    <a href="{{ route('projects.tasks.edit', [$project, $task]) }}" class="icon-btn" title="Éditer">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0A0A0A" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                    </a>
                  @endcan
                  @can('delete', $task)
                    <form method="POST" action="{{ route('projects.tasks.destroy', [$project, $task]) }}" onsubmit="return confirm('Supprimer cette tâche ?')">
                      @csrf @method('DELETE')
                      <button class="icon-btn" title="Supprimer" style="background:#FEE2E2;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                      </button>
                    </form>
                  @endcan
                </div>
              </div>
            </article>
          @empty
            <div class="card p-10 text-center">
              <div class="font-display font-bold text-[20px] mb-2">Aucune tâche</div>
              <p class="text-[14px] text-ink/60 mb-5">Créez la première tâche pour lancer le projet.</p>
              @can('createTask', $project)
                <a href="{{ route('projects.tasks.create', $project) }}" class="btn primary brutal-tap" style="display:inline-flex;">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                  Nouvelle tâche
                </a>
              @endcan
            </div>
          @endforelse
        </div>

        {{-- MEMBERS COLUMN --}}
        <aside class="space-y-6">
          <div class="card p-5">
            <div class="flex items-center justify-between mb-4">
              <div>
                <div class="font-mono text-[10px] font-bold tracking-widest text-ink/60 uppercase">// Crew</div>
                <h3 class="font-display font-bold text-[20px] mt-0.5">Membres</h3>
              </div>
              <span class="meta-chip">{{ $project->users->count() }}</span>
            </div>

            <ul class="space-y-2.5">
              @foreach ($project->users as $member)
                @php $c = $avatarColor($member->id); @endphp
                <li class="flex items-center gap-3 p-2.5 border-2 border-ink rounded-[10px] bg-cream">
                  <div class="avatar lg" style="background:{{ $c['bg'] }};color:{{ $c['fg'] }};">{{ $initials($member->name) }}</div>
                  <div class="flex-1 min-w-0">
                    <div class="font-display font-bold text-[13px] truncate">{{ $member->name }}</div>
                    <div class="font-mono text-[10px] text-ink/60 truncate">{{ $member->email }}</div>
                  </div>
                  @if ($member->pivot->role === 'lead')
                    <span class="lead-stamp">★ LEAD</span>
                  @else
                    <span class="lead-stamp dev">DEV</span>
                  @endif

                  @can('manageMembers', $project)
                    @if ($member->pivot->role !== 'lead')
                      <form method="POST" action="{{ route('projects.members.destroy', [$project, $member]) }}" onsubmit="return confirm('Retirer ce membre ?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="logout-btn" title="Retirer">
                          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" x2="6" y1="6" y2="18"/><line x1="6" x2="18" y1="6" y2="18"/></svg>
                        </button>
                      </form>
                    @endif
                  @endcan
                </li>
              @endforeach
            </ul>

            @can('manageMembers', $project)
              <form method="POST" action="{{ route('projects.members.store', $project) }}" class="mt-5 pt-5 border-t-[3px] border-dashed border-ink/20">
                @csrf
                <label class="font-mono text-[10px] font-bold tracking-widest text-ink/60 uppercase block mb-2">+ Ajouter un developer</label>
                <input type="email" name="email" placeholder="email@devtrack.dev" value="{{ old('email') }}" class="field" required />
                @error('email')
                  <p class="font-mono text-[11px] text-red-accent mt-1.5">{{ $message }}</p>
                @enderror
                <button class="btn primary sm brutal-tap mt-3 w-full">Ajouter</button>
              </form>
            @endcan
          </div>

          <div class="card p-5 bg-amber-accent/30">
            <div class="font-mono text-[10px] font-bold tracking-widest mb-1">⚡ INFOS</div>
            <div class="font-display font-bold text-[14px] leading-tight mb-3">Détails du projet</div>
            <dl class="space-y-2 font-mono text-[11px]">
              <div class="flex justify-between"><dt class="text-ink/60">Créé le</dt><dd class="font-bold">{{ Carbon::parse($project->created_at)->format('d/m/Y') }}</dd></div>
              <div class="flex justify-between"><dt class="text-ink/60">Deadline</dt><dd class="font-bold {{ $isOverdue ? 'text-red-accent' : '' }}">{{ $deadline->format('d/m/Y') }}</dd></div>
              <div class="flex justify-between"><dt class="text-ink/60">Reste</dt>
                <dd class="font-bold">
                  @if ($isOverdue)
                    <span class="text-red-accent">{{ (int) abs($hoursLeft) }}h dépassé</span>
                  @else
                    {{ $deadline->diffForHumans(null, true) }}
                  @endif
                </dd>
              </div>
              <div class="flex justify-between"><dt class="text-ink/60">Mon rôle</dt><dd class="font-bold uppercase">{{ $role }}</dd></div>
            </dl>
          </div>
        </aside>
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
