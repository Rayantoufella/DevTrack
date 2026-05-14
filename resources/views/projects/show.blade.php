@php
    use Illuminate\Support\Str;
    use Illuminate\Support\Carbon;

    $user      = auth()->user();
    $isLead    = $role === 'lead';
    $deadline  = Carbon::parse($project->deadline);
    $isOverdue = $deadline->isPast() && $stats['pct'] < 100;

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
    $userInitials = $initials($user->name) ?: 'U';

    // Une tâche est urgente : non terminée + deadline dans les 48h à venir.
    $isUrgentTask = function ($task) {
        if ($task->status === 'done') return false;
        $diff = now()->diffInHours(Carbon::parse($task->deadline), false);
        return $diff >= 0 && $diff <= 48;
    };

    // Filtres lus dans l'URL (aucun JS) : ?status= ?q= ?sort=
    $statusFilter = in_array(request('status'), ['todo', 'in_progress', 'done', 'urgent']) ? request('status') : 'all';
    $search       = trim((string) request('q'));
    $sort         = in_array(request('sort'), ['priority', 'status', 'title']) ? request('sort') : 'deadline';

    $allTasks = $project->tasks;

    // Compteurs pour les onglets (sur la liste complète).
    $counts = [
        'all'         => $allTasks->count(),
        'todo'        => $allTasks->where('status', 'todo')->count(),
        'in_progress' => $allTasks->where('status', 'in_progress')->count(),
        'done'        => $allTasks->where('status', 'done')->count(),
        'urgent'      => $allTasks->filter($isUrgentTask)->count(),
    ];

    // Application des filtres.
    $tasks = $allTasks;
    if ($search !== '') {
        $tasks = $tasks->filter(fn ($t) => Str::contains(Str::lower($t->title), Str::lower($search)));
    }
    if ($statusFilter === 'urgent') {
        $tasks = $tasks->filter($isUrgentTask);
    } elseif ($statusFilter !== 'all') {
        $tasks = $tasks->where('status', $statusFilter);
    }

    // Tri.
    $priorityRank = ['high' => 0, 'medium' => 1, 'low' => 2];
    $statusRank   = ['todo' => 0, 'in_progress' => 1, 'done' => 2];
    $tasks = (match ($sort) {
        'priority' => $tasks->sortBy(fn ($t) => $priorityRank[$t->priority] ?? 9),
        'status'   => $tasks->sortBy(fn ($t) => $statusRank[$t->status] ?? 9),
        'title'    => $tasks->sortBy(fn ($t) => Str::lower($t->title)),
        default    => $tasks->sortBy('deadline'),
    })->values();

    // Métadonnées d'affichage par statut / priorité.
    $statusClass = ['todo' => 'status-todo', 'in_progress' => 'status-prog', 'done' => 'status-done'];
    $statusText  = ['todo' => 'TODO', 'in_progress' => 'IN PROGRESS', 'done' => 'DONE'];
    $nextStatus  = ['todo' => 'in_progress', 'in_progress' => 'done'];
    $priClass    = ['low' => 'pri-low', 'medium' => 'pri-medium', 'high' => 'pri-high'];
    $priText     = ['low' => 'LOW', 'medium' => 'MEDIUM', 'high' => '⚡ HIGH'];

    // Conserve les autres paramètres d'URL en changeant un onglet / un tri.
    $tabUrl = fn ($params) => route('projects.show', $project) . '?' . http_build_query(array_merge(
        ['q' => $search ?: null, 'sort' => $sort !== 'deadline' ? $sort : null, 'status' => $statusFilter !== 'all' ? $statusFilter : null],
        $params,
    ));

    // Ouverture automatique d'une modal quand sa validation a échoué (aucun JS).
    $openNewTask    = $errors->has('priority') || $errors->has('user_id')
        || (($errors->has('title') || $errors->has('description') || $errors->has('deadline')) && ! $errors->has('email'));
    $openAddMember  = $errors->has('email');
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

  .dot-grid { background-image: radial-gradient(circle, rgba(10,10,10,.10) 1.2px, transparent 1.2px); background-size: 22px 22px; }

  /* SIDEBAR */
  .nav-item { display:flex; align-items:center; gap:12px; padding:11px 14px; border-radius:10px; border:3px solid transparent;
    font-family:'Space Grotesk',sans-serif; font-weight:600; font-size:14px; color:#0A0A0A; cursor:pointer;
    transition:all 160ms ease; text-decoration:none; }
  .nav-item:hover { background:#FFFFFF; border-color:#0A0A0A; box-shadow:4px 4px 0 0 #0A0A0A; transform:translate(-1px,-1px); }
  .nav-item.active { background:#2563EB; color:#FFFFFF; border-color:#0A0A0A; box-shadow:4px 4px 0 0 #0A0A0A; }
  .nav-item .badge { margin-left:auto; font-family:'JetBrains Mono',monospace; font-size:11px; font-weight:700;
    background:#FFFBEB; color:#0A0A0A; border:2px solid #0A0A0A; border-radius:6px; padding:1px 6px; }
  .nav-item.active .badge { background:#FFFFFF; }

  /* AVATAR */
  .avatar { width:32px; height:32px; border-radius:9999px; border:2.5px solid #0A0A0A;
    display:grid; place-items:center; font-family:'Space Grotesk',sans-serif; font-weight:700; font-size:11px; }
  .avatar.lg { width:40px; height:40px; font-size:13px; }
  .avatar.sm { width:26px; height:26px; font-size:10px; border-width:2px; }

  /* PROJECT HERO CARD */
  .proj-hero { background:#FFFFFF; border:3px solid #0A0A0A; border-radius:14px;
    box-shadow:8px 8px 0 0 #0A0A0A; padding:28px; position:relative; overflow:hidden; }
  .proj-hero::before { content:''; position:absolute; top:-40px; right:-40px; width:180px; height:180px;
    background:#EFF6FF; border:3px solid #0A0A0A; border-radius:9999px; z-index:0; }

  /* META CHIP */
  .meta-chip { display:inline-flex; align-items:center; gap:6px; background:#FFFBEB; color:#0A0A0A;
    border:2px solid #0A0A0A; border-radius:9999px; padding:3px 10px; font-family:'JetBrains Mono',monospace;
    font-weight:600; font-size:11px; box-shadow:2px 2px 0 0 #0A0A0A; }
  .meta-chip.urgent { background:#EF4444; color:#FFFFFF; }
  .meta-chip.lime   { background:#84CC16; }
  .meta-chip.cyan   { background:#06B6D4; color:#FFFFFF; }
  .meta-chip.deep   { background:#1E3A8A; color:#FFFFFF; }

  /* MINI PROGRESS */
  .mini-prog { height:14px; background:#FFFBEB; border:3px solid #0A0A0A; border-radius:6px; overflow:hidden; }
  .mini-prog .fill { height:100%; border-right:3px solid #0A0A0A;
    background-image:repeating-linear-gradient(45deg,#2563EB,#2563EB 8px,#06B6D4 8px,#06B6D4 16px); }

  /* TABLE */
  .task-table { background:#FFFFFF; border:3px solid #0A0A0A; border-radius:12px;
    box-shadow:6px 6px 0 0 #0A0A0A; overflow:hidden; }
  .task-table thead th { background:#FFFBEB; border-bottom:3px solid #0A0A0A;
    font-family:'Space Grotesk',sans-serif; font-weight:700; font-size:11px; letter-spacing:0.12em;
    text-transform:uppercase; text-align:left; padding:14px 16px; color:#0A0A0A;
    position:sticky; top:0; z-index:5; }
  .task-table thead th a { color:inherit; text-decoration:none; display:inline-flex; align-items:center; gap:6px; }
  .task-table thead th a:hover { color:#2563EB; }
  .task-table thead th.sorted a { color:#2563EB; }
  .task-table tbody tr { border-bottom:2px solid #0A0A0A; transition:background 140ms ease, box-shadow 140ms ease; }
  .task-table tbody tr:nth-child(even) { background:#FFFBEB; }
  .task-table tbody tr:nth-child(odd)  { background:#FFFFFF; }
  .task-table tbody tr:last-child { border-bottom:none; }
  .task-table tbody tr:hover { background:#EFF6FF; box-shadow:inset 4px 0 0 0 #2563EB; }
  .task-table tbody tr.row-urgent { box-shadow:inset 4px 0 0 0 #EF4444; }
  .task-table tbody tr.row-urgent:hover { background:#FEE2E2; box-shadow:inset 4px 0 0 0 #EF4444; }
  .task-table tbody td { padding:14px 16px; vertical-align:middle; font-size:14px; }

  /* STATUS BADGE */
  .status-badge { display:inline-flex; align-items:center; gap:6px; font-family:'JetBrains Mono',monospace;
    font-weight:700; font-size:11px; padding:4px 10px; border:2px solid #0A0A0A; border-radius:9999px;
    box-shadow:2px 2px 0 0 #0A0A0A; transition:all 140ms ease; text-transform:uppercase; letter-spacing:0.04em; }
  button.status-badge { cursor:pointer; }
  button.status-badge:hover { transform:translate(-1px,-1px); box-shadow:3px 3px 0 0 #0A0A0A; }
  .status-todo { background:#FFFFFF; color:#0A0A0A; }
  .status-prog { background:#F59E0B; color:#0A0A0A; }
  .status-done { background:#84CC16; color:#0A0A0A; }

  /* PRIORITY BADGE */
  .pri-badge { display:inline-flex; align-items:center; gap:4px; font-family:'JetBrains Mono',monospace;
    font-weight:700; font-size:10px; padding:3px 8px; border:2px solid #0A0A0A; border-radius:6px;
    text-transform:uppercase; letter-spacing:0.06em; }
  .pri-low    { background:#CFFAFE; color:#1E3A8A; }
  .pri-medium { background:#F59E0B; color:#0A0A0A; }
  .pri-high   { background:#EC4899; color:#FFFFFF; }

  /* FILTER TABS */
  .filter-tab { display:inline-flex; align-items:center; gap:8px; background:#FFFFFF; border:3px solid #0A0A0A;
    border-radius:10px; padding:9px 14px; font-family:'Space Grotesk',sans-serif; font-weight:700;
    font-size:12px; text-transform:uppercase; letter-spacing:0.04em; box-shadow:4px 4px 0 0 #0A0A0A;
    cursor:pointer; text-decoration:none; transition:all 180ms cubic-bezier(.2,.8,.2,1); }
  .filter-tab:hover { transform:translate(-2px,-2px); box-shadow:6px 6px 0 0 #0A0A0A; }
  .filter-tab.active { background:#2563EB; color:#FFFFFF; }
  .filter-tab .count { font-family:'JetBrains Mono',monospace; background:#FFFBEB; color:#0A0A0A;
    border:2px solid #0A0A0A; border-radius:6px; padding:0 6px; font-size:11px; }
  .filter-tab.active .count { background:#FFFFFF; }
  .filter-tab.urgent { color:#EF4444; }
  .filter-tab.urgent .count { background:#EF4444; color:#FFFFFF; }
  .filter-tab.urgent.active { background:#EF4444; color:#FFFFFF; }

  /* SEARCH */
  .search-input { width:100%; background:#FFFFFF; border:3px solid #0A0A0A; border-radius:10px;
    padding:9px 14px 9px 40px; font-family:'Inter',sans-serif; font-weight:500; font-size:14px;
    box-shadow:4px 4px 0 0 #0A0A0A; transition:all 160ms ease; }
  .search-input:focus { outline:none; border-color:#2563EB; box-shadow:4px 4px 0 0 #2563EB; transform:translate(-2px,-2px); }

  /* SORT DROPDOWN (pur HTML : <details>) */
  .sort-menu { position:relative; }
  .sort-menu > summary { list-style:none; }
  .sort-menu > summary::-webkit-details-marker { display:none; }
  .sort-menu .menu { position:absolute; right:0; margin-top:8px; width:190px; background:#FFFFFF;
    border:3px solid #0A0A0A; border-radius:10px; box-shadow:6px 6px 0 0 #0A0A0A; padding:8px 0; z-index:20; }
  .sort-menu .menu a { display:block; padding:8px 16px; font-family:'Space Grotesk',sans-serif;
    font-weight:600; font-size:14px; color:#0A0A0A; text-decoration:none; }
  .sort-menu .menu a:hover { background:#EFF6FF; color:#2563EB; }
  .sort-menu .menu a.on { color:#2563EB; }

  /* FIELDS */
  .field, .form-input { width:100%; background:#FFFFFF; border:3px solid #0A0A0A; border-radius:10px;
    padding:10px 14px; font-family:'Inter',sans-serif; font-weight:500; font-size:14px;
    transition:box-shadow 160ms ease, transform 160ms ease, border-color 160ms ease; }
  .field:focus, .form-input:focus { outline:none; border-color:#2563EB; box-shadow:4px 4px 0 0 #2563EB; transform:translate(-2px,-2px); }
  .form-label { display:block; font-family:'Space Grotesk',sans-serif; font-weight:700; font-size:11px;
    text-transform:uppercase; letter-spacing:0.14em; color:#0A0A0A; margin-bottom:8px; }
  .form-err { margin-top:6px; font-family:'JetBrains Mono',monospace; font-weight:600; font-size:11px; color:#EF4444; }

  /* BUTTONS */
  .btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; border:3px solid #0A0A0A;
    border-radius:10px; padding:11px 18px; font-family:'Space Grotesk',sans-serif; font-weight:700;
    font-size:13px; letter-spacing:0.02em; text-transform:uppercase; box-shadow:6px 6px 0 0 #0A0A0A;
    cursor:pointer; text-decoration:none; }
  .btn.primary { background:#2563EB; color:#FFFFFF; }
  .btn.ghost   { background:#FFFFFF; color:#0A0A0A; }
  .btn.danger  { background:#EF4444; color:#FFFFFF; }
  .btn.success { background:#84CC16; color:#0A0A0A; }
  .btn.sm      { padding:8px 14px; font-size:12px; box-shadow:4px 4px 0 0 #0A0A0A; }

  .btn-mini { display:inline-flex; align-items:center; justify-content:center; gap:6px; background:#FFFFFF;
    color:#0A0A0A; border:2.5px solid #0A0A0A; border-radius:8px; padding:8px 12px;
    font-family:'Space Grotesk',sans-serif; font-weight:700; font-size:11px; letter-spacing:0.06em;
    text-transform:uppercase; box-shadow:3px 3px 0 0 #0A0A0A; cursor:pointer; text-decoration:none;
    transition:all 160ms cubic-bezier(.2,.8,.2,1); }
  .btn-mini:hover { transform:translate(-2px,-2px); box-shadow:5px 5px 0 0 #0A0A0A; }
  .btn-mini.danger { background:#FEE2E2; }
  .btn-mini.danger:hover { background:#EF4444; color:#FFFFFF; box-shadow:5px 5px 0 0 #EF4444; }

  /* ICON BUTTON */
  .icon-btn { width:36px; height:36px; background:#FFFFFF; border:2.5px solid #0A0A0A; border-radius:8px;
    display:grid; place-items:center; box-shadow:3px 3px 0 0 #0A0A0A; cursor:pointer; text-decoration:none;
    transition:all 160ms cubic-bezier(.2,.8,.2,1); }
  .icon-btn:hover { transform:translate(-2px,-2px); box-shadow:5px 5px 0 0 #0A0A0A; }
  .icon-btn.danger:hover { box-shadow:5px 5px 0 0 #EF4444; background:#EF4444; }
  .icon-btn.danger:hover svg { stroke:#FFFFFF; }

  .card { background:#FFFFFF; border:3px solid #0A0A0A; border-radius:12px; box-shadow:6px 6px 0 0 #0A0A0A; }

  .lead-stamp { display:inline-flex; align-items:center; gap:4px; background:#0A0A0A; color:#FFFFFF;
    border-radius:6px; padding:2px 8px; font-family:'JetBrains Mono',monospace; font-weight:700;
    font-size:10px; letter-spacing:0.12em; }
  .lead-stamp.dev { background:#FFFFFF; color:#0A0A0A; border:2px solid #0A0A0A; }

  /* ═══ MODALS (CSS pur : pseudo-classe :target, aucun JS) ═══ */
  .modal-overlay { position:fixed; inset:0; background:rgba(10,10,10,0.55); display:none;
    place-items:center; z-index:50; padding:24px; }
  .modal-overlay:target, .modal-overlay.is-open { display:grid; }
  .modal-backdrop { position:absolute; inset:0; cursor:default; }
  .modal-card { position:relative; background:#FFFFFF; border:4px solid #0A0A0A; border-radius:14px;
    box-shadow:12px 12px 0 0 #0A0A0A; width:100%; max-width:600px; max-height:calc(100vh - 48px);
    overflow:auto; animation:modal-pop 280ms cubic-bezier(.2,.8,.2,1); }
  .modal-card.lg { max-width:720px; }
  .modal-card.danger { border-color:#EF4444; box-shadow:12px 12px 0 0 #EF4444; }
  @keyframes modal-pop { from { transform:translate(8px,8px) scale(.96); opacity:0; } to { transform:translate(0,0) scale(1); opacity:1; } }
  .modal-x { width:40px; height:40px; display:grid; place-items:center; background:#FFFFFF;
    border:3px solid #0A0A0A; border-radius:10px; box-shadow:4px 4px 0 0 #0A0A0A; cursor:pointer;
    flex-shrink:0; transition:all 140ms ease; }
  .modal-x:hover { background:#EF4444; color:#FFFFFF; }

  /* PRIORITY CARDS (radio + label, CSS pur) */
  .pri-pick { display:block; position:relative; cursor:pointer; }
  .pri-pick input { position:absolute; inset:0; opacity:0; pointer-events:none; }
  .pri-card { display:block; padding:14px; background:#FFFFFF; border:3px solid #0A0A0A; border-radius:10px;
    box-shadow:4px 4px 0 0 #0A0A0A; text-align:center; transition:all 200ms cubic-bezier(.2,.8,.2,1); }
  .pri-pick:hover .pri-card { transform:translate(-2px,-2px); box-shadow:6px 6px 0 0 #0A0A0A; }
  .pri-pick input:checked + .pri-card.low    { background:#CFFAFE; box-shadow:6px 6px 0 0 #06B6D4; transform:translate(-2px,-2px); }
  .pri-pick input:checked + .pri-card.medium { background:#F59E0B; box-shadow:6px 6px 0 0 #B45309; transform:translate(-2px,-2px); }
  .pri-pick input:checked + .pri-card.high   { background:#EC4899; color:#FFFFFF; box-shadow:6px 6px 0 0 #831843; transform:translate(-2px,-2px); }

  .stagger > * { opacity:0; transform:translateY(12px); animation:rise 520ms cubic-bezier(.2,.8,.2,1) forwards; }
  .stagger > *:nth-child(1) { animation-delay:50ms; }
  .stagger > *:nth-child(2) { animation-delay:100ms; }
  .stagger > *:nth-child(3) { animation-delay:150ms; }
  .stagger > *:nth-child(4) { animation-delay:200ms; }
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

      <div class="font-mono text-[10px] font-semibold tracking-[0.18em] text-ink/50 uppercase mt-7 mb-2 px-2">This project</div>
      <ul class="space-y-1.5">
        <li><a href="{{ route('projects.show', $project) }}" class="nav-item active">
          <span class="w-2 h-2 rounded-full bg-lime-accent border-2 border-ink"></span>
          Tasks <span class="badge">{{ $stats['total'] }}</span>
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
          <a href="{{ route('projects.index') }}" class="text-ink/60 hover:text-ink">Projects</a>
          <span class="text-ink/30">/</span>
          <span class="text-ink truncate max-w-[280px]">{{ $project->title }}</span>
        </nav>

        <div class="ml-auto flex items-center gap-3">
          @can('create', App\Models\Project::class)
            <a href="#m-new-project" class="btn primary sm brutal-tap">
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

      {{-- ═══════ PROJECT HERO ═══════ --}}
      <section class="proj-hero mb-8">
        <div class="relative z-10 grid grid-cols-1 lg:grid-cols-3 gap-8">

          <div class="lg:col-span-2">
            <div class="flex flex-wrap items-center gap-2 mb-3">
              @if ($isOverdue)
                <span class="meta-chip urgent">⚠ OVERDUE</span>
              @elseif ($stats['urgent'] > 0)
                <span class="meta-chip urgent">{{ $stats['urgent'] }} URGENT</span>
              @elseif ($stats['pct'] >= 90)
                <span class="meta-chip lime">SHIP-READY</span>
              @elseif ($stats['total'] === 0)
                <span class="meta-chip">FRESH</span>
              @else
                <span class="meta-chip cyan">IN PROGRESS</span>
              @endif

              @if ($isLead)
                <span class="meta-chip deep">★ LEAD</span>
              @else
                <span class="meta-chip">DEV</span>
              @endif
              <span class="meta-chip">PROJ-{{ str_pad($project->id, 2, '0', STR_PAD_LEFT) }}</span>
            </div>

            <h1 class="font-display font-bold text-[40px] sm:text-[52px] leading-[0.98] tracking-tight mb-4">
              {{ $project->title }}
            </h1>

            <p class="text-ink/70 text-[15px] leading-relaxed max-w-[640px] mb-6 whitespace-pre-line">
              {{ $project->description }}
            </p>

            <div class="grid grid-cols-3 gap-3 max-w-[480px]">
              <div class="bg-cream border-[2.5px] border-ink rounded-[10px] p-3 shadow-brutal-sm">
                <div class="font-mono text-[10px] font-bold tracking-widest text-ink/60 uppercase">Total</div>
                <div class="font-display font-bold text-[22px] leading-none mt-1">{{ $stats['total'] }}</div>
              </div>
              <div class="bg-lime-accent border-[2.5px] border-ink rounded-[10px] p-3 shadow-brutal-sm">
                <div class="font-mono text-[10px] font-bold tracking-widest text-ink/80 uppercase">Done</div>
                <div class="font-display font-bold text-[22px] leading-none mt-1">{{ $stats['done'] }}</div>
              </div>
              <div class="bg-red-accent text-white border-[2.5px] border-ink rounded-[10px] p-3 shadow-brutal-sm">
                <div class="font-mono text-[10px] font-bold tracking-widest text-white/80 uppercase">Urgent</div>
                <div class="font-display font-bold text-[22px] leading-none mt-1">{{ $stats['urgent'] }}</div>
              </div>
            </div>
          </div>

          <div class="space-y-5">
            <div>
              <div class="font-mono text-[10px] font-bold tracking-widest text-ink/60 uppercase mb-2">Project deadline</div>
              <div class="flex items-center gap-3">
                <div class="w-12 h-12 grid place-items-center bg-amber-accent border-[3px] border-ink rounded-[10px] shadow-brutal-sm">
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0A0A0A" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                </div>
                <div>
                  <div class="font-display font-bold text-[20px] leading-tight">{{ $deadline->translatedFormat('M j, Y') }}</div>
                  <div class="font-mono text-[11px] mt-0.5 {{ $isOverdue ? 'text-red-accent font-bold' : 'text-ink/60' }}">
                    {{ $isOverdue ? 'Overdue' : $deadline->diffForHumans(null, true) . ' remaining' }}
                  </div>
                </div>
              </div>
            </div>

            <div>
              <div class="flex items-center justify-between mb-2">
                <div class="font-mono text-[10px] font-bold tracking-widest text-ink/60 uppercase">Crew ({{ $project->users->count() }})</div>
                @can('manageMembers', $project)
                  <a href="#m-add-member" class="font-mono text-[10px] font-bold tracking-widest text-electric-blue uppercase hover:underline">+ Add</a>
                @endcan
              </div>
              <div class="flex -space-x-2">
                @foreach ($project->users->take(6) as $m)
                  @php $c = $avatarColor($m->id); @endphp
                  <div class="avatar lg" style="background:{{ $c['bg'] }};color:{{ $c['fg'] }};" title="{{ $m->name }}{{ $m->pivot->role === 'lead' ? ' (Lead)' : '' }}">{{ $initials($m->name) }}</div>
                @endforeach
                @if ($project->users->count() > 6)
                  <div class="avatar lg" style="background:#FFFFFF;color:#0A0A0A;">+{{ $project->users->count() - 6 }}</div>
                @endif
              </div>
            </div>

            <div>
              <div class="flex items-center justify-between mb-2">
                <div class="font-mono text-[10px] font-bold tracking-widest text-ink/60 uppercase">Progress</div>
                <div class="font-mono text-[12px] font-bold {{ $isOverdue ? 'text-red-accent' : '' }}">{{ $stats['pct'] }}%</div>
              </div>
              <div class="mini-prog"><div class="fill" style="width: {{ max($stats['pct'], 2) }}%"></div></div>
            </div>

            <div class="flex items-center gap-2 pt-2">
              @can('update', $project)
                <a href="#m-edit-project" class="btn ghost sm brutal-tap flex-1">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  Edit
                </a>
              @endcan
              @can('delete', $project)
                <form method="POST" action="{{ route('projects.destroy', $project) }}" onsubmit="return confirm('Archiver ce projet ?');">
                  @csrf @method('DELETE')
                  <button class="icon-btn danger" title="Archiver" style="width:42px;height:42px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0A0A0A" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8v13H3V8"/><path d="M1 3h22v5H1z"/><path d="M10 12h4"/></svg>
                  </button>
                </form>
              @endcan
            </div>
          </div>

        </div>
      </section>

      {{-- ═══════ TASKS SECTION ═══════ --}}
      <section>
        <div class="flex items-end justify-between mb-5">
          <div>
            <div class="font-mono text-[11px] font-semibold tracking-[0.18em] text-ink/60 uppercase mb-1">// section</div>
            <h2 class="font-display font-bold text-[28px] tracking-tight">Tasks</h2>
          </div>
          @can('createTask', $project)
            <a href="#m-new-task" class="btn primary brutal-tap">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
              New Task
            </a>
          @endcan
        </div>

        {{-- FILTER BAR : onglets (liens) + recherche (form GET) + tri (<details>) --}}
        <div class="flex flex-col lg:flex-row lg:items-center gap-3 mb-5">
          <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ $tabUrl(['status' => null]) }}"        class="filter-tab {{ $statusFilter === 'all' ? 'active' : '' }}">All <span class="count">{{ $counts['all'] }}</span></a>
            <a href="{{ $tabUrl(['status' => 'todo']) }}"        class="filter-tab {{ $statusFilter === 'todo' ? 'active' : '' }}">Todo <span class="count">{{ $counts['todo'] }}</span></a>
            <a href="{{ $tabUrl(['status' => 'in_progress']) }}" class="filter-tab {{ $statusFilter === 'in_progress' ? 'active' : '' }}">In progress <span class="count">{{ $counts['in_progress'] }}</span></a>
            <a href="{{ $tabUrl(['status' => 'done']) }}"        class="filter-tab {{ $statusFilter === 'done' ? 'active' : '' }}">Done <span class="count">{{ $counts['done'] }}</span></a>
            <a href="{{ $tabUrl(['status' => 'urgent']) }}"      class="filter-tab urgent {{ $statusFilter === 'urgent' ? 'active' : '' }}">⚡ Urgent <span class="count">{{ $counts['urgent'] }}</span></a>
          </div>

          <div class="flex items-center gap-3 lg:ml-auto">
            <form method="GET" action="{{ route('projects.show', $project) }}" class="flex items-center gap-3">
              @if ($statusFilter !== 'all')<input type="hidden" name="status" value="{{ $statusFilter }}">@endif
              @if ($sort !== 'deadline')<input type="hidden" name="sort" value="{{ $sort }}">@endif
              <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="#0A0A0A" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="text" name="q" value="{{ $search }}" placeholder="Search tasks…" class="search-input w-full sm:w-[200px]" />
              </div>
              <button type="submit" class="filter-tab">Go</button>
            </form>

            {{-- Tri : dropdown HTML natif (<details>), aucun JS --}}
            <details class="sort-menu">
              <summary class="filter-tab">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="3" x2="21" y1="6" y2="6"/><line x1="6" x2="18" y1="12" y2="12"/><line x1="9" x2="15" y1="18" y2="18"/></svg>
                Sort: <span class="font-mono">{{ ucfirst($sort) }}</span>
              </summary>
              <div class="menu">
                <a href="{{ $tabUrl(['sort' => null]) }}"        class="{{ $sort === 'deadline' ? 'on' : '' }}">Deadline</a>
                <a href="{{ $tabUrl(['sort' => 'priority']) }}"  class="{{ $sort === 'priority' ? 'on' : '' }}">Priority</a>
                <a href="{{ $tabUrl(['sort' => 'status']) }}"    class="{{ $sort === 'status' ? 'on' : '' }}">Status</a>
                <a href="{{ $tabUrl(['sort' => 'title']) }}"     class="{{ $sort === 'title' ? 'on' : '' }}">Title</a>
              </div>
            </details>

            @if ($search !== '')
              <a href="{{ $tabUrl(['q' => null]) }}" class="filter-tab" title="Effacer la recherche">✕</a>
            @endif
          </div>
        </div>

        {{-- ═══════ TASK TABLE ═══════ --}}
        <div class="task-table">
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead>
                <tr>
                  <th style="width:48px;"></th>
                  <th class="{{ $sort === 'title' ? 'sorted' : '' }}">
                    <a href="{{ $tabUrl(['sort' => 'title']) }}">Task {!! $sort === 'title' ? '<span>▾</span>' : '<span style="opacity:.3">↕</span>' !!}</a>
                  </th>
                  <th>Assignee</th>
                  <th class="{{ $sort === 'priority' ? 'sorted' : '' }}">
                    <a href="{{ $tabUrl(['sort' => 'priority']) }}">Priority {!! $sort === 'priority' ? '<span>▾</span>' : '<span style="opacity:.3">↕</span>' !!}</a>
                  </th>
                  <th class="{{ $sort === 'deadline' ? 'sorted' : '' }}">
                    <a href="{{ $tabUrl(['sort' => null]) }}">Deadline {!! $sort === 'deadline' ? '<span>▾</span>' : '<span style="opacity:.3">↕</span>' !!}</a>
                  </th>
                  <th class="{{ $sort === 'status' ? 'sorted' : '' }}">
                    <a href="{{ $tabUrl(['sort' => 'status']) }}">Status {!! $sort === 'status' ? '<span>▾</span>' : '<span style="opacity:.3">↕</span>' !!}</a>
                  </th>
                  <th style="width:110px; text-align:right;">Actions</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($tasks as $task)
                  @php
                    $td     = Carbon::parse($task->deadline);
                    $diff   = now()->diffInHours($td, false);
                    $urgent = $isUrgentTask($task);
                    $late   = $task->status !== 'done' && $diff < 0;
                    $mine   = $task->user_id === $user->id;
                  @endphp
                  <tr class="{{ $urgent || $late ? 'row-urgent' : '' }}">
                    <td>
                      <span class="grid place-items-center w-[22px] h-[22px] border-[3px] border-ink rounded-[5px] {{ $task->status === 'done' ? 'bg-lime-accent' : 'bg-white' }}" style="box-shadow:2px 2px 0 0 #0A0A0A;">
                        @if ($task->status === 'done')
                          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#0A0A0A" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        @endif
                      </span>
                    </td>

                    <td>
                      <a href="#m-task-{{ $task->id }}" class="flex items-center gap-2 group">
                        @if ($mine)<span class="text-amber-500" title="Assignée à moi">★</span>@endif
                        <span class="font-display font-semibold text-[15px] group-hover:underline decoration-2 underline-offset-4 {{ $task->status === 'done' ? 'line-through opacity-60' : '' }}">{{ $task->title }}</span>
                      </a>
                      <div class="flex items-center gap-3 mt-1 font-mono text-[11px] text-ink/60">
                        <span>#{{ $task->id }}</span>
                        @if ($late)<span>·</span><span class="text-red-accent font-bold">EN RETARD</span>@endif
                      </div>
                    </td>

                    <td>
                      @if ($task->user)
                        @php $c = $avatarColor($task->user->id); @endphp
                        <div class="flex items-center gap-2">
                          <div class="avatar sm" style="background:{{ $c['bg'] }};color:{{ $c['fg'] }};">{{ $initials($task->user->name) }}</div>
                          <div class="font-display font-semibold text-[13px]">{{ $task->user->name }}</div>
                        </div>
                      @else
                        <span class="font-mono text-[12px] text-ink/40 italic">Non assignée</span>
                      @endif
                    </td>

                    <td><span class="pri-badge {{ $priClass[$task->priority] ?? 'pri-medium' }}">{{ $priText[$task->priority] ?? Str::upper($task->priority) }}</span></td>

                    <td>
                      <div class="font-mono text-[12px] font-bold {{ $urgent || $late ? 'text-red-accent' : ($task->status === 'done' ? 'text-ink/50 line-through' : '') }}">
                        {{ $td->translatedFormat('M j') }}
                      </div>
                      <div class="font-mono text-[10px] text-ink/50">{{ $td->format('H:i') }} · {{ $td->diffForHumans() }}</div>
                    </td>

                    <td>
                      @if ($task->status !== 'done' && auth()->user()->can('updateStatus', $task))
                        <form method="POST" action="{{ route('projects.tasks.updateStatus', [$project, $task]) }}">
                          @csrf @method('PATCH')
                          <input type="hidden" name="status" value="{{ $nextStatus[$task->status] }}">
                          <button type="submit" class="status-badge {{ $statusClass[$task->status] }}" title="Passer à : {{ $statusText[$nextStatus[$task->status]] }}">
                            <span class="w-1.5 h-1.5 {{ $task->status === 'todo' ? 'border-2 border-ink' : 'bg-ink' }} rounded-full"></span>
                            {{ $statusText[$task->status] }}
                          </button>
                        </form>
                      @else
                        <span class="status-badge {{ $statusClass[$task->status] }}">
                          @if ($task->status === 'done')
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#0A0A0A" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                          @else
                            <span class="w-1.5 h-1.5 {{ $task->status === 'todo' ? 'border-2 border-ink' : 'bg-ink' }} rounded-full"></span>
                          @endif
                          {{ $statusText[$task->status] }}
                        </span>
                      @endif
                    </td>

                    <td class="text-right">
                      <div class="flex items-center justify-end gap-1.5">
                        @can('update', $task)
                          <a href="{{ route('projects.tasks.edit', [$project, $task]) }}" class="icon-btn" title="Éditer">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0A0A0A" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                          </a>
                        @endcan
                        @can('delete', $task)
                          <form method="POST" action="{{ route('projects.tasks.destroy', [$project, $task]) }}" onsubmit="return confirm('Supprimer cette tâche ?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="icon-btn danger" title="Supprimer">
                              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0A0A0A" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            </button>
                          </form>
                        @endcan
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="7" class="text-center" style="padding:48px 24px;">
                      <div class="font-display font-bold text-[20px] mb-2">
                        {{ $counts['all'] === 0 ? 'Aucune tâche' : 'Aucune tâche pour ce filtre' }}
                      </div>
                      <p class="text-[14px] text-ink/60 mb-5">
                        {{ $counts['all'] === 0 ? 'Créez la première tâche pour lancer le projet.' : 'Essayez un autre filtre ou une autre recherche.' }}
                      </p>
                      @if ($counts['all'] === 0)
                        @can('createTask', $project)
                          <a href="#m-new-task" class="btn primary brutal-tap" style="display:inline-flex;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                            Nouvelle tâche
                          </a>
                        @endcan
                      @else
                        <a href="{{ route('projects.show', $project) }}" class="btn ghost brutal-tap" style="display:inline-flex;">Réinitialiser les filtres</a>
                      @endif
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <div class="border-t-[3px] border-ink bg-cream px-4 py-3 flex items-center justify-between">
            <div class="font-mono text-[11px] font-semibold text-ink/60">
              {{ $tasks->count() }} {{ $tasks->count() === 1 ? 'tâche affichée' : 'tâches affichées' }} · {{ $counts['all'] }} au total
            </div>
            <div class="font-mono text-[11px] text-ink/60">Tri : <span class="text-ink font-bold uppercase">{{ $sort }}</span></div>
          </div>
        </div>
      </section>

      {{-- ═══════ CREW MANAGEMENT ═══════ --}}
      <section id="crew" class="mt-8 card p-6">
        <div class="flex items-center justify-between mb-4">
          <div>
            <div class="font-mono text-[11px] font-semibold tracking-[0.18em] text-ink/60 uppercase mb-1">// crew</div>
            <h2 class="font-display font-bold text-[22px] tracking-tight">Membres du projet</h2>
          </div>
          @can('manageMembers', $project)
            <a href="#m-add-member" class="btn-mini">+ Ajouter</a>
          @endcan
        </div>

        <ul class="grid grid-cols-1 md:grid-cols-2 gap-2.5">
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
                  <form method="POST" action="{{ route('projects.members.destroy', [$project, $member]) }}" onsubmit="return confirm('Retirer ce membre ?');">
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
      </section>

      <div class="mt-10 flex flex-col md:flex-row items-center justify-between gap-3 font-mono text-[11px] text-ink/50">
        <div>&copy; {{ date('Y') }} DevTrack — Made in Agadir 🇲🇦</div>
        <a href="{{ route('dashboard') }}" class="hover:text-ink">← Retour au dashboard</a>
      </div>

    </div>
  </main>
</div>


{{-- ════════════════════════════════════════════════════════
     MODAL : NEW TASK  →  POST projects.tasks.store
     ════════════════════════════════════════════════════════ --}}
@can('createTask', $project)
<div id="m-new-task" class="modal-overlay {{ $openNewTask ? 'is-open' : '' }}">
  <a href="#" class="modal-backdrop" aria-label="Fermer"></a>
  <div class="modal-card lg">
    <form method="POST" action="{{ route('projects.tasks.store', $project) }}">
      @csrf
      <div class="px-7 py-6 border-b-[3px] border-ink flex items-center justify-between">
        <div>
          <div class="font-mono text-[10px] font-bold tracking-widest text-electric-blue uppercase mb-1">// new task</div>
          <h3 class="font-display font-bold text-[26px] tracking-tight">Create a new task</h3>
        </div>
        <a href="#" class="modal-x" aria-label="Fermer">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" x2="6" y1="6" y2="18"/><line x1="6" x2="18" y1="6" y2="18"/></svg>
        </a>
      </div>

      <div class="px-7 py-6 space-y-5">
        <div>
          <label class="form-label">Task title</label>
          <input type="text" name="title" value="{{ old('title') }}" placeholder="e.g. Implement push notifications" class="form-input" required />
          @error('title')<p class="form-err">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="form-label">Description</label>
          <textarea name="description" rows="3" placeholder="What needs to happen? Context, acceptance criteria, links…" class="form-input resize-none" required>{{ old('description') }}</textarea>
          @error('description')<p class="form-err">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div>
            <label class="form-label">Deadline</label>
            <input type="date" name="deadline" value="{{ old('deadline') }}" min="{{ now()->addDay()->toDateString() }}" class="form-input font-mono" required />
            @error('deadline')<p class="form-err">{{ $message }}</p>@enderror
          </div>
          <div>
            <label class="form-label">Assignee</label>
            <select name="user_id" class="form-input" required>
              <option value="">— Choisir un membre —</option>
              @foreach ($project->users as $m)
                <option value="{{ $m->id }}" @selected(old('user_id') == $m->id)>{{ $m->name }} ({{ $m->pivot->role === 'lead' ? 'Lead' : 'Dev' }})</option>
              @endforeach
            </select>
            @error('user_id')<p class="form-err">{{ $message }}</p>@enderror
          </div>
        </div>

        <div>
          <label class="form-label">Priority</label>
          <div class="grid grid-cols-3 gap-3">
            <label class="pri-pick">
              <input type="radio" name="priority" value="low" @checked(old('priority') === 'low')>
              <span class="pri-card low">
                <span class="font-display font-bold text-[18px] block">LOW</span>
                <span class="font-mono text-[10px] text-ink/60">No rush</span>
              </span>
            </label>
            <label class="pri-pick">
              <input type="radio" name="priority" value="medium" @checked(old('priority', 'medium') === 'medium')>
              <span class="pri-card medium">
                <span class="font-display font-bold text-[18px] block">MEDIUM</span>
                <span class="font-mono text-[10px] text-ink/70">This sprint</span>
              </span>
            </label>
            <label class="pri-pick">
              <input type="radio" name="priority" value="high" @checked(old('priority') === 'high')>
              <span class="pri-card high">
                <span class="font-display font-bold text-[18px] block">⚡ HIGH</span>
                <span class="font-mono text-[10px]">Drop everything</span>
              </span>
            </label>
          </div>
          @error('priority')<p class="form-err">{{ $message }}</p>@enderror
        </div>
      </div>

      <div class="px-7 py-5 border-t-[3px] border-ink bg-cream flex items-center justify-end gap-3">
        <a href="#" class="btn ghost brutal-tap">Cancel</a>
        <button type="submit" class="btn primary brutal-tap">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          Save Task
        </button>
      </div>
    </form>
  </div>
</div>
@endcan


{{-- ════════════════════════════════════════════════════════
     MODAL : TASK DETAIL  (une par tâche)
     ════════════════════════════════════════════════════════ --}}
@foreach ($project->tasks as $task)
  @php
    $td     = Carbon::parse($task->deadline);
    $urgent = $isUrgentTask($task);
    $late   = $task->status !== 'done' && now()->gt($td);
  @endphp
  <div id="m-task-{{ $task->id }}" class="modal-overlay">
    <a href="#" class="modal-backdrop" aria-label="Fermer"></a>
    <div class="modal-card lg">
      <div class="px-7 py-6 border-b-[3px] border-ink">
        <div class="flex items-start justify-between">
          <div class="flex-1">
            <div class="flex items-center gap-2 mb-3 flex-wrap">
              <span class="meta-chip">#{{ $task->id }}</span>
              <span class="pri-badge {{ $priClass[$task->priority] ?? 'pri-medium' }}">{{ $priText[$task->priority] ?? Str::upper($task->priority) }}</span>
              @if ($urgent)<span class="meta-chip urgent">⚡ URGENT</span>@endif
              @if ($late)<span class="meta-chip urgent">⚠ EN RETARD</span>@endif
            </div>
            <h3 class="font-display font-bold text-[26px] tracking-tight leading-[1.1] mb-2">{{ $task->title }}</h3>
            <div class="font-mono text-[11px] text-ink/60">
              Créée {{ Carbon::parse($task->created_at)->diffForHumans() }}
            </div>
          </div>
          <a href="#" class="modal-x ml-3" aria-label="Fermer">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" x2="6" y1="6" y2="18"/><line x1="6" x2="18" y1="6" y2="18"/></svg>
          </a>
        </div>
      </div>

      <div class="px-7 py-6 grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-2">
          <div class="font-mono text-[10px] font-bold tracking-widest text-ink/60 uppercase mb-2">Description</div>
          <p class="text-[14px] text-ink leading-relaxed whitespace-pre-line">{{ $task->description ?: 'Aucune description.' }}</p>
        </div>
        <div class="space-y-4">
          <div>
            <div class="font-mono text-[10px] font-bold tracking-widest text-ink/60 uppercase mb-2">Status</div>
            <span class="status-badge {{ $statusClass[$task->status] }} w-full justify-center" style="padding:6px 10px;">
              {{ $statusText[$task->status] }}
            </span>
          </div>
          <div>
            <div class="font-mono text-[10px] font-bold tracking-widest text-ink/60 uppercase mb-2">Assignée à</div>
            @if ($task->user)
              @php $c = $avatarColor($task->user->id); @endphp
              <div class="flex items-center gap-2 bg-cream border-[2.5px] border-ink rounded-[10px] p-2">
                <div class="avatar" style="background:{{ $c['bg'] }};color:{{ $c['fg'] }};">{{ $initials($task->user->name) }}</div>
                <div>
                  <div class="font-display font-bold text-[13px]">{{ $task->user->name }}</div>
                  <div class="font-mono text-[10px] text-ink/60">{{ $task->user->email }}</div>
                </div>
              </div>
            @else
              <div class="font-mono text-[12px] text-ink/40 italic">Non assignée</div>
            @endif
          </div>
          <div>
            <div class="font-mono text-[10px] font-bold tracking-widest text-ink/60 uppercase mb-2">Deadline</div>
            <div class="flex items-center gap-2 border-[2.5px] border-ink rounded-[10px] p-2.5 shadow-brutal-sm {{ $urgent || $late ? 'bg-red-accent text-white' : 'bg-cream' }}">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              <div>
                <div class="font-display font-bold text-[13px]">{{ $td->translatedFormat('M j, Y') }}</div>
                <div class="font-mono text-[10px] {{ $urgent || $late ? 'opacity-80' : 'text-ink/60' }}">{{ $td->format('H:i') }} · {{ $td->diffForHumans() }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="px-7 py-5 border-t-[3px] border-ink bg-cream flex flex-wrap items-center justify-end gap-3">
        @can('update', $task)
          <a href="{{ route('projects.tasks.edit', [$project, $task]) }}" class="btn ghost brutal-tap">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Edit
          </a>
        @endcan
        @can('delete', $task)
          <form method="POST" action="{{ route('projects.tasks.destroy', [$project, $task]) }}" onsubmit="return confirm('Supprimer cette tâche ?');">
            @csrf @method('DELETE')
            <button type="submit" class="btn danger brutal-tap">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
              Delete
            </button>
          </form>
        @endcan
        <div class="flex-1"></div>
        @if ($task->status !== 'done' && auth()->user()->can('updateStatus', $task))
          <form method="POST" action="{{ route('projects.tasks.updateStatus', [$project, $task]) }}">
            @csrf @method('PATCH')
            <input type="hidden" name="status" value="done">
            <button type="submit" class="btn success brutal-tap">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
              Mark as Done
            </button>
          </form>
        @endif
      </div>
    </div>
  </div>
@endforeach


{{-- ════════════════════════════════════════════════════════
     MODAL : EDIT PROJECT  →  PATCH projects.update
     ════════════════════════════════════════════════════════ --}}
@can('update', $project)
<div id="m-edit-project" class="modal-overlay">
  <a href="#" class="modal-backdrop" aria-label="Fermer"></a>
  <div class="modal-card lg">
    <form method="POST" action="{{ route('projects.update', $project) }}">
      @csrf @method('PUT')
      <div class="px-7 py-6 border-b-[3px] border-ink flex items-center justify-between" style="background:#EFF6FF;">
        <div class="flex items-center gap-4">
          <div class="w-14 h-14 grid place-items-center bg-electric-blue border-[3px] border-ink rounded-[12px] shadow-brutal-sm">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          </div>
          <div>
            <div class="font-mono text-[10px] font-bold tracking-[0.2em] text-ink/70 uppercase mb-1">// editing project</div>
            <h3 class="font-display font-bold text-[26px] tracking-tight leading-tight">Edit project details</h3>
            <p class="text-[12px] text-ink/60 mt-0.5 font-mono">PROJ-{{ str_pad($project->id, 2, '0', STR_PAD_LEFT) }} · créé le {{ Carbon::parse($project->created_at)->format('d/m/Y') }}</p>
          </div>
        </div>
        <a href="#" class="modal-x" aria-label="Fermer">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" x2="6" y1="6" y2="18"/><line x1="6" x2="18" y1="6" y2="18"/></svg>
        </a>
      </div>

      <div class="px-7 py-6 space-y-6">
        <div>
          <label class="form-label">Project title <span class="text-red-accent">*</span></label>
          <input type="text" name="title" value="{{ old('title', $project->title) }}" maxlength="255" class="form-input" required />
          @error('title')<p class="form-err">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="form-label">Description <span class="text-red-accent">*</span></label>
          <textarea name="description" rows="4" class="form-input resize-none" required>{{ old('description', $project->description) }}</textarea>
          @error('description')<p class="form-err">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="form-label">Deadline <span class="text-red-accent">*</span></label>
          <input type="date" name="deadline" value="{{ old('deadline', $deadline->toDateString()) }}" class="form-input font-mono" required />
          @error('deadline')<p class="form-err">{{ $message }}</p>@enderror
        </div>

        @can('delete', $project)
          <div class="border-[3px] border-red-accent rounded-[10px] p-4" style="background:#FEF2F2;">
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="font-display font-bold text-[13px] text-red-accent uppercase tracking-wider mb-0.5">⚠ Danger zone</div>
                <p class="text-[12px] text-ink/70">Archiver ce projet l'envoie au coffre. Vous pourrez le restaurer plus tard.</p>
              </div>
            </div>
          </div>
        @endcan
      </div>

      <div class="px-7 py-5 border-t-[3px] border-ink bg-cream flex items-center justify-between gap-3">
        @can('delete', $project)
          <button type="submit" form="form-archive-project" class="btn-mini danger">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8v13H3V8"/><path d="M1 3h22v5H1z"/><path d="M10 12h4"/></svg>
            Archiver
          </button>
        @else
          <span></span>
        @endcan
        <div class="flex items-center gap-3">
          <a href="#" class="btn ghost brutal-tap">Cancel</a>
          <button type="submit" class="btn primary brutal-tap">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            Save Changes
          </button>
        </div>
      </div>
    </form>
    @can('delete', $project)
      {{-- Formulaire séparé pour le bouton « Archiver » du danger zone --}}
      <form id="form-archive-project" method="POST" action="{{ route('projects.destroy', $project) }}" onsubmit="return confirm('Archiver ce projet ?');">
        @csrf @method('DELETE')
      </form>
    @endcan
  </div>
</div>
@endcan


{{-- ════════════════════════════════════════════════════════
     MODAL : NEW PROJECT  →  POST projects.store
     ════════════════════════════════════════════════════════ --}}
@can('create', App\Models\Project::class)
<div id="m-new-project" class="modal-overlay">
  <a href="#" class="modal-backdrop" aria-label="Fermer"></a>
  <div class="modal-card lg">
    <form method="POST" action="{{ route('projects.store') }}">
      @csrf
      <div class="px-7 py-6 border-b-[3px] border-ink flex items-center justify-between" style="background:#EFF6FF;">
        <div class="flex items-center gap-4">
          <div class="w-14 h-14 grid place-items-center bg-electric-blue border-[3px] border-ink rounded-[12px] shadow-brutal-sm">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
          </div>
          <div>
            <div class="font-mono text-[10px] font-bold tracking-[0.2em] text-ink/70 uppercase mb-1">// new project</div>
            <h3 class="font-display font-bold text-[26px] tracking-tight leading-tight">Spin up a new project</h3>
          </div>
        </div>
        <a href="#" class="modal-x" aria-label="Fermer">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" x2="6" y1="6" y2="18"/><line x1="6" x2="18" y1="6" y2="18"/></svg>
        </a>
      </div>

      <div class="px-7 py-6 space-y-6">
        <div>
          <label class="form-label">Project title <span class="text-red-accent">*</span></label>
          <input type="text" name="title" value="{{ old('title') }}" maxlength="255" placeholder="What are we shipping?" class="form-input" required />
        </div>
        <div>
          <label class="form-label">Description <span class="text-red-accent">*</span></label>
          <textarea name="description" rows="4" placeholder="What's the goal? Who's it for?" class="form-input resize-none" required>{{ old('description') }}</textarea>
        </div>
        <div>
          <label class="form-label">Deadline <span class="text-red-accent">*</span></label>
          <input type="date" name="deadline" value="{{ old('deadline') }}" min="{{ now()->addDay()->toDateString() }}" class="form-input font-mono" required />
        </div>
      </div>

      <div class="px-7 py-5 border-t-[3px] border-ink bg-cream flex items-center justify-end gap-3">
        <a href="#" class="btn ghost brutal-tap">Cancel</a>
        <button type="submit" class="btn primary brutal-tap">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
          Create Project
        </button>
      </div>
    </form>
  </div>
</div>
@endcan


{{-- ════════════════════════════════════════════════════════
     MODAL : ADD MEMBER  →  POST projects.members.store
     ════════════════════════════════════════════════════════ --}}
@can('manageMembers', $project)
<div id="m-add-member" class="modal-overlay {{ $openAddMember ? 'is-open' : '' }}">
  <a href="#" class="modal-backdrop" aria-label="Fermer"></a>
  <div class="modal-card">
    <form method="POST" action="{{ route('projects.members.store', $project) }}">
      @csrf
      <div class="px-7 py-6 border-b-[3px] border-ink flex items-center justify-between">
        <h3 class="font-display font-bold text-[22px]">Inviter au projet</h3>
        <a href="#" class="modal-x" style="width:36px;height:36px;" aria-label="Fermer">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" x2="6" y1="6" y2="18"/><line x1="6" x2="18" y1="6" y2="18"/></svg>
        </a>
      </div>
      <div class="px-7 py-6">
        <label class="form-label">Adresse email</label>
        <input type="email" name="email" value="{{ old('email') }}" placeholder="dev@devtrack.ma" class="form-input" required />
        @error('email')<p class="form-err">{{ $message }}</p>@enderror
        <p class="text-[12px] text-ink/60 mt-3">Le membre doit déjà avoir un compte DevTrack. Il sera ajouté comme <strong>developer</strong>.</p>
      </div>
      <div class="px-7 py-5 border-t-[3px] border-ink bg-cream flex items-center justify-end gap-3">
        <a href="#" class="btn ghost brutal-tap">Cancel</a>
        <button type="submit" class="btn primary brutal-tap">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
          Inviter
        </button>
      </div>
    </form>
  </div>
</div>
@endcan

</body>
</html>