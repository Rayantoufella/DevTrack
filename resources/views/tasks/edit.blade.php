@php
    use Illuminate\Support\Str;
    use Illuminate\Support\Carbon;

    $user = auth()->user();
    $initials = fn ($name) => collect(explode(' ', trim((string) $name)))
        ->filter()->take(2)
        ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))
        ->implode('');
    $userInitials = $initials($user->name) ?: 'U';

    $statuses  = ['todo' => 'À faire', 'in_progress' => 'En cours', 'done' => 'Terminé'];
    $curStatus = old('status', $task->status);
    $curPrio   = old('priority', $task->priority);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>DevTrack — Modifier la tâche</title>

<script src="https://cdn.tailwindcss.com"></script>

<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet" />

<script>
  tailwind.config = { theme: { extend: {
    colors: { 'electric-blue':'#2563EB','electric-cyan':'#06B6D4','ink':'#0A0A0A','cream':'#FFFBEB',
      'lime-accent':'#84CC16','amber-accent':'#F59E0B','red-accent':'#EF4444','pink-pop':'#EC4899' },
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

  .form-card { background:#FFFFFF; border:4px solid #0A0A0A; border-radius:14px;
    box-shadow:12px 12px 0 0 #0A0A0A; overflow:hidden; }

  .form-input { width:100%; background:#FFFFFF; border:3px solid #0A0A0A; border-radius:10px;
    padding:11px 14px; font-family:'Inter',sans-serif; font-weight:500; font-size:14px;
    transition:box-shadow 160ms ease, transform 160ms ease, border-color 160ms ease; }
  .form-input:focus { outline:none; border-color:#2563EB; box-shadow:4px 4px 0 0 #2563EB; transform:translate(-2px,-2px); }
  .form-input.err { border-color:#EF4444; box-shadow:4px 4px 0 0 #EF4444; }
  .form-label { display:block; font-family:'Space Grotesk',sans-serif; font-weight:700; font-size:11px;
    text-transform:uppercase; letter-spacing:0.14em; color:#0A0A0A; margin-bottom:8px; }
  .form-err { margin-top:6px; display:flex; align-items:center; gap:6px;
    font-family:'JetBrains Mono',monospace; font-weight:600; font-size:11px; color:#EF4444; }

  /* PRIORITY / STATUS — cartes radio (CSS pur) */
  .pick-grid { display:grid; grid-template-columns:repeat(3, 1fr); gap:12px; align-items:stretch; }
  .pick { position:relative; display:block; cursor:pointer; }
  .pick input { position:absolute; opacity:0; pointer-events:none; }
  .pick-card { height:100%; min-height:64px; display:flex; flex-direction:column;
    align-items:center; justify-content:center; gap:2px;
    padding:10px 12px; background:#FFFFFF; border:3px solid #0A0A0A; border-radius:10px;
    box-shadow:4px 4px 0 0 #0A0A0A; text-align:center; transition:all 160ms cubic-bezier(.2,.8,.2,1); }
  .pick:hover .pick-card { transform:translate(-2px,-2px); box-shadow:6px 6px 0 0 #0A0A0A; }
  .pick input:checked + .pick-card.low    { background:#CFFAFE; box-shadow:5px 5px 0 0 #06B6D4; }
  .pick input:checked + .pick-card.medium { background:#F59E0B; box-shadow:5px 5px 0 0 #B45309; }
  .pick input:checked + .pick-card.high   { background:#EC4899; color:#FFFFFF; box-shadow:5px 5px 0 0 #831843; }
  .pick input:checked + .pick-card.todo   { background:#2563EB; color:#FFFFFF; box-shadow:5px 5px 0 0 #0A0A0A; }
  .pick input:checked + .pick-card.prog   { background:#F59E0B; box-shadow:5px 5px 0 0 #B45309; }
  .pick input:checked + .pick-card.done   { background:#84CC16; box-shadow:5px 5px 0 0 #4D7C0F; }

  .btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; border:3px solid #0A0A0A;
    border-radius:10px; padding:11px 18px; font-family:'Space Grotesk',sans-serif; font-weight:700;
    font-size:13px; letter-spacing:0.02em; text-transform:uppercase; box-shadow:6px 6px 0 0 #0A0A0A;
    cursor:pointer; text-decoration:none; }
  .btn.primary { background:#2563EB; color:#FFFFFF; }
  .btn.ghost   { background:#FFFFFF; color:#0A0A0A; }

  .btn-mini { display:inline-flex; align-items:center; justify-content:center; gap:6px; background:#FFFFFF;
    color:#0A0A0A; border:2.5px solid #0A0A0A; border-radius:8px; padding:8px 12px;
    font-family:'Space Grotesk',sans-serif; font-weight:700; font-size:11px; letter-spacing:0.06em;
    text-transform:uppercase; box-shadow:3px 3px 0 0 #0A0A0A; cursor:pointer; transition:all 160ms cubic-bezier(.2,.8,.2,1); }
  .btn-mini.danger { background:#FEE2E2; }
  .btn-mini.danger:hover { background:#EF4444; color:#FFFFFF; box-shadow:5px 5px 0 0 #EF4444; transform:translate(-2px,-2px); }

  .logout-btn { width:32px; height:32px; display:grid; place-items:center; border:2px solid #0A0A0A;
    border-radius:6px; background:#FFFFFF; cursor:pointer; transition:background-color 140ms ease, color 140ms ease; }
  .logout-btn:hover { background:#EF4444; color:#FFFFFF; }

  .nice-scroll::-webkit-scrollbar { width:8px; }
  .nice-scroll::-webkit-scrollbar-thumb { background:#0A0A0A; border-radius:8px; }

  .stagger > * { opacity:0; transform:translateY(12px); animation:rise 520ms cubic-bezier(.2,.8,.2,1) forwards; }
  .stagger > *:nth-child(1) { animation-delay:50ms; }
  .stagger > *:nth-child(2) { animation-delay:100ms; }
  .stagger > *:nth-child(3) { animation-delay:150ms; }
  @keyframes rise { to { opacity:1; transform:translateY(0); } }

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

      <div class="font-mono text-[10px] font-semibold tracking-[0.18em] text-ink/50 uppercase mt-7 mb-2 px-2">This project</div>
      <ul class="space-y-1.5">
        <li><a href="{{ route('projects.show', $project) }}" class="nav-item">
          <span class="w-2 h-2 rounded-full bg-lime-accent border-2 border-ink"></span>
          Tasks
        </a></li>
      </ul>
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
          <a href="{{ route('projects.index') }}" class="text-ink/60 hover:text-ink">Projects</a>
          <span class="text-ink/30">/</span>
          <a href="{{ route('projects.show', $project) }}" class="text-ink/60 hover:text-ink truncate max-w-[200px]">{{ $project->title }}</a>
          <span class="text-ink/30">/</span>
          <span class="text-ink">Edit task</span>
        </nav>
        <div class="ml-auto hidden md:flex items-center gap-2 bg-white border-[3px] border-ink rounded-[10px] py-1 px-2 shadow-brutal-sm">
          <div class="avatar" style="background:#06B6D4;color:#0A0A0A;">{{ $userInitials }}</div>
          <div class="leading-none mr-1">
            <div class="font-display font-bold text-[12px]">{{ Str::limit($user->name, 14, '') }}</div>
            <div class="font-mono text-[10px] text-ink/60">lead</div>
          </div>
        </div>
      </div>
    </header>

    {{-- CONTENT --}}
    <div class="main-pad relative px-8 py-10 stagger">
      <div class="max-w-[680px] mx-auto">

        @if (session('success'))
          <div class="mb-6 px-4 py-3 border-[3px] border-ink rounded-[10px] bg-lime-accent/40 font-mono text-[12px] font-semibold shadow-brutal-sm">
            {{ session('success') }}
          </div>
        @endif

        <div class="mb-6">
          <div class="font-mono text-[11px] font-semibold tracking-[0.18em] text-electric-blue uppercase mb-2">// editing task</div>
          <h1 class="font-display font-bold text-[36px] sm:text-[44px] leading-[1] tracking-tight">Modifier la tâche.</h1>
        </div>

        <div class="form-card">
          <form method="POST" action="{{ route('projects.tasks.update', [$project, $task]) }}">
            @csrf
            @method('PUT')

            {{-- bandeau d'en-tête --}}
            <div class="px-7 py-6 border-b-[3px] border-ink flex items-center gap-4" style="background:#EFF6FF;">
              <div class="w-14 h-14 grid place-items-center bg-electric-blue border-[3px] border-ink rounded-[12px] shadow-brutal-sm flex-shrink-0">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              </div>
              <div>
                <h2 class="font-display font-bold text-[20px] tracking-tight leading-tight">{{ $task->title }}</h2>
                <p class="text-[12px] text-ink/60 font-mono mt-0.5">#{{ $task->id }} · projet {{ $project->title }}</p>
              </div>
            </div>

            {{-- corps --}}
            <div class="px-7 py-6 space-y-6">
              <div>
                <label for="title" class="form-label">Titre <span class="text-red-accent">*</span></label>
                <input id="title" name="title" type="text" value="{{ old('title', $task->title) }}" maxlength="255"
                       autofocus required class="form-input @error('title') err @enderror" />
                @error('title')
                  <p class="form-err"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>{{ $message }}</p>
                @enderror
              </div>

              <div>
                <label for="description" class="form-label">Description <span class="text-red-accent">*</span></label>
                <textarea id="description" name="description" rows="4" required
                          class="form-input resize-none @error('description') err @enderror">{{ old('description', $task->description) }}</textarea>
                @error('description')
                  <p class="form-err"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>{{ $message }}</p>
                @enderror
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label for="deadline" class="form-label">Deadline <span class="text-red-accent">*</span></label>
                  <input id="deadline" name="deadline" type="datetime-local" required
                         value="{{ old('deadline', Carbon::parse($task->deadline)->format('Y-m-d\TH:i')) }}"
                         class="form-input font-mono @error('deadline') err @enderror" />
                  @error('deadline')
                    <p class="form-err"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>{{ $message }}</p>
                  @enderror
                </div>

                <div>
                  <label for="user_id" class="form-label">Assignée à <span class="text-red-accent">*</span></label>
                  <select id="user_id" name="user_id" required class="form-input @error('user_id') err @enderror">
                    @foreach ($members as $member)
                      <option value="{{ $member->id }}" @selected(old('user_id', $task->user_id) == $member->id)>
                        {{ $member->name }} ({{ $member->pivot->role === 'lead' ? 'Lead' : 'Dev' }})
                      </option>
                    @endforeach
                  </select>
                  @error('user_id')
                    <p class="form-err"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>{{ $message }}</p>
                  @enderror
                </div>
              </div>

              <div>
                <label class="form-label">Priorité <span class="text-red-accent">*</span></label>
                <div class="pick-grid">
                  <label class="pick">
                    <input type="radio" name="priority" value="low" @checked($curPrio === 'low')>
                    <span class="pick-card low">
                      <span class="font-display font-bold text-[16px] block">LOW</span>
                      <span class="font-mono text-[10px] text-ink/60">No rush</span>
                    </span>
                  </label>
                  <label class="pick">
                    <input type="radio" name="priority" value="medium" @checked($curPrio === 'medium')>
                    <span class="pick-card medium">
                      <span class="font-display font-bold text-[16px] block">MEDIUM</span>
                      <span class="font-mono text-[10px] text-ink/70">This sprint</span>
                    </span>
                  </label>
                  <label class="pick">
                    <input type="radio" name="priority" value="high" @checked($curPrio === 'high')>
                    <span class="pick-card high">
                      <span class="font-display font-bold text-[16px] block">⚡ HIGH</span>
                      <span class="font-mono text-[10px]">Drop everything</span>
                    </span>
                  </label>
                </div>
                @error('priority')
                  <p class="form-err"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>{{ $message }}</p>
                @enderror
              </div>

              <div>
                <label class="form-label">Statut <span class="text-red-accent">*</span></label>
                <div class="pick-grid">
                  <label class="pick">
                    <input type="radio" name="status" value="todo" @checked($curStatus === 'todo')>
                    <span class="pick-card todo">
                      <span class="font-display font-bold text-[14px] block">À FAIRE</span>
                    </span>
                  </label>
                  <label class="pick">
                    <input type="radio" name="status" value="in_progress" @checked($curStatus === 'in_progress')>
                    <span class="pick-card prog">
                      <span class="font-display font-bold text-[14px] block">EN COURS</span>
                    </span>
                  </label>
                  <label class="pick">
                    <input type="radio" name="status" value="done" @checked($curStatus === 'done')>
                    <span class="pick-card done">
                      <span class="font-display font-bold text-[14px] block">TERMINÉ</span>
                    </span>
                  </label>
                </div>
                @error('status')
                  <p class="form-err"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>{{ $message }}</p>
                @enderror
              </div>

              {{-- DANGER ZONE --}}
              @can('delete', $task)
                <div class="border-[3px] border-red-accent rounded-[10px] p-4" style="background:#FEF2F2;">
                  <div class="flex items-start justify-between gap-3 flex-wrap">
                    <div>
                      <div class="font-display font-bold text-[13px] text-red-accent uppercase tracking-wider mb-0.5">⚠ Danger zone</div>
                      <p class="text-[12px] text-ink/70">Supprimer cette tâche est définitif et irréversible.</p>
                    </div>
                    <button type="submit" form="form-delete-task" class="btn-mini danger">
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                      Supprimer
                    </button>
                  </div>
                </div>
              @endcan
            </div>

            {{-- pied --}}
            <div class="px-7 py-5 border-t-[3px] border-ink bg-cream flex items-center justify-end gap-3">
              <a href="{{ route('projects.show', $project) }}" class="btn ghost brutal-tap">Annuler</a>
              <button type="submit" class="btn primary brutal-tap">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                Enregistrer
              </button>
            </div>
          </form>

          @can('delete', $task)
            {{-- Formulaire séparé pour le bouton « Supprimer » (évite les formulaires imbriqués) --}}
            <form id="form-delete-task" method="POST" action="{{ route('projects.tasks.destroy', [$project, $task]) }}"
                  onsubmit="return confirm('Supprimer cette tâche ? Action irréversible.');">
              @csrf @method('DELETE')
            </form>
          @endcan
        </div>

      </div>
    </div>
  </main>
</div>

</body>
</html>
