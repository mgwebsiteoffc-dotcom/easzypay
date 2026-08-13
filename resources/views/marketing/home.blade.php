<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="QuickPay — Accept payments on Shopify Basic with Stripe. Apple Pay, Google Pay, and international cards. No Shopify Payments needed.">
    <meta name="keywords" content="Shopify Basic payment, Stripe Shopify, Apple Pay Shopify, checkout bypass, multi-currency checkout">
    <meta property="og:title" content="QuickPay — Stripe Checkout for Shopify Basic Stores">
    <meta property="og:description" content="Accept Apple Pay, Google Pay, and international cards on Shopify Basic. Automatic order sync. 14-day free trial.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ config('app.url') }}">
    <title>QuickPay — Stripe Checkout for Shopify Basic Stores</title>
    <style>
        /* ============================================================
           GLOBAL
        ============================================================ */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary:  #667eea;
            --primary-dk:#5a67d8;
            --purple:   #764ba2;
            --success:  #10b981;
            --dark:     #0f0c29;
            --gray-50:  #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-500: #6b7280;
            --gray-700: #374151;
            --gray-900: #111827;
        }

        html {
            scroll-behavior: smooth;
            font-size: 16px;
            -webkit-font-smoothing: antialiased;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto,
                         Oxygen, Ubuntu, sans-serif;
            color: var(--gray-900);
            background: white;
            overflow-x: hidden;
        }

        a { text-decoration: none; color: inherit; }

        /* ============================================================
           NAV
        ============================================================ */
        .nav {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: 68px;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 5%;
            z-index: 1000;
            box-shadow: 0 1px 8px rgba(0,0,0,0.06);
        }

        .nav-logo {
            font-size: 22px;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary) 0%, var(--purple) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 36px;
            list-style: none;
        }

        .nav-links a {
            font-size: 15px;
            font-weight: 500;
            color: var(--gray-700);
            transition: color 0.2s;
        }

        .nav-links a:hover { color: var(--primary); }

        .nav-cta {
            display: flex;
            gap: 12px;
        }

        .btn-nav-outline {
            padding: 9px 20px;
            border: 1.5px solid var(--primary);
            border-radius: 8px;
            color: var(--primary);
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s;
        }

        .btn-nav-outline:hover {
            background: var(--primary);
            color: white;
        }

        .btn-nav-primary {
            padding: 9px 20px;
            background: linear-gradient(135deg, var(--primary), var(--purple));
            color: white;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s;
        }

        .btn-nav-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(102,126,234,0.35);
        }

        /* ============================================================
           HERO
        ============================================================ */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);
            position: relative;
            overflow: hidden;
            padding-top: 68px;
        }

        .hero::before {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(102,126,234,0.3) 0%, transparent 70%);
            top: -100px;
            right: -100px;
            border-radius: 50%;
        }

        .hero::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(118,75,162,0.25) 0%, transparent 70%);
            bottom: -50px;
            left: -50px;
            border-radius: 50%;
        }

        .hero-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 80px 5%;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 80px;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        @media (max-width: 900px) {
            .hero-content { grid-template-columns: 1fr; gap: 48px; text-align: center; }
            .hero-visual { display: none; }
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(102,126,234,0.15);
            border: 1px solid rgba(102,126,234,0.3);
            color: #a5b4fc;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 24px;
        }

        .hero h1 {
            font-size: clamp(2.2rem, 4vw, 3.5rem);
            font-weight: 900;
            color: white;
            line-height: 1.15;
            margin-bottom: 24px;
            letter-spacing: -0.02em;
        }

        .hero h1 span {
            background: linear-gradient(135deg, #a5b4fc, #e879f9);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-desc {
            font-size: 18px;
            color: rgba(255,255,255,0.75);
            line-height: 1.7;
            margin-bottom: 36px;
        }

        .hero-actions {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }

        .btn-hero-primary {
            padding: 16px 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            font-weight: 700;
            font-size: 16px;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }

        .btn-hero-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(102,126,234,0.5);
        }

        .btn-hero-outline {
            padding: 16px 32px;
            border: 2px solid rgba(255,255,255,0.3);
            color: white;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s;
        }

        .btn-hero-outline:hover {
            border-color: white;
            background: rgba(255,255,255,0.08);
        }

        .hero-trust {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 24px;
            color: rgba(255,255,255,0.5);
            font-size: 13px;
        }

        /* Hero Visual - Checkout Preview */
        .hero-visual {
            position: relative;
        }

        .checkout-preview {
            background: white;
            border-radius: 20px;
            box-shadow: 0 32px 80px rgba(0,0,0,0.5);
            padding: 28px;
            transform: perspective(1000px) rotateY(-8deg) rotateX(4deg);
            transition: transform 0.5s ease;
        }

        .checkout-preview:hover {
            transform: perspective(1000px) rotateY(-4deg) rotateX(2deg);
        }

        .cp-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid #f3f4f6;
        }

        .cp-logo {
            font-weight: 800;
            font-size: 16px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .cp-secure {
            font-size: 12px;
            color: #10b981;
            font-weight: 600;
        }

        .cp-wallets {
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
        }

        .cp-wallet {
            flex: 1;
            padding: 10px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            text-align: center;
        }

        .cp-apple { background: #000; color: white; }
        .cp-google { background: #4285f4; color: white; }
        .cp-link   { background: #635bff; color: white; }

        .cp-divider {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 14px 0;
            font-size: 11px;
            color: #9ca3af;
        }

        .cp-divider::before,
        .cp-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e5e7eb;
        }

        .cp-card-mock {
            background: #f9fafb;
            border-radius: 10px;
            padding: 14px;
        }

        .cp-card-row {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 10px 12px;
            font-size: 13px;
            color: #9ca3af;
            margin-bottom: 8px;
        }

        .cp-pay-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            margin-top: 12px;
        }

        .cp-badges {
            display: flex;
            gap: 6px;
            justify-content: center;
            margin-top: 12px;
        }

        .cp-badge {
            font-size: 10px;
            color: #9ca3af;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 3px 8px;
        }

        /* Floating badges on visual */
        .float-badge {
            position: absolute;
            background: white;
            border-radius: 12px;
            padding: 10px 16px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .fb-1 { top: -20px; right: -20px; }
        .fb-2 { bottom: 20px; left: -30px; }

        /* ============================================================
           SECTION SHARED
        ============================================================ */
        section {
            padding: 100px 5%;
        }

        .section-inner {
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-tag {
            display: inline-block;
            background: #ede9fe;
            color: var(--primary);
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 16px;
        }

        .section-title {
            font-size: clamp(1.8rem, 3vw, 2.8rem);
            font-weight: 900;
            color: var(--gray-900);
            line-height: 1.2;
            margin-bottom: 16px;
            letter-spacing: -0.02em;
        }

        .section-subtitle {
            font-size: 18px;
            color: var(--gray-500);
            line-height: 1.7;
            max-width: 600px;
        }

        /* ============================================================
           HOW IT WORKS
        ============================================================ */
        .how-it-works {
            background: var(--gray-50);
        }

        .steps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 32px;
            margin-top: 56px;
        }

        .step-card {
            background: white;
            border-radius: 16px;
            padding: 32px;
            text-align: center;
            border: 1px solid var(--gray-200);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            position: relative;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .step-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(0,0,0,0.1);
        }

        .step-num {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--primary), var(--purple));
            color: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 800;
            margin: 0 auto 20px;
        }

        .step-icon { font-size: 32px; margin-bottom: 16px; }
        .step-title { font-size: 16px; font-weight: 700; margin-bottom: 10px; }
        .step-desc  { font-size: 14px; color: var(--gray-500); line-height: 1.6; }

        /* Arrow between steps */
        .steps-grid .step-card:not(:last-child)::after {
            content: '→';
            position: absolute;
            top: 50%;
            right: -24px;
            transform: translateY(-50%);
            font-size: 24px;
            color: var(--gray-300);
            z-index: 1;
        }

        @media (max-width: 768px) {
            .steps-grid .step-card::after { display: none; }
        }

        /* ============================================================
           FEATURES
        ============================================================ */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
            margin-top: 56px;
        }

        .feature-card {
            padding: 32px;
            border-radius: 16px;
            border: 1px solid var(--gray-200);
            background: white;
            transition: all 0.2s;
        }

        .feature-card:hover {
            border-color: var(--primary);
            box-shadow: 0 8px 24px rgba(102,126,234,0.12);
        }

        .feature-icon {
            font-size: 36px;
            margin-bottom: 16px;
            display: block;
        }

        .feature-title {
            font-size: 17px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .feature-desc {
            font-size: 14px;
            color: var(--gray-500);
            line-height: 1.7;
        }

        /* ============================================================
           PAYMENT METHODS
        ============================================================ */
        .payments-section {
            background: linear-gradient(135deg, #0f0c29 0%, #302b63 100%);
        }

        .payments-section .section-title,
        .payments-section .section-subtitle,
        .payments-section .section-tag { color: white; }
        .payments-section .section-tag { background: rgba(255,255,255,0.1); color: #a5b4fc; }
        .payments-section .section-subtitle { color: rgba(255,255,255,0.7); }

        .payment-methods-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            margin-top: 48px;
        }

        .payment-method-card {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 24px 16px;
            text-align: center;
            transition: all 0.2s;
        }

        .payment-method-card:hover {
            background: rgba(255,255,255,0.15);
            border-color: rgba(255,255,255,0.25);
            transform: translateY(-4px);
        }

        .pm-icon  { font-size: 36px; display: block; margin-bottom: 10px; }
        .pm-name  { font-size: 14px; font-weight: 700; color: white; }
        .pm-desc  { font-size: 12px; color: rgba(255,255,255,0.5); margin-top: 4px; }

        /* ============================================================
           PRICING
        ============================================================ */
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 24px;
            margin-top: 56px;
        }

        .pricing-card {
            border-radius: 20px;
            padding: 36px;
            border: 1px solid var(--gray-200);
            background: white;
            position: relative;
            transition: all 0.2s;
        }

        .pricing-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 40px rgba(0,0,0,0.1);
        }

        .pricing-card.featured {
            background: linear-gradient(135deg, #0f0c29 0%, #302b63 100%);
            border-color: transparent;
            color: white;
        }

        .plan-popular {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 4px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .plan-name {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--primary);
            margin-bottom: 16px;
        }

        .pricing-card.featured .plan-name { color: #a5b4fc; }

        .plan-price {
            font-size: 48px;
            font-weight: 900;
            line-height: 1;
            margin-bottom: 6px;
        }

        .pricing-card.featured .plan-price { color: white; }

        .plan-period {
            font-size: 14px;
            color: var(--gray-400);
            margin-bottom: 8px;
        }

        .pricing-card.featured .plan-period { color: rgba(255,255,255,0.5); }

        .plan-fee {
            font-size: 13px;
            color: var(--gray-500);
            margin-bottom: 24px;
        }

        .pricing-card.featured .plan-fee { color: rgba(255,255,255,0.6); }

        .plan-features {
            list-style: none;
            margin-bottom: 32px;
        }

        .plan-features li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 8px 0;
            border-bottom: 1px solid var(--gray-100);
            font-size: 14px;
        }

        .pricing-card.featured .plan-features li {
            border-color: rgba(255,255,255,0.08);
            color: rgba(255,255,255,0.85);
        }

        .plan-features li:last-child { border-bottom: none; }

        .plan-features li::before {
            content: '✓';
            color: var(--success);
            font-weight: 800;
            font-size: 13px;
            margin-top: 1px;
            flex-shrink: 0;
        }

        .btn-plan {
            display: block;
            width: 100%;
            padding: 14px;
            border-radius: 10px;
            text-align: center;
            font-weight: 700;
            font-size: 15px;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
        }

        .btn-plan-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .btn-plan-outline:hover {
            background: var(--primary);
            color: white;
        }

        .btn-plan-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-plan-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(102,126,234,0.4);
        }

        /* ============================================================
           TESTIMONIALS
        ============================================================ */
        .testimonials {
            background: var(--gray-50);
        }

        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
            margin-top: 48px;
        }

        .testimonial-card {
            background: white;
            border-radius: 16px;
            padding: 28px;
            border: 1px solid var(--gray-200);
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }

        .testimonial-stars {
            color: #f59e0b;
            font-size: 16px;
            margin-bottom: 14px;
        }

        .testimonial-text {
            font-size: 15px;
            color: var(--gray-700);
            line-height: 1.7;
            margin-bottom: 20px;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .author-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--purple));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 16px;
        }

        .author-name  { font-weight: 700; font-size: 14px; }
        .author-store { font-size: 12px; color: var(--gray-400); margin-top: 2px; }

        /* ============================================================
           STATS
        ============================================================ */
        .stats-section {
            background: linear-gradient(135deg, var(--primary), var(--purple));
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 40px;
            text-align: center;
        }

        .stat-item { color: white; }
        .stat-number { font-size: 48px; font-weight: 900; line-height: 1; }
        .stat-label  { font-size: 15px; opacity: 0.8; margin-top: 8px; font-weight: 500; }

        /* ============================================================
           FAQ
        ============================================================ */
        .faq-list {
            max-width: 800px;
            margin: 48px auto 0;
        }

        .faq-item {
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            margin-bottom: 12px;
            overflow: hidden;
        }

        .faq-q {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            cursor: pointer;
            font-weight: 600;
            font-size: 15px;
            color: var(--gray-900);
            background: white;
            border: none;
            width: 100%;
            text-align: left;
            transition: background 0.15s;
        }

        .faq-q:hover { background: var(--gray-50); }

        .faq-icon {
            font-size: 20px;
            color: var(--primary);
            transition: transform 0.2s;
            flex-shrink: 0;
        }

        .faq-a {
            padding: 0 24px;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease, padding 0.3s ease;
            font-size: 15px;
            color: var(--gray-600);
            line-height: 1.7;
            background: white;
        }

        .faq-item.open .faq-a {
            max-height: 300px;
            padding: 0 24px 20px;
        }

        .faq-item.open .faq-icon { transform: rotate(45deg); }

        /* ============================================================
           CTA SECTION
        ============================================================ */
        .cta-section {
            background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);
            text-align: center;
        }

        .cta-section .section-title { color: white; }

        .cta-desc {
            font-size: 18px;
            color: rgba(255,255,255,0.7);
            margin: 16px auto 40px;
            max-width: 600px;
            line-height: 1.7;
        }

        .cta-actions {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* ============================================================
           FOOTER
        ============================================================ */
        footer {
            background: #0a0a0a;
            color: rgba(255,255,255,0.6);
            padding: 64px 5% 32px;
        }

        .footer-inner {
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 48px;
            margin-bottom: 48px;
        }

        @media (max-width: 768px) {
            .footer-grid { grid-template-columns: 1fr 1fr; gap: 32px; }
            .footer-brand { grid-column: span 2; }
        }

        .footer-logo {
            font-size: 20px;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 12px;
            display: block;
        }

        .footer-desc {
            font-size: 14px;
            line-height: 1.7;
            color: rgba(255,255,255,0.4);
        }

        .footer-heading {
            font-size: 13px;
            font-weight: 700;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 16px;
        }

        .footer-links { list-style: none; }
        .footer-links li { margin-bottom: 10px; }
        .footer-links a {
            font-size: 14px;
            color: rgba(255,255,255,0.5);
            transition: color 0.2s;
        }
        .footer-links a:hover { color: white; }

        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.08);
            padding-top: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .footer-bottom p { font-size: 13px; }

        .footer-badges {
            display: flex;
            gap: 12px;
        }

        .f-badge {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 6px;
            padding: 4px 10px;
            font-size: 12px;
            color: rgba(255,255,255,0.5);
        }

        /* ============================================================
           RESPONSIVE
        ============================================================ */
        @media (max-width: 768px) {
            .nav-links { display: none; }
            section { padding: 64px 5%; }
            .hero-actions { justify-content: center; }
            .hero-trust    { justify-content: center; }
            .pricing-grid { grid-template-columns: 1fr; }
            .stats-grid   { grid-template-columns: repeat(2, 1fr); }
        }

        /* Scroll animation */
        .fade-in {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.6s ease, transform 0.6s ease;
        }

        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>
