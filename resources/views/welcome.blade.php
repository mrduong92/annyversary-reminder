<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gia Phong — Nhắc Ngày Giỗ Tự Động</title>
    <meta name="description" content="Hệ thống nhắc lịch giỗ tự động qua Zalo cho gia đình Việt Nam. Setup một lần, cả nhà nhận nhắc nhở đúng ngày.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', sans-serif;
            background: #FBF7F0;
            color: #2D1F15;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        /* ── Colors ── */
        :root {
            --red:    #8B2635;
            --red-light: #A8324A;
            --gold:   #B8861A;
            --gold-light: #D4A84B;
            --cream:  #FBF7F0;
            --cream-dark: #F2EAD8;
            --brown:  #3D2B1F;
            --brown-light: #6B4C38;
            --ink:    #1A0F0A;
        }

        /* ── Typography ── */
        .font-serif  { font-family: 'Lora', Georgia, serif; }
        .font-sans   { font-family: 'Inter', sans-serif; }

        /* ── Nav ── */
        nav {
            position: fixed; top: 0; left: 0; right: 0; z-index: 100;
            background: rgba(251,247,240,0.92);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(184,134,26,0.15);
        }
        .nav-inner {
            max-width: 1100px; margin: 0 auto;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 24px; height: 64px;
        }
        .logo {
            font-family: 'Lora', serif;
            font-size: 1.35rem; font-weight: 600;
            color: var(--red); letter-spacing: -0.01em;
            text-decoration: none;
            display: flex; align-items: center; gap: 8px;
        }
        .logo-icon { width: 28px; height: 28px; flex-shrink: 0; }
        .nav-links { display: flex; align-items: center; gap: 8px; }
        .btn-ghost {
            padding: 8px 18px; border-radius: 8px;
            font-size: 0.875rem; font-weight: 500; text-decoration: none;
            color: var(--brown); transition: background 0.15s;
        }
        .btn-ghost:hover { background: var(--cream-dark); }
        .btn-primary {
            padding: 8px 20px; border-radius: 8px;
            background: var(--red); color: #fff;
            font-size: 0.875rem; font-weight: 500; text-decoration: none;
            transition: background 0.15s; border: none; cursor: pointer;
        }
        .btn-primary:hover { background: var(--red-light); }

        /* ── Sections ── */
        section { padding: 80px 24px; }
        .container { max-width: 1100px; margin: 0 auto; }
        .section-tag {
            display: inline-flex; align-items: center; gap: 6px;
            font-family: 'Inter', sans-serif;
            font-size: 0.75rem; font-weight: 600; letter-spacing: 0.1em;
            text-transform: uppercase; color: var(--gold);
            margin-bottom: 12px;
        }
        .section-tag::before, .section-tag::after {
            content: ''; display: block;
            width: 24px; height: 1px; background: var(--gold-light);
        }
        h1, h2, h3 { font-family: 'Lora', Georgia, serif; line-height: 1.25; }
        h1 { font-size: clamp(2.2rem, 5vw, 3.4rem); color: var(--ink); }
        h2 { font-size: clamp(1.75rem, 3.5vw, 2.4rem); color: var(--ink); }
        h3 { font-size: 1.2rem; color: var(--brown); }
        p { color: var(--brown-light); }

        /* ── Divider ornament ── */
        .ornament {
            display: flex; align-items: center; gap: 12px;
            margin: 0 auto 16px; width: fit-content;
        }
        .ornament-line { width: 40px; height: 1px; background: var(--gold-light); }
        .ornament-diamond {
            width: 6px; height: 6px; background: var(--gold);
            transform: rotate(45deg); flex-shrink: 0;
        }

        /* ── Hero ── */
        #hero {
            padding-top: 140px; padding-bottom: 80px;
            text-align: center;
            background:
                radial-gradient(ellipse 70% 50% at 50% 0%, rgba(139,38,53,0.06) 0%, transparent 70%),
                var(--cream);
            position: relative; overflow: hidden;
        }
        .hero-pattern {
            position: absolute; inset: 0; opacity: 0.025;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%238B2635' fill-rule='evenodd'%3E%3Ccircle cx='30' cy='30' r='2'/%3E%3Cpath d='M30 0L32 28 30 30 28 28z'/%3E%3Cpath d='M60 30L32 32 30 30 32 28z'/%3E%3Cpath d='M30 60L28 32 30 30 32 32z'/%3E%3Cpath d='M0 30L28 28 30 30 28 32z'/%3E%3C/g%3E%3C/svg%3E");
            pointer-events: none;
        }
        .hero-badge {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(184,134,26,0.1); border: 1px solid rgba(184,134,26,0.25);
            border-radius: 100px; padding: 5px 14px;
            font-size: 0.8rem; font-weight: 500; color: var(--gold);
            margin-bottom: 28px;
        }
        .hero-badge-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--gold); }
        .hero-title { margin-bottom: 20px; }
        .hero-title em { font-style: italic; color: var(--red); }
        .hero-sub {
            font-size: 1.125rem; color: var(--brown-light);
            max-width: 560px; margin: 0 auto 40px; line-height: 1.7;
        }
        .hero-cta { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
        .btn-lg {
            padding: 14px 28px; border-radius: 10px;
            font-size: 1rem; font-weight: 500; text-decoration: none;
            transition: all 0.15s; display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-red { background: var(--red); color: #fff; }
        .btn-red:hover { background: var(--red-light); transform: translateY(-1px); box-shadow: 0 4px 16px rgba(139,38,53,0.25); }
        .btn-outline {
            background: transparent; color: var(--brown);
            border: 1.5px solid rgba(61,43,31,0.2);
        }
        .btn-outline:hover { background: var(--cream-dark); border-color: rgba(61,43,31,0.35); }
        .hero-note {
            margin-top: 16px; font-size: 0.8rem; color: #A09080;
        }

        /* ── Phone mockup ── */
        .phone-wrap {
            margin-top: 64px; position: relative;
            display: flex; justify-content: center;
        }
        .phone-outer {
            width: 280px; border-radius: 36px;
            background: var(--ink);
            padding: 10px;
            box-shadow: 0 32px 80px rgba(26,15,10,0.25), 0 0 0 1px rgba(255,255,255,0.05);
        }
        .phone-screen {
            border-radius: 28px; overflow: hidden;
            background: #fff;
        }
        .phone-notch {
            height: 28px; background: var(--ink);
            display: flex; align-items: center; justify-content: center;
        }
        .phone-camera { width: 10px; height: 10px; border-radius: 50%; background: #333; }
        .zns-card {
            padding: 16px;
            background: linear-gradient(135deg, #0068FF 0%, #0050CC 100%);
            color: #fff;
        }
        .zns-top { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
        .zns-avatar {
            width: 36px; height: 36px; border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; flex-shrink: 0;
        }
        .zns-sender { font-size: 0.7rem; font-weight: 600; }
        .zns-time   { font-size: 0.6rem; opacity: 0.7; }
        .zns-title  { font-size: 0.75rem; font-weight: 700; margin-bottom: 4px; }
        .zns-body   { font-size: 0.65rem; opacity: 0.9; line-height: 1.5; }
        .zns-footer {
            background: #F5F5F5; padding: 10px 16px;
            font-size: 0.6rem; color: #666;
        }
        .zns-footer strong { color: #333; font-weight: 600; display: block; margin-bottom: 2px; }
        .phone-pad { height: 32px; background: #F9F9F9; }
        .phone-glow {
            position: absolute;
            width: 200px; height: 200px; border-radius: 50%;
            background: radial-gradient(circle, rgba(139,38,53,0.15) 0%, transparent 70%);
            top: 50%; left: 50%; transform: translate(-50%, -50%);
            pointer-events: none;
        }

        /* ── Trust bar ── */
        #trust {
            padding: 28px 24px;
            border-top: 1px solid rgba(184,134,26,0.12);
            border-bottom: 1px solid rgba(184,134,26,0.12);
            background: rgba(184,134,26,0.04);
        }
        .trust-inner {
            max-width: 900px; margin: 0 auto;
            display: flex; align-items: center; justify-content: center;
            gap: 40px; flex-wrap: wrap;
        }
        .trust-item {
            display: flex; align-items: center; gap: 8px;
            font-size: 0.85rem; color: var(--brown-light); font-weight: 500;
        }
        .trust-icon { font-size: 1.1rem; }

        /* ── How it works ── */
        #how { background: var(--cream); text-align: center; }
        .steps {
            display: grid; grid-template-columns: repeat(3, 1fr);
            gap: 32px; margin-top: 48px;
        }
        @media(max-width:700px){ .steps { grid-template-columns: 1fr; } }
        .step {
            display: flex; flex-direction: column; align-items: center; gap: 16px;
            padding: 32px 24px; border-radius: 16px;
            background: #fff;
            border: 1px solid rgba(184,134,26,0.12);
            position: relative;
        }
        .step-num {
            width: 48px; height: 48px; border-radius: 50%;
            background: var(--cream-dark); border: 2px solid var(--gold-light);
            display: flex; align-items: center; justify-content: center;
            font-family: 'Lora', serif; font-size: 1.1rem; font-weight: 600; color: var(--gold);
        }
        .step-icon { font-size: 2rem; }
        .step h3 { margin-bottom: 6px; font-size: 1.05rem; }
        .step p { font-size: 0.875rem; }
        .step-connector {
            position: absolute; top: 50px; right: -16px;
            width: 32px; font-size: 1rem; color: var(--gold-light);
            display: flex; align-items: center; justify-content: center;
        }
        @media(max-width:700px){ .step-connector { display: none; } }

        /* ── Features ── */
        #features { background: #fff; }
        .features-grid {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 24px; margin-top: 48px;
        }
        @media(max-width:700px){ .features-grid { grid-template-columns: 1fr; } }
        .feat-card {
            padding: 28px; border-radius: 16px;
            border: 1px solid rgba(61,43,31,0.08);
            background: var(--cream);
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .feat-card:hover { box-shadow: 0 8px 32px rgba(61,43,31,0.08); transform: translateY(-2px); }
        .feat-card.featured {
            background: linear-gradient(135deg, var(--ink) 0%, #3D2010 100%);
            border-color: transparent; grid-column: span 2;
        }
        @media(max-width:700px){ .feat-card.featured { grid-column: span 1; } }
        .feat-card.featured h3, .feat-card.featured p { color: rgba(255,255,255,0.9); }
        .feat-card.featured .feat-icon { background: rgba(255,255,255,0.1); color: #fff; }
        .feat-icon {
            width: 44px; height: 44px; border-radius: 12px;
            background: rgba(139,38,53,0.08);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem; margin-bottom: 14px;
        }
        .feat-card h3 { font-size: 1.05rem; margin-bottom: 6px; }
        .feat-card p { font-size: 0.875rem; }
        .feat-tag {
            display: inline-block; margin-top: 10px;
            font-size: 0.7rem; font-weight: 600; letter-spacing: 0.05em;
            padding: 2px 10px; border-radius: 100px;
            background: rgba(184,134,26,0.15); color: var(--gold);
        }
        .feat-card.featured .feat-tag {
            background: rgba(255,255,255,0.15); color: rgba(255,255,255,0.8);
        }
        .feat-featured-content {
            display: flex; gap: 32px; align-items: center;
        }
        @media(max-width:700px){ .feat-featured-content { flex-direction: column; } }
        .feat-featured-text { flex: 1; }
        .feat-featured-demo {
            flex-shrink: 0;
            background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px; padding: 16px; min-width: 240px;
            font-family: 'Lora', serif; font-style: italic;
            font-size: 0.875rem; line-height: 1.8; color: rgba(255,255,255,0.85);
        }
        .feat-featured-demo-label {
            font-family: 'Inter', sans-serif; font-style: normal;
            font-size: 0.65rem; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase;
            color: var(--gold-light); margin-bottom: 8px;
        }

        /* ── Pricing ── */
        #pricing { background: var(--cream); text-align: center; }
        .pricing-grid {
            display: grid; grid-template-columns: repeat(3, 1fr);
            gap: 20px; margin-top: 48px; align-items: start;
        }
        @media(max-width:800px){ .pricing-grid { grid-template-columns: 1fr; } }
        .plan-card {
            border-radius: 20px; padding: 28px;
            background: #fff; border: 1px solid rgba(61,43,31,0.1);
            text-align: left; position: relative; overflow: hidden;
        }
        .plan-card.popular {
            border-color: var(--red); border-width: 2px;
            box-shadow: 0 8px 40px rgba(139,38,53,0.12);
        }
        .plan-badge {
            position: absolute; top: -1px; right: 20px;
            background: var(--red); color: #fff;
            font-size: 0.65rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase;
            padding: 4px 12px; border-radius: 0 0 10px 10px;
        }
        .plan-name {
            font-family: 'Inter', sans-serif;
            font-size: 0.7rem; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase;
            color: var(--gold); margin-bottom: 8px;
        }
        .plan-name.free { color: #A09080; }
        .plan-name.premium { color: var(--red); }
        .plan-price {
            font-family: 'Lora', serif; font-size: 2.4rem; font-weight: 700;
            color: var(--ink); line-height: 1; margin-bottom: 4px;
        }
        .plan-price span { font-size: 1rem; font-weight: 400; color: var(--brown-light); }
        .plan-period { font-size: 0.8rem; color: #A09080; margin-bottom: 20px; }
        .plan-divider { height: 1px; background: rgba(61,43,31,0.08); margin: 20px 0; }
        .plan-features { list-style: none; }
        .plan-features li {
            display: flex; align-items: flex-start; gap: 10px;
            font-size: 0.875rem; color: var(--brown-light);
            padding: 5px 0;
        }
        .plan-features li.no { color: #C0B0A0; }
        .check { color: #16A34A; font-size: 0.9rem; flex-shrink: 0; margin-top: 2px; }
        .cross { color: #C0B0A0; font-size: 0.9rem; flex-shrink: 0; margin-top: 2px; }
        .plan-cta {
            display: block; width: 100%; padding: 13px; border-radius: 10px;
            text-align: center; font-size: 0.875rem; font-weight: 600; text-decoration: none;
            margin-top: 24px; transition: all 0.15s; cursor: pointer; border: none;
        }
        .plan-cta.free-cta { background: var(--cream-dark); color: var(--brown); }
        .plan-cta.free-cta:hover { background: #E8DDCC; }
        .plan-cta.mini-cta { background: var(--red); color: #fff; }
        .plan-cta.mini-cta:hover { background: var(--red-light); }
        .plan-cta.premium-cta { background: var(--ink); color: #fff; }
        .plan-cta.premium-cta:hover { background: #3D2010; }
        .plan-highlight {
            font-size: 0.7rem; font-weight: 600; color: var(--gold);
            background: rgba(184,134,26,0.08); border-radius: 6px;
            padding: 6px 10px; margin-bottom: 12px; display: block;
        }

        /* ── Testimonial ── */
        #testimonial { background: #fff; text-align: center; }
        .testimonial-quote {
            font-family: 'Lora', Georgia, serif;
            font-size: clamp(1.2rem, 2.5vw, 1.6rem); font-style: italic;
            color: var(--ink); line-height: 1.7; max-width: 680px; margin: 0 auto 24px;
        }
        .testimonial-quote::before { content: '\201C'; color: var(--gold); }
        .testimonial-quote::after  { content: '\201D'; color: var(--gold); }
        .testimonial-author {
            font-size: 0.875rem; color: var(--brown-light);
        }
        .testimonial-author strong { color: var(--brown); font-weight: 600; }

        /* ── Final CTA ── */
        #cta {
            text-align: center; padding: 100px 24px;
            background: linear-gradient(135deg, var(--ink) 0%, #3D2010 100%);
            position: relative; overflow: hidden;
        }
        #cta::before {
            content: '';
            position: absolute; inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='30' cy='30' r='1' fill='%23ffffff' fill-opacity='0.04'/%3E%3C/svg%3E");
        }
        #cta .container { position: relative; }
        #cta h2 { color: #fff; margin-bottom: 16px; }
        #cta p { color: rgba(255,255,255,0.65); margin-bottom: 36px; font-size: 1.05rem; }
        .cta-buttons { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
        .btn-white {
            background: #fff; color: var(--red);
            padding: 14px 28px; border-radius: 10px;
            font-size: 1rem; font-weight: 600; text-decoration: none;
            display: inline-flex; align-items: center; gap: 8px;
            transition: all 0.15s;
        }
        .btn-white:hover { transform: translateY(-1px); box-shadow: 0 8px 24px rgba(0,0,0,0.2); }
        .btn-outline-white {
            background: transparent; color: rgba(255,255,255,0.8);
            border: 1.5px solid rgba(255,255,255,0.25);
            padding: 14px 28px; border-radius: 10px;
            font-size: 1rem; font-weight: 500; text-decoration: none;
            display: inline-flex; align-items: center; gap: 8px;
            transition: all 0.15s;
        }
        .btn-outline-white:hover { border-color: rgba(255,255,255,0.5); color: #fff; }

        /* ── Footer ── */
        footer {
            background: var(--ink); border-top: 1px solid rgba(255,255,255,0.06);
            padding: 32px 24px; text-align: center;
        }
        .footer-inner {
            max-width: 1100px; margin: 0 auto;
            display: flex; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: 16px;
        }
        .footer-logo {
            font-family: 'Lora', serif; font-size: 1.1rem; font-weight: 600;
            color: rgba(255,255,255,0.8); text-decoration: none;
            display: flex; align-items: center; gap: 8px;
        }
        .footer-note { font-size: 0.8rem; color: rgba(255,255,255,0.35); }
        .footer-links { display: flex; gap: 20px; }
        .footer-links a {
            font-size: 0.8rem; color: rgba(255,255,255,0.45); text-decoration: none;
            transition: color 0.15s;
        }
        .footer-links a:hover { color: rgba(255,255,255,0.8); }
    </style>
</head>
<body>

<!-- ── Navigation ── -->
<nav>
    <div class="nav-inner">
        <a href="/" class="logo">
            <svg class="logo-icon" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="14" cy="14" r="13" stroke="#8B2635" stroke-width="1.5"/>
                <path d="M14 7 C14 7, 10 10, 10 14 C10 18, 14 20, 14 20 C14 20, 18 18, 18 14 C18 10, 14 7, 14 7Z" fill="#8B2635" opacity="0.15"/>
                <path d="M14 5 L14 23 M7 14 L21 14" stroke="#8B2635" stroke-width="1" stroke-linecap="round" opacity="0.3"/>
                <circle cx="14" cy="14" r="3" fill="#8B2635"/>
                <circle cx="14" cy="7"  r="1.5" fill="#B8861A"/>
                <circle cx="14" cy="21" r="1.5" fill="#B8861A"/>
                <circle cx="7"  cy="14" r="1.5" fill="#B8861A"/>
                <circle cx="21" cy="14" r="1.5" fill="#B8861A"/>
            </svg>
            Gia Phong
        </a>
        <div class="nav-links">
            <a href="#how"      class="btn-ghost" style="display:none" id="nav-how">Cách dùng</a>
            <a href="#pricing"  class="btn-ghost">Bảng giá</a>
            @auth
                <a href="{{ url('/dashboard') }}" class="btn-primary">Vào ứng dụng →</a>
            @else
                <a href="{{ route('login') }}"    class="btn-ghost">Đăng nhập</a>
                @if (Route::has('register'))
                <a href="{{ route('register') }}" class="btn-primary">Dùng miễn phí</a>
                @endif
            @endauth
        </div>
    </div>
</nav>

<!-- ── Hero ── -->
<section id="hero">
    <div class="hero-pattern"></div>
    <div class="container">
        <div class="hero-badge">
            <span class="hero-badge-dot"></span>
            Thông báo qua Zalo — không cần cài app mới
        </div>
        <h1 class="hero-title font-serif">
            Đừng để lỡ <em>ngày giỗ</em><br>ông bà, cha mẹ
        </h1>
        <p class="hero-sub">
            Setup một lần trên web, hệ thống tự động nhắc cả gia đình qua Zalo đúng ngày — dù bạn bận đến đâu.
        </p>
        <div class="hero-cta">
            @auth
                <a href="{{ url('/dashboard') }}" class="btn-lg btn-red">
                    Vào ứng dụng
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                </a>
            @else
                <a href="{{ route('register') }}" class="btn-lg btn-red">
                    Bắt đầu miễn phí
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                </a>
                <a href="#how" class="btn-lg btn-outline">Xem cách dùng</a>
            @endauth
        </div>
        <p class="hero-note">Miễn phí mãi mãi cho gia đình nhỏ · Không cần thẻ tín dụng</p>

        <!-- Phone mockup -->
        <div class="phone-wrap">
            <div class="phone-glow"></div>
            <div class="phone-outer">
                <div class="phone-screen">
                    <div class="phone-notch"><div class="phone-camera"></div></div>
                    <div class="zns-card">
                        <div class="zns-top">
                            <div class="zns-avatar">🏮</div>
                            <div>
                                <div class="zns-sender">Gia Phong · Gia đình Nguyễn</div>
                                <div class="zns-time">Hôm nay, 7:00 SA</div>
                            </div>
                        </div>
                        <div class="zns-title">📅 Nhắc ngày giỗ — Ngày mai</div>
                        <div class="zns-body">
                            Ngày mai là ngày giỗ <strong>Ông nội Nguyễn Văn An</strong><br>
                            (12 tháng Giêng âm lịch — 10/02/2025)
                        </div>
                    </div>
                    <div class="zns-footer">
                        <strong>Gợi ý chuẩn bị</strong>
                        Hương, hoa, mâm cơm cúng · Mời thêm người thân
                    </div>
                    <div class="phone-pad"></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ── Trust bar ── -->
<section id="trust" style="padding:28px 24px">
    <div class="trust-inner">
        <div class="trust-item"><span class="trust-icon">🔔</span> Nhắc tự động qua Zalo</div>
        <div class="trust-item"><span class="trust-icon">🌙</span> Âm lịch chính xác</div>
        <div class="trust-item"><span class="trust-icon">🤖</span> AI soạn văn khấn</div>
        <div class="trust-item"><span class="trust-icon">🌳</span> Lưu gia phả gia đình</div>
        <div class="trust-item"><span class="trust-icon">🇻🇳</span> Làm cho người Việt</div>
    </div>
</section>

<!-- ── How it works ── -->
<section id="how">
    <div class="container">
        <div class="ornament">
            <div class="ornament-line"></div>
            <div class="ornament-diamond"></div>
            <div class="ornament-line"></div>
        </div>
        <div class="section-tag">Cách hoạt động</div>
        <h2 class="font-serif" style="margin-bottom:8px">Setup 5 phút, dùng mãi mãi</h2>
        <p style="max-width:480px;margin:0 auto">Không cần kỹ thuật. Nhập thông tin, hệ thống lo phần còn lại.</p>

        <div class="steps">
            <div class="step">
                <div class="step-num">1</div>
                <div class="step-icon">📖</div>
                <div>
                    <h3>Nhập ngày giỗ</h3>
                    <p>Thêm tên, ngày âm lịch và quan hệ. Hệ thống tự quy đổi sang dương lịch mỗi năm.</p>
                </div>
                <div class="step-connector">→</div>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <div class="step-icon">👨‍👩‍👧‍👦</div>
                <div>
                    <h3>Thêm người nhận</h3>
                    <p>Thêm số điện thoại Zalo của bố mẹ, anh chị em. Họ không cần cài thêm app gì.</p>
                </div>
                <div class="step-connector">→</div>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <div class="step-icon">🔔</div>
                <div>
                    <h3>Nhận nhắc tự động</h3>
                    <p>7 giờ sáng đúng ngày, mọi người nhận tin Zalo. Bạn không cần làm gì thêm.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ── Features ── -->
<section id="features">
    <div class="container">
        <div class="ornament">
            <div class="ornament-line"></div>
            <div class="ornament-diamond"></div>
            <div class="ornament-line"></div>
        </div>
        <div class="section-tag">Tính năng</div>
        <h2 class="font-serif" style="margin-bottom:8px">Đủ dùng cho mọi gia đình Việt</h2>
        <p style="max-width:480px;margin:0 auto 0">Từ gia đình đơn giản đến dòng họ đông người.</p>

        <div class="features-grid">
            <!-- Featured: AI Prayer -->
            <div class="feat-card featured">
                <div class="feat-featured-content">
                    <div class="feat-featured-text">
                        <div class="feat-icon">🤖</div>
                        <h3 style="font-size:1.3rem;margin-bottom:8px">AI soạn văn khấn</h3>
                        <p style="margin-bottom:12px;line-height:1.7">
                            Không biết văn khấn đúng? Chat với AI, nhập tên người mất và địa chỉ — AI soạn đầy đủ, đúng nghi thức truyền thống Việt Nam.
                        </p>
                        <span class="feat-tag">Gói Mini & Premium</span>
                    </div>
                    <div class="feat-featured-demo">
                        <div class="feat-featured-demo-label">Ví dụ văn khấn AI soạn</div>
                        "Nam mô A Di Đà Phật!<br>
                        Con lạy chín phương Trời, mười phương Chư Phật...<br>
                        Hôm nay là ngày giỗ thứ ba mươi của
                        Cụ ông Nguyễn Văn An, thọ chín mươi hai tuổi..."
                    </div>
                </div>
            </div>

            <div class="feat-card">
                <div class="feat-icon">🌙</div>
                <h3>Âm lịch chính xác</h3>
                <p>Tự động quy đổi ngày âm sang dương lịch mỗi năm, kể cả năm nhuận âm lịch. Không bao giờ sai.</p>
            </div>

            <div class="feat-card">
                <div class="feat-icon">🌳</div>
                <h3>Cây gia phả trực quan</h3>
                <p>Xây dựng và lưu trữ gia phả dạng cây. Gắn ngày giỗ trực tiếp cho từng thành viên đã mất.</p>
            </div>

            <div class="feat-card">
                <div class="feat-icon">🏮</div>
                <h3>Nhắc Rằm & Mùng 1</h3>
                <p>Không chỉ ngày giỗ — nhắc cả Rằm và Mùng 1 để cả nhà chuẩn bị hương hoa đúng lúc.</p>
                <span class="feat-tag">Gói Premium</span>
            </div>

            <div class="feat-card">
                <div class="feat-icon">💬</div>
                <h3>Chatbot gia đình</h3>
                <p>Chia sẻ link chatbot cho cả nhà. Ai cũng hỏi được lịch giỗ, không cần đăng ký tài khoản.</p>
                <span class="feat-tag">Gói Premium</span>
            </div>
        </div>
    </div>
</section>

<!-- ── Pricing ── -->
<section id="pricing">
    <div class="container">
        <div class="ornament">
            <div class="ornament-line"></div>
            <div class="ornament-diamond"></div>
            <div class="ornament-line"></div>
        </div>
        <div class="section-tag">Bảng giá</div>
        <h2 class="font-serif" style="margin-bottom:8px">Chọn gói phù hợp gia đình</h2>
        <p style="max-width:440px;margin:0 auto">Tất cả gói đều có ngày giỗ và gia phả không giới hạn.</p>

        <div class="pricing-grid">
            <!-- Free -->
            <div class="plan-card">
                <div class="plan-name free">Free</div>
                <div class="plan-price">0đ</div>
                <div class="plan-period">Mãi mãi</div>
                <ul class="plan-features">
                    <li><span class="check">✓</span> Ngày giỗ & gia phả không giới hạn</li>
                    <li><span class="check">✓</span> 1 người nhận ZNS Zalo</li>
                    <li><span class="check">✓</span> 1 mốc nhắc / ngày giỗ</li>
                    <li><span class="check">✓</span> Tối đa 20 tin ZNS / năm</li>
                    <li><span class="check">✓</span> Chat AI hỏi lịch giỗ</li>
                    <li class="no"><span class="cross">✗</span> AI soạn văn khấn</li>
                    <li class="no"><span class="cross">✗</span> Nhắc Rằm & Mùng 1</li>
                    <li class="no"><span class="cross">✗</span> Chia sẻ chatbot gia đình</li>
                </ul>
                @auth
                    <a href="{{ url('/dashboard') }}" class="plan-cta free-cta">Vào ứng dụng</a>
                @else
                    <a href="{{ route('register') }}" class="plan-cta free-cta">Bắt đầu miễn phí</a>
                @endauth
            </div>

            <!-- Mini -->
            <div class="plan-card popular">
                <div class="plan-badge">Phổ biến nhất</div>
                <div class="plan-name">Mini</div>
                <div class="plan-price">100.000đ <span>/ năm</span></div>
                <div class="plan-period">≈ 8.300đ / tháng</div>
                <span class="plan-highlight">💡 Đủ dùng cho hầu hết gia đình</span>
                <ul class="plan-features">
                    <li><span class="check">✓</span> Tất cả tính năng Free</li>
                    <li><span class="check">✓</span> 3 người nhận ZNS Zalo</li>
                    <li><span class="check">✓</span> 2 mốc nhắc / ngày giỗ</li>
                    <li><span class="check">✓</span> Nhắc Rằm & Mùng 1</li>
                    <li><span class="check">✓</span> Tối đa 130 tin ZNS / năm</li>
                    <li><span class="check">✓</span> AI soạn văn khấn (10 lần/tháng)</li>
                    <li><span class="check">✓</span> AI tạo/sửa ngày giỗ qua chat</li>
                    <li class="no"><span class="cross">✗</span> Chia sẻ chatbot gia đình</li>
                </ul>
                <form method="POST" action="{{ route('payment.create') }}">
                    @csrf
                    <input type="hidden" name="plan" value="mini">
                    <button type="submit" class="plan-cta mini-cta">Mua Mini — 100.000đ/năm</button>
                </form>
            </div>

            <!-- Premium -->
            <div class="plan-card">
                <div class="plan-name premium">Premium</div>
                <div class="plan-price">200.000đ <span>/ năm</span></div>
                <div class="plan-period">≈ 16.700đ / tháng</div>
                <ul class="plan-features">
                    <li><span class="check">✓</span> Tất cả tính năng Mini</li>
                    <li><span class="check">✓</span> <strong>10</strong> người nhận ZNS Zalo</li>
                    <li><span class="check">✓</span> <strong>5</strong> mốc nhắc / ngày giỗ</li>
                    <li><span class="check">✓</span> Tối đa 450 tin ZNS / năm</li>
                    <li><span class="check">✓</span> AI văn khấn không giới hạn</li>
                    <li><span class="check">✓</span> Chia sẻ chatbot cho cả nhà</li>
                    <li><span class="check">✓</span> Upload tài liệu gia đình (RAG)</li>
                    <li><span class="check">✓</span> Nhập ngày giỗ từ ảnh (AI)</li>
                </ul>
                <form method="POST" action="{{ route('payment.create') }}">
                    @csrf
                    <input type="hidden" name="plan" value="premium">
                    <button type="submit" class="plan-cta premium-cta">Mua Premium — 200.000đ/năm</button>
                </form>
            </div>
        </div>

        <p style="margin-top:24px;font-size:0.8rem;color:#A09080;text-align:center">
            Thanh toán qua chuyển khoản ngân hàng · Kích hoạt tự động trong vài phút
        </p>
    </div>
</section>

<!-- ── Testimonial ── -->
<section id="testimonial" style="background:#fff;padding:80px 24px">
    <div class="container" style="max-width:800px">
        <div class="ornament">
            <div class="ornament-line"></div>
            <div class="ornament-diamond"></div>
            <div class="ornament-line"></div>
        </div>
        <blockquote class="testimonial-quote font-serif">
            Từ hồi có Gia Phong, ba mẹ mình ở quê không còn bao giờ quên ngày giỗ ông bà nữa. Tin Zalo gửi tự động, không cần mình nhớ hay nhắc.
        </blockquote>
        <div class="testimonial-author">
            <strong>Nguyễn Minh Tuấn</strong> · Kỹ sư phần mềm, Hà Nội
        </div>
    </div>
</section>

<!-- ── Final CTA ── -->
<section id="cta" style="padding:100px 24px">
    <div class="container" style="max-width:600px">
        <h2 class="font-serif" style="color:#fff;margin-bottom:16px">Cả nhà cùng nhớ, cùng giữ nét đẹp truyền thống</h2>
        <p style="color:rgba(255,255,255,0.65);margin-bottom:36px;font-size:1.05rem">
            Setup miễn phí trong 5 phút. Không cần thẻ tín dụng.
        </p>
        <div class="cta-buttons">
            @auth
                <a href="{{ url('/dashboard') }}" class="btn-white">Vào ứng dụng →</a>
            @else
                <a href="{{ route('register') }}" class="btn-white">Bắt đầu miễn phí →</a>
                <a href="{{ route('login') }}" class="btn-outline-white">Đăng nhập</a>
            @endauth
        </div>
    </div>
</section>

<!-- ── Footer ── -->
<footer>
    <div class="footer-inner">
        <a href="/" class="footer-logo">
            🏮 Gia Phong
        </a>
        <div class="footer-links">
            <a href="#pricing">Bảng giá</a>
            <a href="mailto:support@giaphong.vn">Hỗ trợ</a>
        </div>
        <p class="footer-note">© {{ date('Y') }} Gia Phong · Làm với ❤️ cho gia đình Việt</p>
    </div>
</footer>

<script>
    // Reveal nav links on scroll
    const navHow = document.getElementById('nav-how');
    window.addEventListener('scroll', () => {
        if (navHow) navHow.style.display = window.scrollY > 100 ? 'block' : 'none';
    });

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(a => {
        a.addEventListener('click', e => {
            const target = document.querySelector(a.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
</script>

</body>
</html>
