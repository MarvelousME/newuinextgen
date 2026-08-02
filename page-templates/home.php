<?php
/**
 * Home template for NextGen Tutors kinetic homepage.
 *
 * Copy this file into your active theme or child theme as home.php.
 * Template Name: NextGen Kinetic Homepage
 */
defined( 'ABSPATH' ) || exit;
get_header();
?>
<style>
:root{--ngi-navy:#07172f;--ngi-midnight:#031126;--ngi-blue:#123c7c;--ngi-cyan:#28c7f7;--ngi-gold:#ffb703;--ngi-green:#27ae60;--ngi-orange:#ff7a1a;--ngi-soft:#f5f8ff;--ngi-white:#fff;--ngi-text:#10213f;--ngi-muted:#687386;--ngi-line:#e6edf7;--ngi-shadow:0 24px 70px rgba(18,60,124,.12);--ngi-radius:28px}
*{box-sizing:border-box}.ngi-home{margin:0;font-family:Inter,Plus Jakarta Sans,system-ui,-apple-system,Segoe UI,Arial,sans-serif;background:var(--ngi-soft);color:var(--ngi-text);overflow:hidden}.ngi-home a{text-decoration:none}.ngi-home button,.ngi-home a{cursor:pointer}.ngi-wrap{width:min(1180px,86vw);margin:0 auto}.ngi-hero{position:relative;min-height:880px;color:#fff;background:radial-gradient(circle at 74% 18%,rgba(40,199,247,.34),transparent 23%),radial-gradient(circle at 12% 78%,rgba(255,183,3,.20),transparent 26%),linear-gradient(135deg,var(--ngi-midnight),var(--ngi-blue));overflow:hidden}.ngi-hero:before{content:"";position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.045) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.045) 1px,transparent 1px);background-size:44px 44px;mask-image:linear-gradient(to bottom,#000,transparent 88%);opacity:.72}.ngi-nav{position:relative;z-index:3;display:flex;align-items:center;justify-content:space-between;padding:28px 0}.ngi-logo{font-size:26px;font-weight:950;letter-spacing:-.8px;color:white}.ngi-logo span{color:var(--ngi-cyan)}.ngi-menu{display:flex;gap:26px;align-items:center}.ngi-menu a{color:#dcefff;font-size:14px;font-weight:800}.ngi-pill-btn,.ngi-btn{display:inline-flex;align-items:center;justify-content:center;gap:9px;border:0;border-radius:999px;font-weight:950;transition:.25s transform,.25s box-shadow,.25s background}.ngi-pill-btn{padding:12px 18px;background:var(--ngi-gold);color:#07172f}.ngi-btn{padding:16px 24px;border-radius:18px}.ngi-btn:hover,.ngi-pill-btn:hover{transform:translateY(-4px);box-shadow:0 18px 45px rgba(0,0,0,.22)}.ngi-btn-primary{background:var(--ngi-cyan);color:#041326}.ngi-btn-secondary{background:rgba(255,255,255,.09);color:white;border:1px solid rgba(255,255,255,.24)}.ngi-hero-grid{position:relative;z-index:2;display:grid;grid-template-columns:1.03fr .97fr;gap:56px;align-items:center;padding:72px 0 130px}.ngi-badge{display:inline-flex;gap:9px;align-items:center;padding:10px 15px;border:1px solid rgba(255,255,255,.22);background:rgba(255,255,255,.1);border-radius:999px;color:#d8f4ff;font-weight:850}.ngi-title{font-size:clamp(46px,6vw,82px);line-height:.92;margin:22px 0 24px;letter-spacing:-4px;max-width:820px}.ngi-title .ngi-accent{color:var(--ngi-cyan)}.ngi-lead{font-size:20px;line-height:1.68;color:#d8ebff;max-width:720px}.ngi-actions{display:flex;gap:15px;flex-wrap:wrap;margin:34px 0}.ngi-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-top:32px;max-width:790px}.ngi-stat{background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);border-radius:22px;padding:18px;transition:.25s}.ngi-stat:hover{transform:translateY(-6px);background:rgba(255,255,255,.16)}.ngi-stat strong{display:block;font-size:30px;letter-spacing:-1px}.ngi-stat small{color:#c7e8ff}.ngi-visual{position:relative;height:610px}.ngi-glow{position:absolute;inset:70px 30px auto auto;width:390px;height:390px;background:radial-gradient(circle,rgba(40,199,247,.45),transparent 62%);filter:blur(14px)}.ngi-panel{position:absolute;right:0;top:34px;width:92%;background:rgba(255,255,255,.96);backdrop-filter:blur(18px);border:1px solid rgba(255,255,255,.78);border-radius:36px;color:#13223d;padding:26px;box-shadow:0 40px 100px rgba(0,0,0,.35);z-index:3;transition:.35s transform}.ngi-panel:hover{transform:scale(1.015)}.ngi-panel-head{display:flex;align-items:center;justify-content:space-between;gap:16px}.ngi-kpi-pill{background:#edf7ff;border-radius:999px;padding:9px 12px;color:var(--ngi-blue);font-size:13px;font-weight:900}.ngi-progress-card{background:#f1f7ff;border:1px solid #dcecff;border-radius:24px;padding:18px;margin-top:16px}.ngi-progress-title{display:flex;justify-content:space-between;font-weight:900}.ngi-bar{height:11px;background:#fff;border-radius:999px;margin-top:12px;overflow:hidden}.ngi-bar span{display:block;height:100%;width:0;background:linear-gradient(90deg,var(--ngi-cyan),var(--ngi-green));border-radius:999px;transition:width 1.2s cubic-bezier(.2,.9,.2,1)}.ngi-dashgrid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin-top:16px}.ngi-dashitem{background:white;border:1px solid #e5edf8;border-radius:18px;padding:16px;transition:.25s}.ngi-dashitem:hover{transform:translateY(-5px);border-color:var(--ngi-cyan)}.ngi-dashitem b{display:block;margin-top:5px}.ngi-chips{display:flex;gap:10px;flex-wrap:wrap;margin-top:16px}.ngi-chip{border:0;background:#eaf5ff;color:var(--ngi-blue);border-radius:999px;padding:10px 13px;font-weight:900}.ngi-chip.is-active{background:var(--ngi-blue);color:white}.ngi-float{position:absolute;z-index:4;background:white;color:#12213d;padding:16px 18px;border-radius:20px;font-weight:950;box-shadow:0 20px 48px rgba(0,0,0,.25);animation:ngiFloat 3.2s ease-in-out infinite}.ngi-f1{left:0;top:76px}.ngi-f2{right:4px;bottom:112px;animation-delay:.35s}.ngi-f3{left:42px;bottom:42px;animation-delay:.7s}.ngi-f4{right:80px;top:0;animation-delay:1s;background:#081a36;color:#d9f5ff;border:1px solid rgba(255,255,255,.18)}@keyframes ngiFloat{0%,100%{transform:translateY(-8px)}50%{transform:translateY(10px)}}.ngi-shape{position:absolute;left:0;bottom:-1px;width:100%;height:130px;background:white;clip-path:polygon(0 48%,18% 60%,34% 46%,52% 64%,72% 42%,100% 56%,100% 100%,0 100%)}.ngi-section{padding:92px 0;background:white}.ngi-alt{background:#f6f9ff}.ngi-section-head{text-align:center;margin-bottom:50px}.ngi-eyebrow{color:var(--ngi-blue);font-weight:950;letter-spacing:.1em;text-transform:uppercase;font-size:12px}.ngi-heading{font-size:clamp(34px,4vw,54px);line-height:1;margin:12px 0 14px;letter-spacing:-2px}.ngi-subtitle{color:var(--ngi-muted);font-size:18px;max-width:850px;margin:0 auto;line-height:1.65}.ngi-card-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:22px}.ngi-card{background:#fff;border:1px solid var(--ngi-line);border-radius:var(--ngi-radius);padding:28px;box-shadow:var(--ngi-shadow);transition:.25s transform,.25s border,.25s box-shadow}.ngi-card:hover{transform:translateY(-10px) scale(1.012);border-color:var(--ngi-cyan);box-shadow:0 32px 80px rgba(18,60,124,.18)}.ngi-icon{font-size:38px;margin-bottom:13px}.ngi-card h3{margin:0 0 10px;font-size:23px}.ngi-card p{color:var(--ngi-muted);line-height:1.62;margin:0}.ngi-marquee{display:flex;gap:14px;overflow:hidden;mask-image:linear-gradient(90deg,transparent,#000 9%,#000 91%,transparent);margin-top:36px}.ngi-marquee-track{display:flex;gap:14px;min-width:max-content;animation:ngiMarquee 28s linear infinite}.ngi-marquee span{background:#fff;border:1px solid var(--ngi-line);border-radius:999px;padding:12px 18px;font-weight:950;color:var(--ngi-blue)}@keyframes ngiMarquee{to{transform:translateX(-50%)}}.ngi-subject-shell{display:grid;grid-template-columns:.75fr 1.25fr;gap:24px;align-items:start}.ngi-subject-tabs{display:grid;gap:10px}.ngi-tab{border:1px solid #dbe7f7;background:white;border-radius:18px;padding:16px;text-align:left;font-weight:950;color:var(--ngi-blue);display:flex;justify-content:space-between}.ngi-tab.is-active{background:var(--ngi-blue);color:white}.ngi-subject-panel{background:white;border:1px solid var(--ngi-line);border-radius:34px;padding:34px;box-shadow:var(--ngi-shadow);min-height:320px}.ngi-subject-panel h3{font-size:36px;margin:0 0 10px}.ngi-subject-panel p{color:var(--ngi-muted);line-height:1.7}.ngi-bullet-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin-top:22px}.ngi-bullet{background:#f4f8ff;border:1px solid #e4edf9;border-radius:16px;padding:14px;font-weight:850}.ngi-steps{display:grid;grid-template-columns:repeat(5,1fr);gap:16px}.ngi-step{background:white;border:1px solid var(--ngi-line);border-radius:24px;padding:24px;text-align:center;transition:.25s}.ngi-step:hover,.ngi-step.is-active{transform:translateY(-8px);border-color:var(--ngi-gold);box-shadow:0 20px 45px rgba(255,183,3,.18)}.ngi-num{width:48px;height:48px;background:var(--ngi-blue);color:white;border-radius:50%;display:grid;place-items:center;margin:0 auto 14px;font-weight:950}.ngi-tutor-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:22px}.ngi-tutor{overflow:hidden;padding:0}.ngi-tutor-visual{height:180px;background:radial-gradient(circle at 20% 20%,rgba(40,199,247,.5),transparent 28%),linear-gradient(135deg,#092746,#123c7c);display:flex;align-items:end;padding:20px;color:white}.ngi-avatar{width:74px;height:74px;border-radius:24px;background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.28);display:grid;place-items:center;font-size:34px}.ngi-tutor-body{padding:24px}.ngi-tagline{display:flex;gap:8px;flex-wrap:wrap;margin:14px 0}.ngi-tag{font-size:12px;font-weight:900;color:#123c7c;background:#eaf5ff;border-radius:999px;padding:7px 10px}.ngi-pricing{display:grid;grid-template-columns:repeat(3,1fr);gap:22px}.ngi-price{position:relative}.ngi-price.is-featured{background:linear-gradient(180deg,#fff,#f4fbff);border-color:var(--ngi-cyan);transform:translateY(-10px)}.ngi-price strong{font-size:42px;letter-spacing:-2px}.ngi-price ul{padding-left:20px;color:var(--ngi-muted);line-height:1.9}.ngi-faq{max-width:900px;margin:0 auto}.ngi-faq-item{background:white;border:1px solid var(--ngi-line);border-radius:20px;margin-bottom:12px;overflow:hidden}.ngi-faq-q{width:100%;text-align:left;background:white;border:0;padding:20px 22px;font-size:17px;font-weight:950;display:flex;justify-content:space-between}.ngi-faq-a{max-height:0;overflow:hidden;transition:max-height .35s ease}.ngi-faq-a p{margin:0;padding:0 22px 20px;color:var(--ngi-muted);line-height:1.65}.ngi-cta{background:linear-gradient(135deg,var(--ngi-midnight),var(--ngi-blue));color:white;border-radius:40px;padding:54px;display:grid;grid-template-columns:1.1fr .9fr;gap:30px;align-items:center;box-shadow:0 35px 90px rgba(7,23,47,.3)}.ngi-cta h2{font-size:44px;line-height:1;margin:0 0 16px}.ngi-cta p{color:#d9eeff;line-height:1.65}.ngi-cta-panel{background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.18);border-radius:28px;padding:24px}.ngi-modal{position:fixed;inset:0;background:rgba(7,23,47,.74);display:none;align-items:center;justify-content:center;z-index:9999;padding:24px}.ngi-modal.is-open{display:flex}.ngi-modal-card{background:white;border-radius:28px;padding:28px;max-width:560px;width:100%;box-shadow:0 40px 100px rgba(0,0,0,.35)}.ngi-close{float:right;border:0;background:#edf4ff;border-radius:50%;width:38px;height:38px;font-weight:950}.ngi-modal-card input,.ngi-modal-card select,.ngi-modal-card textarea{width:100%;padding:14px;border:1px solid #dce7f5;border-radius:14px;margin:8px 0;font:inherit}.ngi-sticky{position:fixed;right:24px;bottom:24px;z-index:100;background:var(--ngi-gold);color:#07172f;border-radius:999px;padding:16px 22px;font-weight:950;box-shadow:0 18px 45px rgba(0,0,0,.22);animation:ngiPulse 1.7s ease-in-out infinite;border:0}@keyframes ngiPulse{0%,100%{transform:scale(1)}50%{transform:scale(1.045)}}.ngi-reveal{opacity:0;transform:translateY(30px);transition:opacity .7s ease,transform .7s ease}.ngi-reveal.is-in{opacity:1;transform:translateY(0)}.ngi-sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}@media (prefers-reduced-motion:reduce){*,*:before,*:after{animation:none!important;scroll-behavior:auto!important;transition:none!important}.ngi-reveal{opacity:1;transform:none}}@media(max-width:980px){.ngi-menu{display:none}.ngi-hero-grid,.ngi-subject-shell,.ngi-cta{grid-template-columns:1fr}.ngi-card-grid,.ngi-tutor-grid,.ngi-pricing,.ngi-steps{grid-template-columns:1fr}.ngi-stats{grid-template-columns:repeat(2,1fr)}.ngi-visual{height:650px}.ngi-panel{width:100%}.ngi-title{letter-spacing:-2px}.ngi-f2{bottom:55px}.ngi-sticky{left:16px;right:16px;text-align:center}.ngi-cta{padding:34px}}@media(max-width:620px){.ngi-wrap{width:min(92vw,1180px)}.ngi-stats,.ngi-bullet-grid,.ngi-dashgrid{grid-template-columns:1fr}.ngi-hero{min-height:980px}.ngi-section{padding:72px 0}.ngi-float{font-size:13px;padding:12px}.ngi-f4{display:none}}

/* KineticHub Free feature layer */
.ngi-kh-mesh{position:absolute;inset:0;pointer-events:none;background:radial-gradient(circle at var(--mx,70%) var(--my,20%),rgba(40,199,247,.32),transparent 20%),radial-gradient(circle at 20% 72%,rgba(255,183,3,.18),transparent 28%);mix-blend-mode:screen;transition:background .25s ease}.ngi-kinetic-box{position:relative;border:1px solid rgba(255,255,255,.18);background:linear-gradient(180deg,rgba(255,255,255,.10),rgba(255,255,255,.04));box-shadow:inset 0 1px 0 rgba(255,255,255,.18),0 28px 80px rgba(3,17,38,.22);backdrop-filter:blur(18px);border-radius:32px;overflow:hidden}.ngi-kinetic-box:before{content:"";position:absolute;inset:-1px;background:linear-gradient(130deg,rgba(40,199,247,.38),transparent 30%,rgba(255,183,3,.28));opacity:.45;pointer-events:none}.ngi-kinetic-text span{display:inline-block;will-change:transform,opacity}.ngi-magnetic{position:relative;overflow:hidden}.ngi-magnetic:after{content:"";position:absolute;inset:auto auto -55% -30%;width:90%;height:120%;background:radial-gradient(circle,rgba(255,255,255,.42),transparent 60%);transform:translateX(var(--magx,0)) translateY(var(--magy,0));transition:.18s}.ngi-scroll-divider{height:4px;background:#dce8f8;border-radius:99px;overflow:hidden;margin:0 auto;max-width:1180px}.ngi-scroll-divider span{display:block;height:100%;width:0;background:linear-gradient(90deg,var(--ngi-cyan),var(--ngi-gold),var(--ngi-green));transition:width .45s ease}.ngi-video-tile{min-height:320px;border-radius:36px;background:radial-gradient(circle at 30% 20%,rgba(40,199,247,.35),transparent 28%),linear-gradient(135deg,#06152e,#123c7c);display:grid;place-items:center;color:white;box-shadow:0 30px 90px rgba(3,17,38,.28);position:relative;overflow:hidden}.ngi-play{width:86px;height:86px;border-radius:50%;border:1px solid rgba(255,255,255,.35);background:rgba(255,255,255,.14);color:white;font-size:30px;display:grid;place-items:center;backdrop-filter:blur(14px)}.ngi-before-after{position:relative;border-radius:32px;overflow:hidden;min-height:330px;box-shadow:var(--ngi-shadow);background:#fff}.ngi-ba-layer{position:absolute;inset:0;display:grid;place-items:center;padding:32px;color:white;font-weight:950;font-size:28px}.ngi-ba-before{background:linear-gradient(135deg,#8a1025,#ff7a1a)}.ngi-ba-after{background:linear-gradient(135deg,#07172f,#28c7f7);clip-path:inset(0 0 0 50%)}.ngi-ba-range{position:absolute;left:8%;right:8%;bottom:22px;width:84%;accent-color:var(--ngi-gold)}.ngi-audio{display:flex;gap:16px;align-items:center;background:white;border:1px solid var(--ngi-line);border-radius:28px;padding:18px;box-shadow:var(--ngi-shadow)}.ngi-audio button{width:48px;height:48px;border-radius:50%;border:0;background:var(--ngi-blue);color:white;font-weight:950}.ngi-audio-bar{height:10px;flex:1;background:#eaf2fb;border-radius:99px;overflow:hidden}.ngi-audio-bar span{display:block;width:42%;height:100%;background:linear-gradient(90deg,var(--ngi-cyan),var(--ngi-green));border-radius:inherit}.ngi-split{display:grid;grid-template-columns:.92fr 1.08fr;gap:34px;align-items:start}.ngi-split-media{position:sticky;top:96px;min-height:480px;border-radius:36px;background:linear-gradient(135deg,#07172f,#123c7c);box-shadow:0 35px 100px rgba(3,17,38,.25);padding:28px;color:white;overflow:hidden}.ngi-split-item{padding:28px;border:1px solid var(--ngi-line);border-radius:28px;background:white;box-shadow:var(--ngi-shadow);margin-bottom:18px}.ngi-cursor-list{display:grid;grid-template-columns:.85fr 1.15fr;gap:22px}.ngi-cursor-item{border:1px solid var(--ngi-line);border-radius:20px;padding:18px;background:white;font-weight:950}.ngi-cursor-item.is-active{border-color:var(--ngi-cyan);box-shadow:0 18px 45px rgba(40,199,247,.14)}.ngi-cursor-preview{min-height:360px;border-radius:34px;background:radial-gradient(circle at var(--rx,50%) var(--ry,50%),rgba(255,183,3,.36),transparent 22%),linear-gradient(135deg,#06152e,#123c7c);display:grid;place-items:center;color:white;padding:28px;box-shadow:0 30px 90px rgba(3,17,38,.24)}.ngi-aura{position:relative;overflow:hidden}.ngi-aura:before,.ngi-aura:after{content:"";position:absolute;width:420px;height:420px;border-radius:50%;filter:blur(34px);opacity:.28;pointer-events:none}.ngi-aura:before{background:var(--ngi-cyan);left:-140px;top:-120px}.ngi-aura:after{background:var(--ngi-gold);right:-150px;bottom:-140px}.ngi-feature-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:24px}.ngi-video-modal iframe{width:min(920px,92vw);height:min(520px,55vw);border:0;border-radius:24px;background:#000}.ngi-modal-card.ngi-video-modal{max-width:980px;background:#07172f;color:white}.ngi-kh-note{font-size:13px;color:var(--ngi-muted);margin-top:8px}@media(max-width:980px){.ngi-split,.ngi-cursor-list,.ngi-feature-grid{grid-template-columns:1fr}.ngi-split-media{position:relative;top:auto;min-height:280px}.ngi-before-after{min-height:260px}}

</style>
<style id="ngi-nav-blue-chrome">
  /* Solid theme-blue homepage nav (no header CTAs) */
  .ngi-home .ngi-nav{
    position:sticky;top:0;z-index:20;
    margin:0 -7vw;padding:16px 7vw;
    width:calc(100% + 14vw);
    box-sizing:border-box;
    background:linear-gradient(180deg,var(--ngi-blue),var(--ngi-midnight));
    border-bottom:2px solid rgba(40,199,247,.35);
    box-shadow:0 12px 32px rgba(3,17,38,.28);
    backdrop-filter:blur(10px);
  }
  .ngi-home .ngi-logo{color:#fff}
  .ngi-home .ngi-logo span{color:var(--ngi-cyan)}
  .ngi-home .ngi-menu a{color:#dcefff}
  .ngi-home .ngi-menu a:hover{color:#fff}
  .ngi-home .ngi-pill-btn{display:none!important}
</style>

<div class="ngi-home" id="nextgen-home">
  <!--<button class="ngi-sticky" data-ngi-open type="button">Book Free Assessment</button>-->

  <main class="ngi-hero" aria-label="NextGen Tutors homepage hero">
    <div class="ngi-kh-mesh" aria-hidden="true"></div>
    <div class="ngi-wrap">
      <nav class="ngi-nav" aria-label="Homepage navigation">
        <a class="ngi-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" data-ngt-transition data-testid="ngi-home-logo" aria-label="NextGen Tutors home" onclick="return window.NGT_pageTransition ? window.NGT_pageTransition(this.href, event) : true;">NextGen<span>Tutors</span></a>
        <div class="ngi-menu">
          <a href="#subjects">Subjects</a><a href="#journey">How It Works</a><a href="#tutors">Tutors</a><a href="#pricing">Pricing</a><a href="#faq">FAQ</a>
        </div>
      </nav>
      <div class="ngi-hero-grid">
        <div>
          <div class="ngi-badge ngi-reveal" data-kh-motion="fade-up">⚡ Premium online, in-person and hybrid tutoring</div>
          <h1 class="ngi-title ngi-reveal" data-kh-animated-typography>Bridging academic gaps with <span class="ngi-accent">world-class tutors</span>.</h1>
          <p class="ngi-lead ngi-reveal">A conversion-focused learning marketplace for parents, students and tutors across South Africa — with progress tracking, verified tutor matching, booking journeys and CRM-ready follow-up.</p>
          <div class="ngi-actions ngi-reveal">
            <button class="ngi-btn ngi-btn-primary" data-ngi-open type="button">Find My Tutor</button>
            <a class="ngi-btn ngi-btn-secondary" href="#tutors">Become a Tutor</a>
          </div>
          <div class="ngi-stats ngi-reveal" aria-label="Platform statistics">
            <div class="ngi-stat"><strong data-count="500" data-suffix="+">0+</strong><small>Students assisted</small></div>
            <div class="ngi-stat"><strong data-count="50" data-suffix="+">0+</strong><small>Verified tutors</small></div>
            <div class="ngi-stat"><strong data-count="98" data-suffix="%">0%</strong><small>Parent satisfaction</small></div>
            <div class="ngi-stat"><strong data-count="9" data-suffix="">0</strong><small>Provinces served</small></div>
          </div>
        </div>
        <div class="ngi-visual" data-kh-ambient-visual>
          <div class="ngi-glow" aria-hidden="true"></div>
          <div class="ngi-float ngi-f1">📘 Maths Rescue</div>
          <div class="ngi-float ngi-f2">🎯 Exam Prep</div>
          <div class="ngi-float ngi-f3">📈 Parent Reports</div>
          <div class="ngi-float ngi-f4">✨ Verified Tutor Match</div>
          <div class="ngi-panel ngi-reveal" data-kh-motion-container>
            <div class="ngi-panel-head">
              <div><h2 style="margin:0">Parent Dashboard</h2><small>Live progress preview</small></div>
              <div class="ngi-kpi-pill">Online + In-person</div>
            </div>
            <div class="ngi-progress-card"><div class="ngi-progress-title"><span id="ngiCourseName">Mathematics</span><span id="ngiCourseScore">82%</span></div><div class="ngi-bar"><span id="ngiCourseBar"></span></div></div>
            <div class="ngi-progress-card"><div class="ngi-progress-title"><span>Homework Completion</span><span id="ngiHomeworkScore">76%</span></div><div class="ngi-bar"><span id="ngiHomeworkBar"></span></div></div>
            <div class="ngi-chips" aria-label="Subject preview controls">
              <button class="ngi-chip is-active" data-course="Mathematics" data-score="82" data-homework="76" type="button">Maths</button>
              <button class="ngi-chip" data-course="Physical Science" data-score="74" data-homework="69" type="button">Science</button>
              <button class="ngi-chip" data-course="English" data-score="88" data-homework="91" type="button">English</button>
              <button class="ngi-chip" data-course="Accounting" data-score="79" data-homework="84" type="button">Accounting</button>
            </div>
            <div class="ngi-dashgrid">
              <div class="ngi-dashitem">📅 Next lesson<b>Today 17:00</b></div><div class="ngi-dashitem">✅ Homework<b>4/5 complete</b></div><div class="ngi-dashitem">🧑‍🏫 Tutor match<b>Verified</b></div><div class="ngi-dashitem">⭐ Rating<b>4.9 / 5</b></div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="ngi-shape" aria-hidden="true"></div>
  </main>

  <section class="ngi-section" id="trust">
    <div class="ngi-wrap">
      <div class="ngi-section-head ngi-reveal"><div class="ngi-eyebrow">Trusted learning ecosystem</div><h2 class="ngi-heading">Everything parents need to move from worry to progress.</h2><p class="ngi-subtitle">Designed for NextGenCompanion workflows: registration, tutor matching, booking, CRM follow-up, payment status, dashboards and verification.</p></div>
      <div class="ngi-card-grid">
        <article class="ngi-card ngi-reveal"><div class="ngi-icon">🧭</div><h3>Guided tutor matching</h3><p>Parents select subjects, grade, province and learning format, then move into the correct registration and CRM journey.</p></article>
        <article class="ngi-card ngi-reveal"><div class="ngi-icon">🧑‍🏫</div><h3>Verified tutor profiles</h3><p>Showcase tutor credentials, subjects, availability, reviews and booking CTAs with a premium marketplace feel.</p></article>
        <article class="ngi-card ngi-reveal"><div class="ngi-icon">📊</div><h3>Dashboard-first experience</h3><p>Students, parents and tutors see relational data: lessons, assignments, progress, bookings and payment status.</p></article>
      </div>
      <div class="ngi-marquee" data-kh-marquee aria-hidden="true"><div class="ngi-marquee-track"><span>Mathematics</span><span>Physical Science</span><span>English</span><span>Accounting</span><span>IT</span><span>CAT</span><span>Programming</span><span>University Support</span><span>Exam Prep</span><span>Mathematics</span><span>Physical Science</span><span>English</span><span>Accounting</span><span>IT</span><span>CAT</span><span>Programming</span><span>University Support</span><span>Exam Prep</span></div></div>
    </div>
  </section>

  <section class="ngi-section ngi-alt" id="subjects">
    <div class="ngi-wrap">
      <div class="ngi-section-head ngi-reveal"><div class="ngi-eyebrow">Subject explorer</div><h2 class="ngi-heading">Click a subject and watch the learning plan adapt.</h2><p class="ngi-subtitle">A KineticHub-inspired interactive content area for a modern tutoring homepage.</p></div>
      <div class="ngi-subject-shell">
        <div class="ngi-subject-tabs ngi-reveal">
          <button class="ngi-tab is-active" type="button" data-title="Mathematics" data-body="Maths rescue plans, weekly tutoring, exam preparation, homework support and parent progress reports." data-bullets="Grade 1–12|Exam technique|Weekly progress|Homework rescue">Mathematics <span>→</span></button>
          <button class="ngi-tab" type="button" data-title="Physical Science" data-body="Focused support for formulas, practical understanding, experiments, topic-by-topic improvement and exam confidence." data-bullets="Physics|Chemistry|Problem solving|Matric prep">Physical Science <span>→</span></button>
          <button class="ngi-tab" type="button" data-title="English" data-body="Reading, writing, comprehension, grammar, essays, literature support and confidence building." data-bullets="Comprehension|Essay writing|Grammar|Literature">English <span>→</span></button>
          <button class="ngi-tab" type="button" data-title="Programming" data-body="Beginner-friendly coding support for school, college and project-based learning." data-bullets="Python basics|Web projects|Logic|Portfolio support">Programming <span>→</span></button>
        </div>
        <div class="ngi-subject-panel ngi-reveal"><h3 id="ngiSubjectTitle">Mathematics</h3><p id="ngiSubjectBody">Maths rescue plans, weekly tutoring, exam preparation, homework support and parent progress reports.</p><div class="ngi-bullet-grid" id="ngiSubjectBullets"><div class="ngi-bullet">Grade 1–12</div><div class="ngi-bullet">Exam technique</div><div class="ngi-bullet">Weekly progress</div><div class="ngi-bullet">Homework rescue</div></div><div style="margin-top:26px"><button class="ngi-btn ngi-btn-primary" data-ngi-open type="button">Get Subject Help</button></div></div>
      </div>
    </div>
  </section>

  <section class="ngi-section" id="journey">
    <div class="ngi-wrap">
      <div class="ngi-section-head ngi-reveal"><div class="ngi-eyebrow">Learner journey</div><h2 class="ngi-heading">A clear path from assessment to measurable improvement.</h2></div>
      <div class="ngi-steps">
        <div class="ngi-step ngi-reveal"><div class="ngi-num">1</div><b>Assessment</b><p>Identify gaps.</p></div><div class="ngi-step ngi-reveal"><div class="ngi-num">2</div><b>Tutor Match</b><p>Assign fit.</p></div><div class="ngi-step ngi-reveal"><div class="ngi-num">3</div><b>Learning Plan</b><p>Set goals.</p></div><div class="ngi-step ngi-reveal"><div class="ngi-num">4</div><b>Weekly Lessons</b><p>Track work.</p></div><div class="ngi-step ngi-reveal"><div class="ngi-num">5</div><b>Reports</b><p>Show progress.</p></div>
      </div>
    </div>
  </section>


  <div class="ngi-scroll-divider" aria-hidden="true"><span id="ngiScrollDivider"></span></div>

  <section class="ngi-section ngi-aura" id="kinetic-hub-showcase">
    <div class="ngi-wrap">
      <div class="ngi-section-head ngi-reveal">
        <div class="ngi-eyebrow">KineticHub Free layer</div>
        <h2 class="ngi-heading ngi-kinetic-text">Motion blocks that make the page feel alive.</h2>
        <p class="ngi-subtitle">This section maps every enabled KineticHub Free feature into a WordPress-friendly homepage pattern: kinetic boxes, animated typography, magnetic CTAs, marquee, video, split scroll, cursor reveal, audio and before/after proof.</p>
      </div>
      <div class="ngi-feature-grid">
        <article class="ngi-card ngi-kinetic-box ngi-reveal"><div class="ngi-icon">📦</div><h3>Kinetic Box</h3><p>Motion-ready containers for trust blocks, feature highlights, dashboards and tutor cards.</p><button class="ngi-btn ngi-btn-primary ngi-magnetic" data-ngi-open type="button">Start Matching</button></article>
        <article class="ngi-card ngi-reveal"><div class="ngi-icon">🔠</div><h3>Kinetic Typography</h3><p>Animated text with accessible fallback for headlines, value propositions and CTA lines.</p><p class="ngi-kh-note">Fallback text remains readable when motion is disabled.</p></article>
        <article class="ngi-card ngi-reveal"><div class="ngi-icon">🧲</div><h3>Magnetic Button</h3><p>Premium pointer-aware CTAs for “Find a Tutor”, “Book Assessment” and “Become a Tutor”.</p><button class="ngi-btn ngi-btn-primary ngi-magnetic" data-ngi-open type="button">Try Magnetic CTA</button></article>
        <article class="ngi-card ngi-reveal"><div class="ngi-icon">🌫️</div><h3>Ambient Aura</h3><p>Soft ambient backgrounds that add polish without heavy images or layout shift.</p></article>
      </div>
    </div>
  </section>

  <section class="ngi-section ngi-alt" id="learning-proof">
    <div class="ngi-wrap">
      <div class="ngi-section-head ngi-reveal"><div class="ngi-eyebrow">Before / After</div><h2 class="ngi-heading">Show the transformation parents want to see.</h2><p class="ngi-subtitle">Accessible comparison slider for “before tutoring” vs “after structured support”.</p></div>
      <div class="ngi-before-after ngi-reveal" aria-label="Before and after progress comparison">
        <div class="ngi-ba-layer ngi-ba-before">Before: Confused, behind, anxious</div>
        <div class="ngi-ba-layer ngi-ba-after" id="ngiBaAfter">After: Confident, supported, improving</div>
        <input class="ngi-ba-range" id="ngiBaRange" type="range" min="0" max="100" value="50" aria-label="Compare before and after learning progress" />
      </div>
    </div>
  </section>

  <section class="ngi-section" id="video-story">
    <div class="ngi-wrap ngi-split">
      <div class="ngi-split-media ngi-reveal">
        <div class="ngi-video-tile"><button class="ngi-play" id="ngiOpenVideo" type="button" aria-label="Open NextGen Tutors story video">▶</button></div>
        <div style="margin-top:18px"><h3>Video Modal</h3><p>Lazy video modal ready for a brand trailer, tutor intro or parent onboarding video.</p></div>
        <div class="ngi-audio" style="margin-top:18px"><button id="ngiAudioToggle" type="button">▶</button><div><b>Audio Player</b><div class="ngi-audio-bar"><span id="ngiAudioBar"></span></div></div></div>
      </div>
      <div>
        <article class="ngi-split-item ngi-reveal"><h3>Split Scroll: Parent confidence</h3><p>Pinned media stays visible while content explains the tutoring journey.</p></article>
        <article class="ngi-split-item ngi-reveal"><h3>Verified tutor matching</h3><p>Parents see subject fit, grade support, province availability and learning format.</p></article>
        <article class="ngi-split-item ngi-reveal"><h3>CRM-ready follow-up</h3><p>Each CTA can connect to Fluent Forms, FluentCRM, Amelia and NextGenCompanion workflows.</p></article>
      </div>
    </div>
  </section>

  <section class="ngi-section ngi-alt" id="cursor-reveal">
    <div class="ngi-wrap">
      <div class="ngi-section-head ngi-reveal"><div class="ngi-eyebrow">Cursor Reveal</div><h2 class="ngi-heading">Interactive discovery for learning pathways.</h2></div>
      <div class="ngi-cursor-list ngi-reveal">
        <div>
          <button class="ngi-cursor-item is-active" type="button" data-title="Parent Journey" data-copy="Book assessment, match tutor, track progress and manage payments.">Parent Journey</button>
          <button class="ngi-cursor-item" type="button" data-title="Student Journey" data-copy="View lessons, subjects, achievements and personal progress.">Student Journey</button>
          <button class="ngi-cursor-item" type="button" data-title="Tutor Journey" data-copy="Manage bookings, learners, availability, reviews and earnings.">Tutor Journey</button>
          <button class="ngi-cursor-item" type="button" data-title="Admin Journey" data-copy="Monitor CRM, workflows, bookings, API checks and demo readiness.">Admin Journey</button>
        </div>
        <div class="ngi-cursor-preview" id="ngiCursorPreview"><div><h3 id="ngiCursorTitle">Parent Journey</h3><p id="ngiCursorCopy">Book assessment, match tutor, track progress and manage payments.</p></div></div>
      </div>
    </div>
  </section>

  <section class="ngi-section ngi-alt" id="tutors">
    <div class="ngi-wrap">
      <div class="ngi-section-head ngi-reveal"><div class="ngi-eyebrow">Featured tutors</div><h2 class="ngi-heading">Premium marketplace cards ready for real tutor data.</h2><p class="ngi-subtitle">Use these as static homepage highlights or replace with NextGenCompanion tutor directory output.</p></div>
      <div class="ngi-tutor-grid">
        <article class="ngi-card ngi-tutor ngi-reveal"><div class="ngi-tutor-visual"><div class="ngi-avatar">MS</div></div><div class="ngi-tutor-body"><h3>Marvin Saunders</h3><p>Senior Technology, Maths and Coding tutor based in KwaZulu-Natal.</p><div class="ngi-tagline"><span class="ngi-tag">Maths</span><span class="ngi-tag">IT</span><span class="ngi-tag">Coding</span></div><button class="ngi-btn ngi-btn-primary" data-ngi-open type="button">Book Marvin</button></div></article>
        <article class="ngi-card ngi-tutor ngi-reveal"><div class="ngi-tutor-visual"><div class="ngi-avatar">NT</div></div><div class="ngi-tutor-body"><h3>Nandi Tutor</h3><p>Physical Science and Mathematics support for high-school learners.</p><div class="ngi-tagline"><span class="ngi-tag">Science</span><span class="ngi-tag">Maths</span><span class="ngi-tag">Online</span></div><button class="ngi-btn ngi-btn-primary" data-ngi-open type="button">Book Nandi</button></div></article>
        <article class="ngi-card ngi-tutor ngi-reveal"><div class="ngi-tutor-visual"><div class="ngi-avatar">AT</div></div><div class="ngi-tutor-body"><h3>Ayesha Tutor</h3><p>English, exam preparation and confident communication coaching.</p><div class="ngi-tagline"><span class="ngi-tag">English</span><span class="ngi-tag">Essays</span><span class="ngi-tag">Hybrid</span></div><button class="ngi-btn ngi-btn-primary" data-ngi-open type="button">Book Ayesha</button></div></article>
      </div>
    </div>
  </section>

  <section class="ngi-section" id="pricing">
    <div class="ngi-wrap">
      <div class="ngi-section-head ngi-reveal"><div class="ngi-eyebrow">Simple packages</div><h2 class="ngi-heading">Flexible learning options for every family.</h2></div>
      <div class="ngi-pricing">
        <article class="ngi-card ngi-price ngi-reveal"><h3>Online Boost</h3><strong>R320</strong><p>per hour</p><ul><li>Online tutoring</li><li>Homework support</li><li>Progress notes</li></ul><button class="ngi-btn ngi-btn-primary" data-ngi-open type="button">Start Online</button></article>
        <article class="ngi-card ngi-price is-featured ngi-reveal"><h3>Hybrid Growth</h3><strong>R350</strong><p>per hour</p><ul><li>Online + in-person</li><li>Priority matching</li><li>Parent reporting</li></ul><button class="ngi-btn ngi-btn-primary" data-ngi-open type="button">Choose Hybrid</button></article>
        <article class="ngi-card ngi-price ngi-reveal"><h3>Tertiary Support</h3><strong>R500</strong><p>per hour</p><ul><li>University support</li><li>Project guidance</li><li>Exam preparation</li></ul><button class="ngi-btn ngi-btn-primary" data-ngi-open type="button">Get Support</button></article>
      </div>
    </div>
  </section>

  <section class="ngi-section ngi-alt" id="faq">
    <div class="ngi-wrap"><div class="ngi-section-head ngi-reveal"><div class="ngi-eyebrow">Questions</div><h2 class="ngi-heading">Built for trust before parents click.</h2></div><div class="ngi-faq ngi-reveal">
      <div class="ngi-faq-item"><button class="ngi-faq-q" type="button">Can parents track progress? <span>+</span></button><div class="ngi-faq-a"><p>Yes. The dashboard design highlights attendance, homework, upcoming lessons, tutor notes and progress reports.</p></div></div>
      <div class="ngi-faq-item"><button class="ngi-faq-q" type="button">Does this work with KineticHub Free? <span>+</span></button><div class="ngi-faq-a"><p>Yes. The page uses motion-ready containers and Gutenberg-friendly sections. You can recreate key sections with KineticHub Free blocks, then use this PHP template as the theme-level homepage.</p></div></div>
      <div class="ngi-faq-item"><button class="ngi-faq-q" type="button">Can this connect to Amelia bookings? <span>+</span></button><div class="ngi-faq-a"><p>The CTAs are designed to connect to Amelia or NextGenCompanion booking flows. The modal includes a safe fallback.</p></div></div>
    </div></div>
  </section>

  <section class="ngi-section">
    <div class="ngi-wrap"><div class="ngi-cta ngi-reveal"><div><h2>Ready to turn tutoring into a premium digital experience?</h2><p>Launch a homepage that feels like a modern education technology platform and guides parents into the correct registration and booking journey.</p><div class="ngi-actions"><button class="ngi-btn ngi-btn-primary" data-ngi-open type="button">Book Free Assessment</button><a class="ngi-btn ngi-btn-secondary" href="#subjects">Explore Subjects</a></div></div><div class="ngi-cta-panel"><h3>NextGen-ready touchpoints</h3><p>Designed for FluentCRM, Fluent Forms, Amelia, WooCommerce, MasterStudy and NextGenCompanion demo workflows.</p></div></div></div>
  </section>


  <div class="ngi-modal" id="ngiVideoModal" role="dialog" aria-modal="true" aria-labelledby="ngiVideoTitle">
    <div class="ngi-modal-card ngi-video-modal"><button class="ngi-close" data-ngi-video-close type="button" aria-label="Close video modal">×</button><h2 id="ngiVideoTitle">NextGen Tutors Story</h2><iframe id="ngiVideoFrame" title="NextGen Tutors video" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>
  </div>

  <div class="ngi-modal" id="ngiBookingModal" role="dialog" aria-modal="true" aria-labelledby="ngiModalTitle">
    <div class="ngi-modal-card"><button class="ngi-close" data-ngi-close type="button" aria-label="Close booking modal">×</button><h2 id="ngiModalTitle">Book Free Assessment</h2><p style="color:var(--ngi-muted)">Connect this area to NextGenCompanion, Fluent Forms or Amelia. Fallback fields are provided for static preview.</p><?php
      if ( shortcode_exists( 'ngc_find_tutor_form' ) ) {
          echo do_shortcode( '[ngc_find_tutor_form]' );
      } elseif ( shortcode_exists( 'fluentform' ) && get_option( 'ngc_fluentform_booking_id' ) ) {
          echo do_shortcode( '[fluentform id="' . absint( get_option( 'ngc_fluentform_booking_id' ) ) . '"]' );
      } else {
          ?>
          <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
              <input type="hidden" name="action" value="ngt_find_tutor" />
              <?php wp_nonce_field( 'ngt_find_tutor', 'ngt_nonce' ); ?>
              <label class="ngi-sr-only" for="ngi_parent_name">Parent name</label>
              <input id="ngi_parent_name" name="parent_name" placeholder="Parent name" required />
              <label class="ngi-sr-only" for="ngi_parent_email">Email address</label>
              <input id="ngi_parent_email" name="email" type="email" placeholder="Email address" required />
              <label class="ngi-sr-only" for="ngi_subject">Subject</label>
              <select id="ngi_subject" name="subject"><option>Mathematics</option><option>Physical Science</option><option>English</option><option>Programming</option></select>
              <button class="ngi-btn ngi-btn-primary" style="width:100%;margin-top:10px" type="submit">Submit Assessment Request</button>
          </form>
          <?php
      }
      ?></div>
  </div>
</div>

<script>
(function(){
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const root = document.querySelector('.ngi-home');
  if(!root) return;
  const revealEls = root.querySelectorAll('.ngi-reveal');
  if(reduced){ revealEls.forEach(el=>el.classList.add('is-in')); }
  const counterSeen = new WeakSet();
  function animateCounter(el){
    if(counterSeen.has(el)) return; counterSeen.add(el);
    const end = Number(el.dataset.count || 0); const suffix = el.dataset.suffix || '+';
    const startTime = performance.now(); const duration = 1250;
    function tick(now){
      const p = Math.min((now-startTime)/duration,1); const eased = 1-Math.pow(1-p,4);
      el.textContent = Math.round(end*eased).toLocaleString() + suffix;
      if(p<1) requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
  }
  const io = new IntersectionObserver(entries=>{
    entries.forEach(entry=>{
      if(entry.isIntersecting){
        entry.target.classList.add('is-in');
        entry.target.querySelectorAll?.('[data-count]').forEach(animateCounter);
      }
    });
  },{threshold:.16});
  revealEls.forEach(el=>io.observe(el));
  root.querySelectorAll('.ngi-stats').forEach(el=>io.observe(el));
  function setBars(score,homework){
    const cb = root.querySelector('#ngiCourseBar'), hb = root.querySelector('#ngiHomeworkBar');
    if(cb) cb.style.width = score + '%'; if(hb) hb.style.width = homework + '%';
  }
  setTimeout(()=>setBars(82,76),250);
  root.querySelectorAll('.ngi-chip').forEach(chip=>{
    chip.addEventListener('click',()=>{
      root.querySelectorAll('.ngi-chip').forEach(c=>c.classList.remove('is-active'));
      chip.classList.add('is-active');
      const course = chip.dataset.course, score = chip.dataset.score, homework = chip.dataset.homework;
      root.querySelector('#ngiCourseName').textContent = course;
      root.querySelector('#ngiCourseScore').textContent = score + '%';
      root.querySelector('#ngiHomeworkScore').textContent = homework + '%';
      setBars(0,0); setTimeout(()=>setBars(score,homework),80);
    });
  });
  root.querySelectorAll('.ngi-tab').forEach(tab=>{
    tab.addEventListener('click',()=>{
      root.querySelectorAll('.ngi-tab').forEach(t=>t.classList.remove('is-active'));
      tab.classList.add('is-active');
      const panel = root.querySelector('.ngi-subject-panel');
      panel.style.opacity = 0; panel.style.transform = 'translateY(12px)';
      setTimeout(()=>{
        root.querySelector('#ngiSubjectTitle').textContent = tab.dataset.title;
        root.querySelector('#ngiSubjectBody').textContent = tab.dataset.body;
        const bullets = (tab.dataset.bullets || '').split('|').filter(Boolean);
        root.querySelector('#ngiSubjectBullets').innerHTML = bullets.map(b=>'<div class="ngi-bullet">'+b.replace(/[<>&]/g, s=>({'<':'&lt;','>':'&gt;','&':'&amp;'}[s]))+'</div>').join('');
        panel.style.transition = '.25s'; panel.style.opacity = 1; panel.style.transform = 'translateY(0)';
      },160);
    });
  });
  root.querySelectorAll('.ngi-faq-q').forEach(btn=>{
    btn.addEventListener('click',()=>{
      const answer = btn.parentElement.querySelector('.ngi-faq-a'); const isOpen = !!answer.style.maxHeight;
      root.querySelectorAll('.ngi-faq-a').forEach(a=>a.style.maxHeight=null);
      root.querySelectorAll('.ngi-faq-q span').forEach(s=>s.textContent='+');
      if(!isOpen){ answer.style.maxHeight = answer.scrollHeight + 'px'; btn.querySelector('span').textContent='−'; }
    });
  });

  // KineticHub Free inspired interactions.
  const mesh = root.querySelector('.ngi-kh-mesh');
  root.addEventListener('pointermove', e=>{
    const r = root.getBoundingClientRect();
    root.style.setProperty('--mx', ((e.clientX-r.left)/Math.max(r.width,1))*100 + '%');
    root.style.setProperty('--my', ((e.clientY-r.top)/Math.max(r.height,1))*100 + '%');
  });
  root.querySelectorAll('.ngi-magnetic').forEach(btn=>{
    btn.addEventListener('pointermove', e=>{
      const b = btn.getBoundingClientRect();
      btn.style.transform = `translate(${(e.clientX-b.left-b.width/2)*.10}px,${(e.clientY-b.top-b.height/2)*.10}px)`;
      btn.style.setProperty('--magx', (e.clientX-b.left-b.width/2)+'px');
      btn.style.setProperty('--magy', (e.clientY-b.top-b.height/2)+'px');
    });
    btn.addEventListener('pointerleave',()=>{btn.style.transform='';btn.style.setProperty('--magx','0');btn.style.setProperty('--magy','0');});
  });
  root.querySelectorAll('.ngi-kinetic-text').forEach(el=>{
    if(reduced) return;
    const words = el.textContent.trim().split(/\s+/);
    el.setAttribute('aria-label', el.textContent.trim());
    el.innerHTML = words.map((w,i)=>`<span style="transition-delay:${i*35}ms">${w}</span>`).join(' ');
  });
  const divider = root.querySelector('#ngiScrollDivider');
  window.addEventListener('scroll',()=>{
    if(!divider) return;
    const max = document.documentElement.scrollHeight - window.innerHeight;
    divider.style.width = Math.min(100, Math.max(0, window.scrollY / Math.max(max,1) * 100)) + '%';
  },{passive:true});
  const baRange = root.querySelector('#ngiBaRange'), baAfter = root.querySelector('#ngiBaAfter');
  baRange?.addEventListener('input',()=>{ baAfter.style.clipPath = `inset(0 0 0 ${baRange.value}%)`; });
  const videoModal = document.getElementById('ngiVideoModal'), videoFrame = document.getElementById('ngiVideoFrame');
  root.querySelector('#ngiOpenVideo')?.addEventListener('click',()=>{
    if(videoFrame && !videoFrame.src) videoFrame.src = 'https://www.youtube.com/embed/dQw4w9WgXcQ?rel=0';
    videoModal?.classList.add('is-open');
  });
  root.querySelectorAll('[data-ngi-video-close]').forEach(btn=>btn.addEventListener('click',()=>{ videoModal?.classList.remove('is-open'); if(videoFrame) videoFrame.src=''; }));
  videoModal?.addEventListener('click',e=>{ if(e.target===videoModal){ videoModal.classList.remove('is-open'); if(videoFrame) videoFrame.src=''; }});
  let audioOn=false, audioTick=null;
  root.querySelector('#ngiAudioToggle')?.addEventListener('click',e=>{
    audioOn=!audioOn; e.currentTarget.textContent=audioOn?'❚❚':'▶';
    const bar=root.querySelector('#ngiAudioBar'); let w=42;
    clearInterval(audioTick);
    if(audioOn){ audioTick=setInterval(()=>{w=(w+7)%100; if(bar) bar.style.width=Math.max(8,w)+'%';},420); }
  });
  root.querySelectorAll('.ngi-cursor-item').forEach(item=>{
    item.addEventListener('pointerenter',()=>{
      root.querySelectorAll('.ngi-cursor-item').forEach(i=>i.classList.remove('is-active'));
      item.classList.add('is-active');
      root.querySelector('#ngiCursorTitle').textContent=item.dataset.title;
      root.querySelector('#ngiCursorCopy').textContent=item.dataset.copy;
    });
  });
  root.querySelector('#ngiCursorPreview')?.addEventListener('pointermove',e=>{
    const b=e.currentTarget.getBoundingClientRect();
    e.currentTarget.style.setProperty('--rx', ((e.clientX-b.left)/b.width)*100+'%');
    e.currentTarget.style.setProperty('--ry', ((e.clientY-b.top)/b.height)*100+'%');
  });

  const modal = document.getElementById('ngiBookingModal');
  root.querySelectorAll('[data-ngi-open]').forEach(btn=>btn.addEventListener('click',()=>modal?.classList.add('is-open')));
  root.querySelectorAll('[data-ngi-close]').forEach(btn=>btn.addEventListener('click',()=>modal?.classList.remove('is-open')));
  modal?.addEventListener('click',e=>{ if(e.target===modal) modal.classList.remove('is-open'); });
})();
</script>
<?php
get_footer();