<body>

<!-- ================================================================
     NAVIGATION
================================================================ -->
<nav class="nav">
    <a href="/" class="nav-logo">⚡ QuickPay</a>
    <ul class="nav-links">
        <li><a href="#how-it-works">How It Works</a></li>
        <li><a href="#features">Features</a></li>
        <li><a href="#pricing">Pricing</a></li>
        <li><a href="#faq">FAQ</a></li>
    </ul>
    <div class="nav-cta">
        <a href="{{ route('tenant.login') }}" class="btn-nav-outline">Sign In</a>
        <a href="{{ route('tenant.register') }}" class="btn-nav-primary">Start Free Trial</a>
    </div>
</nav>

<!-- ================================================================
     HERO
================================================================ -->
<section class="hero">
    <div class="hero-content">
        <div class="hero-text">
            <div class="hero-badge">
                🚀 Trusted by 500+ Shopify stores
            </div>

            <h1>
                Accept <span>Stripe Payments</span>
                on Shopify Basic
            </h1>

            <p class="hero-desc">
                Use Apple Pay, Google Pay, and international cards
                on Shopify Basic plans — without Shopify Payments.
                Auto-sync orders. Multi-currency. Zero code needed.
            </p>

            <div class="hero-actions">
                <a href="{{ route('tenant.register') }}" class="btn-hero-primary">
                    Start 14-Day Free Trial →
                </a>
                <a href="#how-it-works" class="btn-hero-outline">
                    See How It Works
                </a>
            </div>

            <div class="hero-trust">
                ✅ No credit card required &nbsp;·&nbsp;
                ✅ 5-min setup &nbsp;·&nbsp;
                ✅ Cancel anytime
            </div>
        </div>

        <div class="hero-visual">
            <!-- Floating badges -->
            <div class="float-badge fb-1">
                ✅ Order synced to Shopify!
            </div>
            <div class="float-badge fb-2">
                🍎 Apple Pay detected
            </div>

            <!-- Checkout Preview -->
            <div class="checkout-preview">
                <div class="cp-header">
                    <span class="cp-logo">⚡ QuickPay</span>
                    <span class="cp-secure">🔒 SSL Secure</span>
                </div>

                <div class="cp-wallets">
                    <div class="cp-wallet cp-apple">🍎 Apple Pay</div>
                    <div class="cp-wallet cp-google">G Pay</div>
                    <div class="cp-wallet cp-link">🔗 Link</div>
                </div>

                <div class="cp-divider">or pay with card</div>

                <div class="cp-card-mock">
                    <div class="cp-card-row">Card Number</div>
                    <div class="cp-card-row">MM / YY &nbsp;&nbsp;&nbsp; CVC</div>
                    <button class="cp-pay-btn">🔒 Pay $99.00 Securely</button>
                </div>

                <div class="cp-badges">
                    <span class="cp-badge">💳 All Cards</span>
                    <span class="cp-badge">🌍 30+ Currencies</span>
                    <span class="cp-badge">🛡️ PCI DSS</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ================================================================
     STATS
