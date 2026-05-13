<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>DevTrack — Sign up</title>

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
        }
      }
    }
  }
</script>

<style>
  html, body { height: 100%; }
  body {
    font-family: 'Inter', sans-serif;
    color: #0A0A0A;
    background: #FFFBEB;
    -webkit-font-smoothing: antialiased;
    overflow: hidden;
  }
  .font-display { font-family: 'Space Grotesk', sans-serif; letter-spacing: -0.02em; }
  .font-mono    { font-family: 'JetBrains Mono', monospace; }

  .brutal-tap {
    transition: transform 180ms cubic-bezier(.2,.8,.2,1),
                box-shadow 180ms cubic-bezier(.2,.8,.2,1);
  }
  .brutal-tap:hover { transform: translate(-2px, -2px); box-shadow: 8px 8px 0 0 #0A0A0A; }
  .brutal-tap:active { transform: translate(2px, 2px); box-shadow: 2px 2px 0 0 #0A0A0A; transition-duration: 80ms; }

  .hero-stage { background: linear-gradient(135deg, #2563EB 0%, #06B6D4 100%); }

  .float-shape {
    position: absolute;
    border: 3px solid #0A0A0A;
    box-shadow: 6px 6px 0 0 #0A0A0A;
  }
  .float-shape.spin     { animation: spin 18s linear infinite; }
  .float-shape.bob      { animation: bob 6s ease-in-out infinite; }
  .float-shape.bob-slow { animation: bob 9s ease-in-out infinite; }
  @keyframes spin { to { transform: rotate(360deg); } }
  @keyframes bob  { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-14px); } }

  .dot-grid {
    background-image: radial-gradient(circle, rgba(10,10,10,.18) 1.2px, transparent 1.2px);
    background-size: 22px 22px;
  }

  .brutal-input {
    width: 100%;
    background: #FFFFFF;
    border: 3px solid #0A0A0A;
    border-radius: 8px;
    padding: 14px 46px 14px 46px;
    font-family: 'Inter', sans-serif;
    font-weight: 500;
    font-size: 16px;
    color: #0A0A0A;
    transition: box-shadow 160ms ease, transform 160ms ease, border-color 160ms ease;
  }
  .brutal-input::placeholder { color: #9CA3AF; }
  .brutal-input:focus {
    outline: none;
    border-color: #2563EB;
    box-shadow: 6px 6px 0 0 #2563EB;
    transform: translate(-2px, -2px);
  }
  .brutal-input.error { border-color: #EF4444; box-shadow: 4px 4px 0 0 #EF4444; }

  .input-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    width: 20px;
    height: 20px;
    color: #0A0A0A;
    pointer-events: none;
  }

  .input-toggle {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    width: 22px;
    height: 22px;
    color: #0A0A0A;
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 0;
    display: grid;
    place-items: center;
  }

  .brutal-check {
    appearance: none;
    -webkit-appearance: none;
    width: 22px;
    height: 22px;
    border: 3px solid #0A0A0A;
    border-radius: 4px;
    background: #FFFFFF;
    cursor: pointer;
    position: relative;
    box-shadow: 2px 2px 0 0 #0A0A0A;
    transition: all 140ms ease;
    flex-shrink: 0;
  }
  .brutal-check:checked { background: #2563EB; }
  .brutal-check:checked::after {
    content: '';
    position: absolute;
    inset: 0;
    background: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='4' stroke-linecap='round' stroke-linejoin='round'><polyline points='20 6 9 17 4 12'/></svg>") center/14px no-repeat;
  }

  .seg-track {
    position: relative;
    background: #FFFFFF;
    border: 3px solid #0A0A0A;
    border-radius: 10px;
    padding: 4px;
    box-shadow: 4px 4px 0 0 #0A0A0A;
    display: grid;
    grid-template-columns: 1fr 1fr;
  }
  .seg-pill {
    position: absolute;
    top: 4px; bottom: 4px;
    width: calc(50% - 4px);
    background: #0A0A0A;
    border-radius: 6px;
    z-index: 0;
    transition: transform 220ms cubic-bezier(.2,.8,.2,1);
  }
  .seg-pill.right { transform: translateX(100%); }
  .seg-btn {
    position: relative;
    z-index: 1;
    padding: 10px 14px;
    font-family: 'Space Grotesk', sans-serif;
    font-weight: 700;
    font-size: 14px;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #0A0A0A;
    text-align: center;
    text-decoration: none;
  }
  .seg-btn.active { color: #FFFFFF; }

  .btn-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    background: #2563EB;
    color: #FFFFFF;
    border: 3px solid #0A0A0A;
    border-radius: 10px;
    padding: 16px 24px;
    font-family: 'Space Grotesk', sans-serif;
    font-weight: 700;
    font-size: 18px;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    box-shadow: 6px 6px 0 0 #0A0A0A;
    cursor: pointer;
  }
  .btn-primary .arrow { transition: transform 200ms ease; }
  .btn-primary:hover .arrow { transform: translateX(4px); }

  .btn-social {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    background: #FFFFFF;
    color: #0A0A0A;
    border: 3px solid #0A0A0A;
    border-radius: 10px;
    padding: 12px 16px;
    font-family: 'Space Grotesk', sans-serif;
    font-weight: 600;
    font-size: 14px;
    box-shadow: 4px 4px 0 0 #0A0A0A;
    cursor: pointer;
    text-decoration: none;
  }

  .logo-mark {
    width: 44px;
    height: 44px;
    border: 3px solid #0A0A0A;
    border-radius: 10px;
    background: #2563EB;
    display: grid;
    place-items: center;
    box-shadow: 4px 4px 0 0 #0A0A0A;
    transition: transform 240ms ease;
  }
  .logo-mark:hover { transform: rotate(-6deg); }

  .tag-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #FFFFFF;
    color: #0A0A0A;
    border: 3px solid #0A0A0A;
    border-radius: 999px;
    padding: 6px 14px 6px 8px;
    box-shadow: 4px 4px 0 0 #0A0A0A;
    font-family: 'JetBrains Mono', monospace;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.04em;
  }
  .tag-dot {
    width: 10px; height: 10px;
    background: #84CC16;
    border: 2px solid #0A0A0A;
    border-radius: 999px;
  }

  .marquee {
    display: flex;
    gap: 32px;
    animation: scroll 26s linear infinite;
    white-space: nowrap;
  }
  @keyframes scroll {
    from { transform: translateX(0); }
    to   { transform: translateX(-50%); }
  }

  .stagger > * {
    opacity: 0;
    transform: translateY(12px);
    animation: rise 520ms cubic-bezier(.2,.8,.2,1) forwards;
  }
  .stagger > *:nth-child(1) { animation-delay: 60ms; }
  .stagger > *:nth-child(2) { animation-delay: 120ms; }
  .stagger > *:nth-child(3) { animation-delay: 180ms; }
  .stagger > *:nth-child(4) { animation-delay: 240ms; }
  .stagger > *:nth-child(5) { animation-delay: 300ms; }
  .stagger > *:nth-child(6) { animation-delay: 360ms; }
  .stagger > *:nth-child(7) { animation-delay: 420ms; }
  .stagger > *:nth-child(8) { animation-delay: 480ms; }
  @keyframes rise { to { opacity: 1; transform: translateY(0); } }

  .strength-track {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 6px;
    margin-top: 10px;
  }
  .strength-bar {
    height: 6px;
    background: #FFFFFF;
    border: 2px solid #0A0A0A;
    border-radius: 999px;
    transition: background 160ms ease;
  }
  .strength-bar.on-1 { background: #EF4444; }
  .strength-bar.on-2 { background: #F59E0B; }
  .strength-bar.on-3 { background: #84CC16; }
  .strength-bar.on-4 { background: #2563EB; }

  @media (max-width: 1023px) {
    body { overflow: auto; }
    .hero-stage { display: none; }
  }
</style>
</head>

<body>

<div class="grid grid-cols-1 lg:grid-cols-2 min-h-screen">

  {{-- LEFT: FORM PANEL --}}
  <section class="relative bg-cream flex flex-col px-6 sm:px-10 lg:px-16 py-8">

    <div class="absolute inset-0 dot-grid opacity-60 pointer-events-none"></div>

    <header class="relative flex items-center justify-between">
      <a href="{{ url('/') }}" class="flex items-center gap-3 group">
        <div class="logo-mark">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
            <path d="M13 2 L4 14 H11 L11 22 L20 10 H13 Z" fill="white" />
          </svg>
        </div>
        <div class="leading-none">
          <div class="font-display font-bold text-[22px] text-ink">DevTrack</div>
          <div class="font-mono text-[10px] text-ink/60 mt-0.5 tracking-widest">v1.0 · TECHNOPARK</div>
        </div>
      </a>

      <div class="hidden sm:flex items-center gap-2 font-mono text-[11px] font-semibold text-ink/70">
        <span class="w-2 h-2 rounded-full bg-lime-accent border-2 border-ink"></span>
        ALL SYSTEMS GO
      </div>
    </header>

    <div class="relative flex-1 flex items-center justify-center py-10">
      <div class="w-full max-w-[440px] stagger">

        <div class="flex items-center gap-2 mb-4">
          <span class="font-mono text-[11px] font-semibold tracking-[0.18em] text-electric-blue uppercase">
            // new_recruit
          </span>
          <div class="h-[2px] flex-1 bg-ink/15"></div>
        </div>

        <h1 class="font-display font-bold text-[40px] sm:text-[48px] leading-[1.02] text-ink mb-3">
          Join the <br/>build crew.
        </h1>
        <p class="text-ink/70 text-[15px] mb-7 max-w-[380px]">
          Create an account to start tracking projects with your team.
        </p>

        {{-- TOGGLE --}}
        <div class="seg-track mb-6">
          <div class="seg-pill right"></div>
          <a class="seg-btn" href="{{ route('login') }}">Sign in</a>
          <a class="seg-btn active" href="{{ route('register') }}">Sign up</a>
        </div>

        <form method="POST" action="{{ route('register') }}" novalidate id="registerForm">
          @csrf

          {{-- Full name --}}
          <div class="mb-4">
            <label for="name" class="block font-display font-semibold text-[12px] uppercase tracking-[0.14em] text-ink mb-2">
              Full name
            </label>
            <div class="relative">
              <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              <input
                id="name"
                type="text"
                name="name"
                value="{{ old('name') }}"
                placeholder="Yassine El Hammouti"
                required autofocus autocomplete="name"
                class="brutal-input @error('name') error @enderror"
              />
            </div>
            @error('name')
              <p class="mt-2 text-red-accent text-[13px] font-semibold flex items-center gap-1.5">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                {{ $message }}
              </p>
            @enderror
          </div>

          {{-- Email --}}
          <div class="mb-4">
            <label for="email" class="block font-display font-semibold text-[12px] uppercase tracking-[0.14em] text-ink mb-2">
              Email address
            </label>
            <div class="relative">
              <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 5L2 7"/></svg>
              <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                placeholder="you@devtrack.ma"
                required autocomplete="username"
                class="brutal-input @error('email') error @enderror"
              />
            </div>
            @error('email')
              <p class="mt-2 text-red-accent text-[13px] font-semibold flex items-center gap-1.5">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                {{ $message }}
              </p>
            @enderror
          </div>

          {{-- Password --}}
          <div class="mb-2">
            <label for="password" class="block font-display font-semibold text-[12px] uppercase tracking-[0.14em] text-ink mb-2">
              Password
            </label>
            <div class="relative">
              <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              <input
                id="password"
                type="password"
                name="password"
                placeholder="••••••••••"
                required autocomplete="new-password"
                class="brutal-input @error('password') error @enderror"
              />
              <button type="button" class="input-toggle" id="togglePassword" aria-label="Toggle password visibility">
                <svg id="eyeOpen" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg id="eyeClosed" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" y1="2" x2="22" y2="22"/></svg>
              </button>
            </div>
            <input type="hidden" name="password_confirmation" id="password_confirmation">
            @error('password')
              <p class="mt-2 text-red-accent text-[13px] font-semibold flex items-center gap-1.5">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                {{ $message }}
              </p>
            @enderror

            <div class="strength-track" aria-hidden="true">
              <div class="strength-bar" data-bar="1"></div>
              <div class="strength-bar" data-bar="2"></div>
              <div class="strength-bar" data-bar="3"></div>
              <div class="strength-bar" data-bar="4"></div>
            </div>
            <div class="flex items-center justify-between mt-1.5 font-mono text-[10px] font-semibold tracking-widest text-ink/60 uppercase">
              <span>Strength</span>
              <span id="strengthLabel">Weak</span>
            </div>
          </div>

          {{-- Terms --}}
          <div class="flex items-start gap-2.5 mt-5 mb-6">
            <input id="terms" type="checkbox" name="terms" required class="brutal-check mt-0.5" checked />
            <label for="terms" class="text-[14px] font-medium text-ink select-none">
              I agree to the
              <a href="#" class="text-electric-blue underline underline-offset-4 decoration-2">Terms</a>
              &amp;
              <a href="#" class="text-electric-blue underline underline-offset-4 decoration-2">Privacy Policy</a>.
            </label>
          </div>

          <button type="submit" class="btn-primary brutal-tap">
            <span>Create account</span>
            <svg class="arrow" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
          </button>

          <div class="relative my-7">
            <div class="h-[3px] bg-ink/10"></div>
            <span class="absolute left-1/2 -translate-x-1/2 -top-2.5 bg-cream px-3 font-mono text-[11px] font-semibold tracking-widest text-ink/50 uppercase">
              or continue with
            </span>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <a href="#" class="btn-social brutal-tap">
              <svg width="18" height="18" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.27-4.74 3.27-8.1Z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.99.66-2.25 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84A11 11 0 0 0 12 23Z"/><path fill="#FBBC05" d="M5.84 14.1A6.6 6.6 0 0 1 5.5 12c0-.73.13-1.44.34-2.1V7.07H2.18A11 11 0 0 0 1 12c0 1.78.43 3.46 1.18 4.93l3.66-2.83Z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.83C6.71 7.31 9.14 5.38 12 5.38Z"/></svg>
              Google
            </a>
            <a href="#" class="btn-social brutal-tap">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="#0A0A0A"><path d="M12 .5C5.65.5.5 5.65.5 12c0 5.08 3.29 9.39 7.86 10.91.58.1.79-.25.79-.56v-2c-3.2.7-3.87-1.37-3.87-1.37-.52-1.32-1.27-1.67-1.27-1.67-1.04-.71.08-.7.08-.7 1.15.08 1.76 1.18 1.76 1.18 1.02 1.75 2.69 1.25 3.35.96.1-.74.4-1.25.72-1.54-2.55-.29-5.24-1.28-5.24-5.69 0-1.26.45-2.28 1.18-3.09-.12-.29-.51-1.46.11-3.04 0 0 .97-.31 3.18 1.18a11 11 0 0 1 5.79 0c2.2-1.49 3.17-1.18 3.17-1.18.63 1.58.24 2.75.12 3.04.74.81 1.18 1.83 1.18 3.09 0 4.42-2.69 5.39-5.25 5.68.41.36.78 1.06.78 2.13v3.16c0 .31.21.66.79.55A11.51 11.51 0 0 0 23.5 12C23.5 5.65 18.35.5 12 .5Z"/></svg>
              GitHub
            </a>
          </div>
        </form>
      </div>
    </div>

    <footer class="relative pt-4 flex flex-col sm:flex-row items-center justify-between gap-3 text-[12px] font-mono text-ink/50">
      <div>&copy; {{ date('Y') }} DevTrack — Made in Agadir</div>
      <div class="flex items-center gap-4">
        <a href="#" class="hover:text-ink">Privacy</a>
        <a href="#" class="hover:text-ink">Terms</a>
        <a href="#" class="hover:text-ink">Status</a>
      </div>
    </footer>
  </section>


  {{-- RIGHT: HERO STAGE --}}
  <section class="hero-stage relative overflow-hidden hidden lg:block">

    <div class="absolute inset-0 opacity-40 dot-grid"></div>

    <div class="float-shape spin" style="top:14%; left:18%; width:120px; height:120px; background:#F59E0B; border-radius:8px;"></div>

    <div class="float-shape bob" style="top:62%; left:10%; width:88px; height:88px; background:#EC4899; border-radius:9999px;"></div>

    <div class="float-shape bob-slow" style="top:22%; right:14%; width:140px; height:90px; background:#FFFFFF; border-radius:8px; transform: rotate(-12deg);">
      <div class="p-3">
        <div class="flex gap-1 mb-2">
          <span class="w-2 h-2 rounded-full bg-red-accent border border-ink"></span>
          <span class="w-2 h-2 rounded-full bg-amber-accent border border-ink"></span>
          <span class="w-2 h-2 rounded-full bg-lime-accent border border-ink"></span>
        </div>
        <div class="font-mono text-[10px] text-ink/80 leading-relaxed">
          <div><span class="text-electric-blue">const</span> ship = <span class="text-pink-pop">true</span>;</div>
          <div><span class="text-electric-blue">if</span>(ship) <span class="text-ink">&rarr; done.</span></div>
        </div>
      </div>
    </div>

    <div class="float-shape bob" style="bottom:18%; right:22%; width:100px; height:100px; background:transparent; border:none; box-shadow:none;">
      <svg viewBox="0 0 100 100" width="100" height="100">
        <polygon points="50,8 95,90 5,90" fill="#84CC16" stroke="#0A0A0A" stroke-width="3"/>
      </svg>
    </div>

    <div class="float-shape spin" style="bottom:32%; left:30%; width:64px; height:64px; background:transparent; border:5px solid #0A0A0A; border-radius:9999px; box-shadow: 6px 6px 0 0 #06B6D4;"></div>

    <div class="relative z-10 h-full flex flex-col justify-between px-12 py-10">

      <div class="flex items-center justify-between">
        <div class="tag-pill">
          <span class="tag-dot"></span>
          DEVTRACK · v1.0
        </div>
        <div class="font-mono text-[11px] font-semibold text-white/80 tracking-widest">
          // INTERNAL_TOOL
        </div>
      </div>

      <div class="relative">

        <div class="absolute -top-8 right-0 lg:right-6">
          <div class="relative" style="width:96px; height:96px;">
            <div class="absolute inset-0 bg-ink rounded-[12px]" style="transform: translate(8px, 8px);"></div>
            <div class="absolute inset-0 bg-white border-[3px] border-ink rounded-[12px] flex flex-col items-center justify-center gap-2">
              <div class="flex gap-3">
                <div class="w-4 h-5 bg-ink rounded-sm"></div>
                <div class="w-4 h-5 bg-ink rounded-sm"></div>
              </div>
              <div class="w-7 h-1.5 bg-ink rounded-full"></div>
              <div class="absolute -top-3 left-1/2 -translate-x-1/2 w-1 h-3 bg-ink"></div>
              <div class="absolute -top-5 left-1/2 -translate-x-1/2 w-3 h-3 bg-pink-pop border-[2.5px] border-ink rounded-full"></div>
            </div>
          </div>
          <div class="mt-2 text-center font-mono text-[10px] font-bold text-white tracking-widest">
            BIT — YOUR PM
          </div>
        </div>

        <h2 class="font-display font-bold text-white text-[88px] xl:text-[112px] leading-[0.9] tracking-[-0.04em] mb-2">
          Track.<br/>
          <span class="inline-flex items-center">
            Build.
            <span class="inline-block w-3 h-3 bg-lime-accent border-[3px] border-ink ml-3 mb-2 rounded-sm"></span>
          </span><br/>
          Ship.
        </h2>

        <div class="mt-6 flex items-center gap-3">
          <div class="h-[3px] w-12 bg-white"></div>
          <p class="text-white/95 font-medium text-[16px] max-w-[400px]">
            Project &amp; task management for small dev crews. No bloat. No WhatsApp soup.
          </p>
        </div>
      </div>

      <div class="grid grid-cols-3 gap-4">

        <div class="bg-white border-[3px] border-ink rounded-[10px] p-4 shadow-brutal-sm">
          <div class="flex items-center justify-between">
            <div class="font-mono text-[10px] font-semibold tracking-widest text-ink/60">PROJECTS</div>
            <span class="w-6 h-6 grid place-items-center bg-electric-blue border-2 border-ink rounded-md">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
            </span>
          </div>
          <div class="font-display font-bold text-[34px] leading-none mt-3 text-ink">12</div>
          <div class="font-mono text-[10px] mt-1 text-lime-accent font-semibold">+3 this week</div>
        </div>

        <div class="bg-amber-accent border-[3px] border-ink rounded-[10px] p-4 shadow-brutal-sm">
          <div class="flex items-center justify-between">
            <div class="font-mono text-[10px] font-semibold tracking-widest text-ink/80">TASKS LIVE</div>
            <span class="w-6 h-6 grid place-items-center bg-white border-2 border-ink rounded-md">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#0A0A0A" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v10l4 4"/><circle cx="12" cy="12" r="10"/></svg>
            </span>
          </div>
          <div class="font-display font-bold text-[34px] leading-none mt-3 text-ink">47</div>
          <div class="font-mono text-[10px] mt-1 text-ink/80 font-semibold">8 urgent · 12 done</div>
        </div>

        <div class="bg-white border-[3px] border-ink rounded-[10px] p-4 shadow-brutal-sm">
          <div class="flex items-center justify-between">
            <div class="font-mono text-[10px] font-semibold tracking-widest text-ink/60">CREW</div>
            <span class="w-6 h-6 grid place-items-center bg-pink-pop border-2 border-ink rounded-md">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </span>
          </div>
          <div class="flex items-center gap-3 mt-3">
            <div class="flex -space-x-2">
              <div class="w-8 h-8 rounded-full border-[2.5px] border-ink bg-electric-cyan grid place-items-center font-display font-bold text-[12px] text-ink">YH</div>
              <div class="w-8 h-8 rounded-full border-[2.5px] border-ink bg-pink-pop grid place-items-center font-display font-bold text-[12px] text-white">SK</div>
              <div class="w-8 h-8 rounded-full border-[2.5px] border-ink bg-lime-accent grid place-items-center font-display font-bold text-[12px] text-ink">MA</div>
              <div class="w-8 h-8 rounded-full border-[2.5px] border-ink bg-amber-accent grid place-items-center font-display font-bold text-[12px] text-ink">+1</div>
            </div>
          </div>
          <div class="font-mono text-[10px] mt-2 text-ink/60 font-semibold">4 BUILDERS ONLINE</div>
        </div>
      </div>
    </div>

    <div class="absolute bottom-0 left-0 right-0 bg-ink text-white border-t-[3px] border-ink overflow-hidden h-9 flex items-center">
      <div class="marquee font-mono text-[12px] font-semibold tracking-widest uppercase">
        <span>&#9733; Build &middot; Track &middot; Ship</span>
        <span>&#9733; Made in Agadir, Morocco</span>
        <span>&#9733; Laravel + Tailwind</span>
        <span>&#9733; For small dev crews</span>
        <span>&#9733; v1.0 — Internal tool</span>
        <span>&#9733; Build &middot; Track &middot; Ship</span>
        <span>&#9733; Made in Agadir, Morocco</span>
        <span>&#9733; Laravel + Tailwind</span>
        <span>&#9733; For small dev crews</span>
        <span>&#9733; v1.0 — Internal tool</span>
      </div>
    </div>
  </section>

</div>

<script>
  (function () {
    const pwd = document.getElementById('password');
    const pwdConfirm = document.getElementById('password_confirmation');
    const toggle = document.getElementById('togglePassword');
    const eyeOpen = document.getElementById('eyeOpen');
    const eyeClosed = document.getElementById('eyeClosed');
    const bars = document.querySelectorAll('.strength-bar');
    const label = document.getElementById('strengthLabel');

    toggle.addEventListener('click', function () {
      const isPwd = pwd.type === 'password';
      pwd.type = isPwd ? 'text' : 'password';
      eyeOpen.style.display = isPwd ? 'none' : '';
      eyeClosed.style.display = isPwd ? '' : 'none';
    });

    function score(v) {
      let s = 0;
      if (v.length >= 6)  s++;
      if (v.length >= 10) s++;
      if (/[A-Z]/.test(v) && /[a-z]/.test(v)) s++;
      if (/\d/.test(v) && /[^A-Za-z0-9]/.test(v)) s++;
      return Math.min(s, 4);
    }
    const labels = ['Weak', 'Weak', 'Fair', 'Good', 'Strong'];

    pwd.addEventListener('input', function () {
      pwdConfirm.value = pwd.value;
      const s = pwd.value.length === 0 ? 0 : Math.max(score(pwd.value), 1);
      bars.forEach((b, i) => {
        b.classList.remove('on-1', 'on-2', 'on-3', 'on-4');
        if (i < s) b.classList.add('on-' + s);
      });
      label.textContent = labels[s].toUpperCase();
    });
  })();
</script>

</body>
</html>