================================================================ -->
<section class="stats-section" style="padding:64px 5%;">
    <div class="section-inner">
        <div class="stats-grid fade-in">
            <div class="stat-item">
                <div class="stat-number">500+</div>
                <div class="stat-label">Shopify Stores</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">$2M+</div>
                <div class="stat-label">Payments Processed</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">30+</div>
                <div class="stat-label">Currencies Supported</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">99.9%</div>
                <div class="stat-label">Uptime SLA</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">5 min</div>
                <div class="stat-label">Average Setup Time</div>
            </div>
        </div>
    </div>
</section>

<!-- ================================================================
     HOW IT WORKS
================================================================ -->
<section id="how-it-works" class="how-it-works">
    <div class="section-inner">
        <div class="fade-in" style="text-align:center;">
            <span class="section-tag">Simple Process</span>
            <h2 class="section-title">Set up in 5 minutes. Sell in seconds.</h2>
            <p class="section-subtitle" style="margin:0 auto;">
                QuickPay automatically installs on your Shopify store
                and handles everything from checkout to order sync.
            </p>
        </div>

        <div class="steps-grid fade-in">
            <div class="step-card">
                <div class="step-num">1</div>
                <div class="step-icon">🏪</div>
                <div class="step-title">Connect Your Store</div>
                <div class="step-desc">
                    Install QuickPay from the link. We connect to
                    your Shopify store via OAuth in seconds.
                </div>
            </div>

            <div class="step-card">
                <div class="step-num">2</div>
                <div class="step-icon">⚡</div>
                <div class="step-title">Auto-Install Button</div>
                <div class="step-desc">
                    We automatically inject the "Buy Now — Secure Checkout"
                    button into your product pages. No coding needed.
                </div>
            </div>

            <div class="step-card">
                <div class="step-num">3</div>
                <div class="step-icon">💳</div>
                <div class="step-title">Customer Pays via Stripe</div>
                <div class="step-desc">
                    Customer is redirected to our beautiful checkout.
                    Apple Pay, Google Pay, and cards — all supported.
                </div>
            </div>

            <div class="step-card">
                <div class="step-num">4</div>
                <div class="step-icon">📦</div>
                <div class="step-title">Order Auto-Created</div>
                <div class="step-desc">
                    After payment, we automatically create the order
                    in Shopify, send confirmation emails, and reduce inventory.
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ================================================================
     FEATURES
================================================================ -->
<section id="features">
    <div class="section-inner">
        <div class="fade-in">
            <span class="section-tag">Features</span>
            <h2 class="section-title">Everything you need to sell</h2>
            <p class="section-subtitle">
                Built for Shopify Basic merchants who need professional
                payment capabilities without upgrading plans.
            </p>
        </div>

        <div class="features-grid fade-in">
            @foreach([
                ['🍎', 'Apple Pay', 'One-touch checkout for Safari users on iPhone, iPad and Mac. Highest converting mobile payment.'],
                ['🤖', 'Google Pay', 'Instant checkout for Android and Chrome users. Auto-detected by Stripe.'],
                ['🔗', 'Link by Stripe', 'Saved card checkout for returning Stripe users. One-click for millions of customers.'],
                ['🌍', 'Multi-Currency', 'Auto-detect customer location and show prices in local currency. USD, EUR, GBP, AED, INR and 30+ more.'],
                ['📦', 'Auto Order Sync', 'Orders auto-created in Shopify with payment status PAID. Inventory updated. Customer email sent. Every 5 minutes.'],
                ['🔄', 'Refund Sync', 'Refunds in Stripe sync back to Shopify. Refunds in Shopify sync to Stripe. Bi-directional.'],
                ['🔒', 'Price Validation', 'All cart prices validated against Shopify API before payment. Prevents price manipulation.'],
                ['📊', 'Analytics Dashboard', 'Revenue charts, order volume, currency breakdown, payment methods — all in one dashboard.'],
                ['🏪', 'Multiple Stores', 'Manage all your Shopify stores from one QuickPay account. Perfect for agencies and multi-brand operators.'],
                ['🌐', 'State & City Autocomplete', 'Smart address autocomplete with country-specific states and cities. Reduces checkout friction.'],
                ['📡', 'Webhook Monitoring', 'Real-time webhook logs with auto-retry for failed events. Never miss a payment.'],
                ['⚡', '5-Min Auto Sync', 'Automatic background sync every 5 minutes ensures no orders are ever missed.'],
            ] as [$icon, $title, $desc])
            <div class="feature-card fade-in">
                <span class="feature-icon">{{ $icon }}</span>
                <div class="feature-title">{{ $title }}</div>
                <div class="feature-desc">{{ $desc }}</div>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- ================================================================
     PAYMENT METHODS
================================================================ -->
<section class="payments-section">
    <div class="section-inner">
        <div class="fade-in" style="text-align:center;">
            <span class="section-tag">Payment Methods</span>
            <h2 class="section-title">Accept every payment globally</h2>
            <p class="section-subtitle" style="margin:0 auto;">
                Stripe handles all payment methods automatically.
                Show the right wallets to the right customers.
            </p>
        </div>

        <div class="payment-methods-grid fade-in">
            @foreach([
                ['🍎', 'Apple Pay',       'Safari & iOS'],
                ['🤖', 'Google Pay',      'Android & Chrome'],
                ['🔗', 'Link',            'Stripe network'],
                ['💳', 'Visa',            'Worldwide'],
                ['💳', 'Mastercard',      'Worldwide'],
                ['💳', 'American Express','Premium cards'],
                ['💳', 'Discover',        'US & Global'],
                ['🌍', 'Int\'l Cards',   '30+ countries'],
            ] as [$icon, $name, $desc])
            <div class="payment-method-card">
                <span class="pm-icon">{{ $icon }}</span>
                <div class="pm-name">{{ $name }}</div>
                <div class="pm-desc">{{ $desc }}</div>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- ================================================================
     PRICING
================================================================ -->
<section id="pricing">
    <div class="section-inner">
        <div class="fade-in" style="text-align:center;">
            <span class="section-tag">Pricing</span>
            <h2 class="section-title">Simple, transparent pricing</h2>
            <p class="section-subtitle" style="margin:0 auto;">
                Start free. Scale as you grow. No hidden fees.
            </p>
        </div>

        <div class="pricing-grid fade-in">
            @foreach([
                [
                    'name' => 'STARTER',
                    'price' => '$29',
                    'period' => '/month',
                    'fee' => '+ 1.5% per transaction',
                    'stores' => '1 Store',
                    'orders' => '300 orders/month',
                    'featured' => false,
                    'features' => [
                        'Stripe Payments',
                        'Apple Pay & Google Pay',
                        'Multi-currency',
                        'Auto order sync',
                        'Email support',
                        '1 Shopify store',
                    ],
                    'btn_text' => 'Start Free Trial',
                    'btn_class' => 'btn-plan-outline',
                ],
                [
                    'name' => 'GROWTH',
                    'price' => '$79',
                    'period' => '/month',
                    'fee' => '+ 1% per transaction',
                    'stores' => '5 Stores',
                    'orders' => '2,000 orders/month',
                    'featured' => true,
                    'popular' => true,
                    'features' => [
                        'Everything in Starter',
                        'Up to 5 Shopify stores',
                        'Advanced analytics',
                        'Refund sync',
                        'Custom branding',
                        'Priority support',
                        'Webhook monitoring',
                    ],
                    'btn_text' => 'Start Free Trial',
                    'btn_class' => 'btn-plan-primary',
                ],
                [
                    'name' => 'ENTERPRISE',
                    'price' => '$199',
                    'period' => '/month',
                    'fee' => '+ 0.5% per transaction',
                    'stores' => 'Unlimited',
                    'orders' => 'Unlimited orders',
                    'featured' => false,
                    'features' => [
                        'Everything in Growth',
                        'Unlimited stores',
                        'White label option',
                        'Dedicated support',
                        'SLA guarantee',
                        'Custom integrations',
                        'API access',
                    ],
                    'btn_text' => 'Contact Sales',
                    'btn_class' => 'btn-plan-outline',
                ],
            ] as $plan)
            <div class="pricing-card {{ $plan['featured'] ? 'featured' : '' }}">
                @if(!empty($plan['popular']))
                    <div class="plan-popular">⭐ Most Popular</div>
                @endif

                <div class="plan-name">{{ $plan['name'] }}</div>
                <div class="plan-price">{{ $plan['price'] }}</div>
                <div class="plan-period">{{ $plan['period'] }}</div>
                <div class="plan-fee">{{ $plan['fee'] }}</div>

                <ul class="plan-features">
                    @foreach($plan['features'] as $feature)
                    <li>{{ $feature }}</li>
                    @endforeach
                </ul>

                <a href="{{ route('tenant.register') }}"
                   class="btn-plan {{ $plan['btn_class'] }}">
                    {{ $plan['btn_text'] }}
                </a>
            </div>
            @endforeach
        </div>

        <div style="text-align:center;margin-top:32px;color:var(--gray-500);font-size:14px;">
            All plans include a <strong>14-day free trial</strong>.
            No credit card required to start.
        </div>
    </div>
</section>

<!-- ================================================================
     TESTIMONIALS
================================================================ -->
<section class="testimonials">
    <div class="section-inner">
        <div class="fade-in" style="text-align:center;">
            <span class="section-tag">Testimonials</span>
            <h2 class="section-title">Loved by Shopify merchants</h2>
        </div>

        <div class="testimonials-grid fade-in">
            @foreach([
                [
                    'text' => "We couldn't afford Shopify Advanced just for Stripe payments. QuickPay gave us Apple Pay and Google Pay on Basic plan. Our mobile conversion went up 34%!",
                    'name' => 'Sarah M.',
                    'store' => 'Fashion Store · Dubai',
                    'initial' => 'S',
                ],
                [
                    'text' => "Set up in literally 5 minutes. The button appeared on our product pages automatically. Orders sync to Shopify in real-time. Game changer for our multi-brand operation.",
                    'name' => 'James K.',
                    'store' => 'Multi-brand Retailer · UK',
                    'initial' => 'J',
                ],
                [
                    'text' => "International customers now see prices in their local currency automatically. Our UAE sales increased 60% after enabling AED pricing. The multi-currency feature alone is worth it.",
                    'name' => 'Priya R.',
                    'store' => 'Lifestyle Brand · Singapore',
                    'initial' => 'P',
                ],
                [
                    'text' => "Managing 8 Shopify stores from one dashboard is amazing. The 5-minute sync means I never have to worry about missed orders. Support team is incredibly responsive.",
                    'name' => 'Ahmed H.',
                    'store' => 'E-commerce Agency · Egypt',
                    'initial' => 'A',
                ],
            ] as $review)
            <div class="testimonial-card">
                <div class="testimonial-stars">★★★★★</div>
                <p class="testimonial-text">"{{ $review['text'] }}"</p>
                <div class="testimonial-author">
                    <div class="author-avatar">{{ $review['initial'] }}</div>
                    <div>
                        <div class="author-name">{{ $review['name'] }}</div>
                        <div class="author-store">{{ $review['store'] }}</div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- ================================================================
     FAQ
================================================================ -->
<section id="faq">
    <div class="section-inner">
        <div class="fade-in" style="text-align:center;">
            <span class="section-tag">FAQ</span>
            <h2 class="section-title">Common questions</h2>
        </div>

        <div class="faq-list fade-in">
            @foreach([
                [
                    'q' => 'Do I need to upgrade my Shopify plan?',
                    'a' => 'No! QuickPay works specifically with Shopify Basic (and all other plans). You stay on your current plan and we handle Stripe payments through our external checkout.',
                ],
                [
                    'q' => 'How does the Shopify order sync work?',
                    'a' => 'After a customer pays through Stripe, we automatically create the order in your Shopify admin via the GraphQL API. The order is marked as PAID, inventory is reduced, and Shopify sends the customer confirmation email — all within 5 minutes automatically.',
                ],
                [
                    'q' => 'Is Apple Pay really supported?',
                    'a' => 'Yes! Apple Pay works automatically for Safari users on iPhone, iPad, and Mac through Stripe\'s Express Checkout Element. No custom Apple Pay integration needed — Stripe handles domain verification and merchant setup automatically.',
                ],
                [
                    'q' => 'How does multi-currency work?',
                    'a' => 'We detect the customer\'s location via IP and automatically show prices in their local currency (EUR for Europeans, GBP for UK, AED for UAE, etc.). The payment is processed in the customer\'s currency and Stripe handles the conversion to your settlement currency.',
                ],
                [
                    'q' => 'What happens if a sync fails?',
                    'a' => 'Our system retries automatically every 5 minutes. You can also see all failed syncs in your dashboard and manually trigger a retry. We also alert you via email for any orders that have been unsynced for more than 2 hours.',
                ],
                [
                    'q' => 'Can I use this with multiple Shopify stores?',
                    'a' => 'Yes! Growth and Enterprise plans support multiple stores from one QuickPay account. Perfect for agencies managing multiple client stores or multi-brand operations.',
                ],
                [
                    'q' => 'Is it safe? How is price manipulation prevented?',
                    'a' => 'Very safe. Every order\'s prices are validated against your Shopify Admin API before payment processing. Even if someone tries to manipulate the cart, we verify the real price from Shopify and use that for the Stripe charge.',
                ],
                [
                    'q' => 'Do you take a percentage of my sales?',
                    'a' => 'Yes, in addition to the monthly plan fee, we charge a small transaction fee (0.5%–1.5% depending on plan). This is separate from Stripe\'s own fees. On Enterprise, this drops to just 0.5% per transaction.',
                ],
            ] as $faq)
            <div class="faq-item">
                <button class="faq-q" onclick="toggleFaq(this)">
                    {{ $faq['q'] }}
                    <span class="faq-icon">+</span>
                </button>
                <div class="faq-a">{{ $faq['a'] }}</div>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- ================================================================
     CTA
================================================================ -->
<section class="cta-section">
    <div class="section-inner fade-in" style="text-align:center;">
        <span class="section-tag" style="background:rgba(255,255,255,0.1);color:#a5b4fc;">
            Get Started Today
        </span>

        <h2 class="section-title" style="color:white;margin-top:16px;">
            Ready to accept Stripe payments<br>on your Shopify Basic store?
        </h2>

        <p class="cta-desc">
            Join 500+ Shopify merchants already using QuickPay.
            Start your 14-day free trial — no credit card required.
        </p>

        <div class="cta-actions">
            <a href="{{ route('tenant.register') }}" class="btn-hero-primary" style="font-size:17px;padding:18px 40px;">
                Start Free Trial — It's Free →
            </a>
            <a href="{{ route('shopify.install') }}?shop=" class="btn-hero-outline" style="font-size:17px;padding:18px 40px;">
                Install from Shopify
            </a>
        </div>

        <div style="margin-top:24px;color:rgba(255,255,255,0.4);font-size:13px;">
            ✅ 14-day free trial &nbsp;·&nbsp;
            ✅ No credit card &nbsp;·&nbsp;
            ✅ 5-minute setup &nbsp;·&nbsp;
            ✅ Cancel anytime
        </div>
    </div>
</section>

<!-- ================================================================
     FOOTER
================================================================ -->
<footer>
    <div class="footer-inner">
        <div class="footer-grid">
            <div class="footer-brand">
                <span class="footer-logo">⚡ QuickPay</span>
                <p class="footer-desc">
                    The easiest way to accept Stripe payments on Shopify Basic.
                    Apple Pay, Google Pay, multi-currency, and automatic order sync.
                </p>
                <div style="margin-top:20px;display:flex;gap:12px;flex-wrap:wrap;">
                    <span class="f-badge">🔒 PCI DSS</span>
                    <span class="f-badge">🛡️ SSL Secure</span>
                    <span class="f-badge">✅ Stripe Verified</span>
                </div>
            </div>

            <div>
                <div class="footer-heading">Product</div>
                <ul class="footer-links">
                    <li><a href="#features">Features</a></li>
                    <li><a href="#pricing">Pricing</a></li>
                    <li><a href="#how-it-works">How It Works</a></li>
                    <li><a href="#faq">FAQ</a></li>
                    <li><a href="/changelog">Changelog</a></li>
                </ul>
            </div>

            <div>
                <div class="footer-heading">Company</div>
                <ul class="footer-links">
                    <li><a href="/about">About</a></li>
                    <li><a href="/blog">Blog</a></li>
                    <li><a href="/contact">Contact</a></li>
                    <li><a href="/affiliate">Affiliate</a></li>
                </ul>
            </div>

            <div>
                <div class="footer-heading">Legal</div>
                <ul class="footer-links">
                    <li><a href="/privacy">Privacy Policy</a></li>
                    <li><a href="/terms">Terms of Service</a></li>
                    <li><a href="/cookies">Cookie Policy</a></li>
                    <li><a href="/gdpr">GDPR</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p>© {{ date('Y') }} QuickPay. All rights reserved.</p>
            <div class="footer-badges">
                <span class="f-badge">Built with Laravel 11</span>
                <span class="f-badge">Powered by Stripe</span>
            </div>
        </div>
    </div>
</footer>

<script>
// ================================================================
// FAQ Toggle
// ================================================================
function toggleFaq(btn) {
    const item = btn.closest('.faq-item');
    const isOpen = item.classList.contains('open');

    // Close all
    document.querySelectorAll('.faq-item.open').forEach(el => {
        el.classList.remove('open');
    });

    // Open clicked if was closed
    if (!isOpen) {
        item.classList.add('open');
    }
}

// ================================================================
// Scroll Animations
// ================================================================
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
        }
    });
}, { threshold: 0.1 });

document.querySelectorAll('.fade-in').forEach(el => {
    observer.observe(el);
});

// ================================================================
// Smooth nav background
// ================================================================
window.addEventListener('scroll', () => {
    const nav = document.querySelector('.nav');
    if (window.scrollY > 50) {
        nav.style.boxShadow = '0 4px 20px rgba(0,0,0,0.1)';
    } else {
        nav.style.boxShadow = '0 1px 8px rgba(0,0,0,0.06)';
    }
});
</script>

</body>
</html>