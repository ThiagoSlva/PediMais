<!DOCTYPE html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PediMais - Sistema Completo de Cardápio Digital e Delivery</title>
    <meta
      name="description"
      content="Sistema profissional de cardápio digital com WhatsApp, PIX automático, Kanban de pedidos e muito mais. Solução completa para restaurantes e delivery."
    />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
      rel="stylesheet"
    />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
    />
    <style>
      :root {
        --primary: #8b5cf6;
        --primary-dark: #7c3aed;
        --primary-light: #a78bfa;
        --secondary: #06b6d4;
        --accent: #f59e0b;
        --success: #10b981;
        --dark: #0f172a;
        --dark-light: #1e293b;
        --gray: #64748b;
        --light: #f1f5f9;
        --white: #ffffff;
        --gradient-primary: linear-gradient(135deg, #8b5cf6 0%, #06b6d4 100%);
        --gradient-dark: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        --gradient-accent: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%);
      }

      * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
      }

      html {
        scroll-behavior: smooth;
      }

      body {
        font-family: "Inter", sans-serif;
        background: var(--dark);
        color: var(--white);
        overflow-x: hidden;
      }

      /* Animated Background */
      .bg-animated {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: -1;
        background: var(--dark);
        overflow: hidden;
      }

      .bg-animated::before {
        content: "";
        position: absolute;
        width: 600px;
        height: 600px;
        background: radial-gradient(
          circle,
          rgba(139, 92, 246, 0.15) 0%,
          transparent 70%
        );
        top: -200px;
        right: -200px;
        animation: float 20s ease-in-out infinite;
      }

      .bg-animated::after {
        content: "";
        position: absolute;
        width: 500px;
        height: 500px;
        background: radial-gradient(
          circle,
          rgba(6, 182, 212, 0.1) 0%,
          transparent 70%
        );
        bottom: -150px;
        left: -150px;
        animation: float 25s ease-in-out infinite reverse;
      }

      @keyframes float {
        0%,
        100% {
          transform: translate(0, 0) rotate(0deg);
        }
        25% {
          transform: translate(50px, 50px) rotate(5deg);
        }
        50% {
          transform: translate(0, 100px) rotate(0deg);
        }
        75% {
          transform: translate(-50px, 50px) rotate(-5deg);
        }
      }

      /* Container */
      .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 24px;
      }

      /* Navigation */
      .navbar {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1000;
        padding: 20px 0;
        transition: all 0.3s ease;
      }

      .navbar.scrolled {
        background: rgba(15, 23, 42, 0.95);
        backdrop-filter: blur(20px);
        padding: 15px 0;
        box-shadow: 0 4px 30px rgba(0, 0, 0, 0.3);
      }

      .navbar .container {
        display: flex;
        justify-content: space-between;
        align-items: center;
      }

      .logo {
        font-size: 1.8rem;
        font-weight: 800;
        background: var(--gradient-primary);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
      }

      .logo span {
        color: var(--accent);
        -webkit-text-fill-color: var(--accent);
      }

      .nav-links {
        display: flex;
        gap: 35px;
        list-style: none;
      }

      .nav-links a {
        color: var(--light);
        text-decoration: none;
        font-weight: 500;
        font-size: 0.95rem;
        transition: color 0.3s;
      }

      .nav-links a:hover {
        color: var(--primary-light);
      }

      .nav-cta {
        background: var(--gradient-primary);
        color: white;
        padding: 12px 28px;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
        box-shadow: 0 4px 20px rgba(139, 92, 246, 0.4);
      }

      .nav-cta:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 30px rgba(139, 92, 246, 0.5);
      }

      .mobile-toggle {
        display: none;
        background: none;
        border: none;
        color: white;
        font-size: 1.5rem;
        cursor: pointer;
      }

      /* Hero Section */
      .hero {
        min-height: 100vh;
        display: flex;
        align-items: center;
        padding: 120px 0 80px;
        position: relative;
      }

      .hero .container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 60px;
        align-items: center;
      }

      .hero-content h1 {
        font-size: 3.5rem;
        font-weight: 800;
        line-height: 1.1;
        margin-bottom: 24px;
      }

      .hero-content h1 .gradient-text {
        background: var(--gradient-primary);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
      }

      .hero-content p {
        font-size: 1.2rem;
        color: var(--gray);
        margin-bottom: 40px;
        line-height: 1.7;
      }

      .hero-buttons {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
      }

      .btn-primary {
        background: var(--gradient-primary);
        color: white;
        padding: 18px 40px;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 700;
        font-size: 1.1rem;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        transition: all 0.3s;
        box-shadow: 0 8px 30px rgba(139, 92, 246, 0.4);
        border: none;
        cursor: pointer;
      }

      .btn-primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 40px rgba(139, 92, 246, 0.5);
      }

      .btn-secondary {
        background: transparent;
        color: white;
        padding: 18px 40px;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 600;
        font-size: 1.1rem;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        transition: all 0.3s;
        border: 2px solid rgba(255, 255, 255, 0.2);
      }

      .btn-secondary:hover {
        border-color: var(--primary);
        background: rgba(139, 92, 246, 0.1);
      }

      .hero-image {
        position: relative;
      }

      .hero-mockup {
        width: 100%;
        max-width: 500px;
        border-radius: 30px;
        box-shadow: 0 40px 80px rgba(0, 0, 0, 0.5);
        animation: mockupFloat 6s ease-in-out infinite;
      }

      @keyframes mockupFloat {
        0%,
        100% {
          transform: translateY(0);
        }
        50% {
          transform: translateY(-20px);
        }
      }

      .hero-badge {
        position: absolute;
        top: 20px;
        right: -20px;
        background: var(--gradient-accent);
        padding: 15px 25px;
        border-radius: 15px;
        font-weight: 700;
        font-size: 0.9rem;
        box-shadow: 0 10px 30px rgba(245, 158, 11, 0.4);
        animation: pulse 2s ease-in-out infinite;
      }

      @keyframes pulse {
        0%,
        100% {
          transform: scale(1);
        }
        50% {
          transform: scale(1.05);
        }
      }

      /* Stats Section */
      .stats {
        padding: 60px 0;
        background: rgba(30, 41, 59, 0.5);
        backdrop-filter: blur(10px);
      }

      .stats .container {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 40px;
        text-align: center;
      }

      .stat-item h3 {
        font-size: 3rem;
        font-weight: 800;
        background: var(--gradient-primary);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 8px;
      }

      .stat-item p {
        color: var(--gray);
        font-weight: 500;
      }

      /* Features Section */
      .features {
        padding: 120px 0;
      }

      .section-header {
        text-align: center;
        max-width: 700px;
        margin: 0 auto 80px;
      }

      .section-badge {
        display: inline-block;
        background: rgba(139, 92, 246, 0.15);
        color: var(--primary-light);
        padding: 8px 20px;
        border-radius: 50px;
        font-size: 0.85rem;
        font-weight: 600;
        margin-bottom: 20px;
        text-transform: uppercase;
        letter-spacing: 1px;
      }

      .section-header h2 {
        font-size: 2.8rem;
        font-weight: 800;
        margin-bottom: 20px;
      }

      .section-header p {
        color: var(--gray);
        font-size: 1.1rem;
        line-height: 1.7;
      }

      .features-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 30px;
      }

      .feature-card {
        background: var(--gradient-dark);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 24px;
        padding: 40px 30px;
        transition: all 0.4s;
        position: relative;
        overflow: hidden;
      }

      .feature-card::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: var(--gradient-primary);
        transform: scaleX(0);
        transition: transform 0.4s;
      }

      .feature-card:hover {
        transform: translateY(-10px);
        border-color: rgba(139, 92, 246, 0.3);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
      }

      .feature-card:hover::before {
        transform: scaleX(1);
      }

      .feature-icon {
        width: 70px;
        height: 70px;
        background: rgba(139, 92, 246, 0.15);
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        color: var(--primary-light);
        margin-bottom: 25px;
      }

      .feature-card h3 {
        font-size: 1.3rem;
        font-weight: 700;
        margin-bottom: 15px;
      }

      .feature-card p {
        color: var(--gray);
        font-size: 0.95rem;
        line-height: 1.6;
      }

      /* Showcase Section */
      .showcase {
        padding: 120px 0;
        background: linear-gradient(
          180deg,
          transparent 0%,
          rgba(139, 92, 246, 0.05) 50%,
          transparent 100%
        );
      }

      .showcase-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 80px;
        align-items: center;
      }

      .showcase-content h2 {
        font-size: 2.5rem;
        font-weight: 800;
        margin-bottom: 25px;
      }

      .showcase-content p {
        color: var(--gray);
        font-size: 1.1rem;
        line-height: 1.7;
        margin-bottom: 30px;
      }

      .showcase-list {
        list-style: none;
      }

      .showcase-list li {
        display: flex;
        align-items: flex-start;
        gap: 15px;
        margin-bottom: 20px;
        font-size: 1rem;
      }

      .showcase-list li i {
        color: var(--success);
        font-size: 1.2rem;
        margin-top: 3px;
      }

      .showcase-image {
        border-radius: 24px;
        box-shadow: 0 30px 60px rgba(0, 0, 0, 0.4);
        overflow: hidden;
      }

      .showcase-image img {
        width: 100%;
        display: block;
      }

      /* Demo Section */
      .demo {
        padding: 120px 0;
        background: linear-gradient(180deg, rgba(139, 92, 246, 0.08) 0%, transparent 100%);
      }

      .demo-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 40px;
        max-width: 900px;
        margin: 0 auto;
      }

      .demo-card {
        background: var(--gradient-dark);
        border: 2px solid rgba(139, 92, 246, 0.3);
        border-radius: 24px;
        padding: 40px;
        text-align: center;
        transition: all 0.4s;
        position: relative;
        overflow: hidden;
      }

      .demo-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--gradient-primary);
      }

      .demo-card:hover {
        transform: translateY(-10px);
        border-color: var(--primary);
        box-shadow: 0 30px 60px rgba(139, 92, 246, 0.3);
      }

      .demo-icon {
        width: 80px;
        height: 80px;
        background: rgba(139, 92, 246, 0.15);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: var(--primary-light);
        margin: 0 auto 25px;
      }

      .demo-card h3 {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 15px;
      }

      .demo-card p {
        color: var(--gray);
        margin-bottom: 25px;
        line-height: 1.6;
      }

      .demo-credentials {
        background: rgba(0, 0, 0, 0.3);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 25px;
        text-align: left;
      }

      .demo-credentials h4 {
        font-size: 0.85rem;
        color: var(--primary-light);
        margin-bottom: 12px;
        text-transform: uppercase;
        letter-spacing: 1px;
      }

      .demo-credentials .credential {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid rgba(255,255,255,0.08);
      }

      .demo-credentials .credential:last-child {
        border-bottom: none;
      }

      .demo-credentials .label {
        color: var(--gray);
        font-size: 0.9rem;
      }

      .demo-credentials .value {
        font-family: 'Courier New', monospace;
        background: rgba(139, 92, 246, 0.2);
        padding: 4px 12px;
        border-radius: 6px;
        font-size: 0.9rem;
        color: var(--primary-light);
      }

      .btn-demo {
        background: var(--gradient-primary);
        color: white;
        padding: 16px 35px;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 700;
        font-size: 1rem;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        transition: all 0.3s;
        box-shadow: 0 8px 30px rgba(139, 92, 246, 0.4);
      }

      .btn-demo:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 40px rgba(139, 92, 246, 0.5);
      }

      .btn-demo-secondary {
        background: rgba(6, 182, 212, 0.15);
        border: 2px solid var(--secondary);
        color: var(--secondary);
        padding: 16px 35px;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 700;
        font-size: 1rem;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        transition: all 0.3s;
      }

      .btn-demo-secondary:hover {
        background: var(--secondary);
        color: white;
        transform: translateY(-3px);
        box-shadow: 0 12px 40px rgba(6, 182, 212, 0.4);
      }

      @media (max-width: 768px) {
        .demo-grid {
          grid-template-columns: 1fr;
        }
      }

      /* Pricing Section */
      .pricing {
        padding: 120px 0;
      }

      .pricing-card {
        max-width: 600px;
        margin: 0 auto;
        background: var(--gradient-dark);
        border: 2px solid rgba(139, 92, 246, 0.3);
        border-radius: 32px;
        padding: 50px;
        text-align: center;
        position: relative;
        overflow: hidden;
      }

      .pricing-card::before {
        content: "";
        position: absolute;
        top: -100px;
        right: -100px;
        width: 300px;
        height: 300px;
        background: radial-gradient(
          circle,
          rgba(139, 92, 246, 0.2) 0%,
          transparent 70%
        );
      }

      .pricing-popular {
        position: absolute;
        top: 30px;
        right: -35px;
        background: var(--gradient-accent);
        padding: 8px 50px;
        font-size: 0.8rem;
        font-weight: 700;
        transform: rotate(45deg);
      }

      .pricing-card h3 {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 10px;
      }

      .pricing-card .price {
        font-size: 4rem;
        font-weight: 800;
        margin: 30px 0;
        background: var(--gradient-primary);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
      }

      .pricing-card .price span {
        font-size: 1.5rem;
        color: var(--gray);
        -webkit-text-fill-color: var(--gray);
      }

      .pricing-features {
        list-style: none;
        margin: 40px 0;
        text-align: left;
      }

      .pricing-features li {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        font-size: 1rem;
      }

      .pricing-features li:last-child {
        border-bottom: none;
      }

      .pricing-features li i {
        color: var(--success);
        font-size: 1.1rem;
      }

      .pricing-cta {
        width: 100%;
        padding: 20px;
        font-size: 1.2rem;
      }

      /* Testimonials */
      .testimonials {
        padding: 120px 0;
        background: rgba(30, 41, 59, 0.3);
      }

      .testimonials-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 30px;
      }

      .testimonial-card {
        background: var(--dark-light);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 20px;
        padding: 35px;
      }

      .testimonial-stars {
        color: var(--accent);
        margin-bottom: 20px;
      }

      .testimonial-card p {
        color: var(--light);
        font-size: 1rem;
        line-height: 1.7;
        margin-bottom: 25px;
      }

      .testimonial-author {
        display: flex;
        align-items: center;
        gap: 15px;
      }

      .testimonial-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: var(--gradient-primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.2rem;
      }

      .testimonial-info h4 {
        font-weight: 600;
        margin-bottom: 4px;
      }

      .testimonial-info span {
        color: var(--gray);
        font-size: 0.9rem;
      }

      /* FAQ Section */
      .faq {
        padding: 120px 0;
      }

      .faq-grid {
        max-width: 800px;
        margin: 0 auto;
      }

      .faq-item {
        background: var(--dark-light);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 16px;
        margin-bottom: 15px;
        overflow: hidden;
      }

      .faq-question {
        padding: 25px 30px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 600;
        font-size: 1.05rem;
        transition: background 0.3s;
      }

      .faq-question:hover {
        background: rgba(139, 92, 246, 0.1);
      }

      .faq-question i {
        color: var(--primary);
        transition: transform 0.3s;
      }

      .faq-item.active .faq-question i {
        transform: rotate(180deg);
      }

      .faq-answer {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease;
      }

      .faq-item.active .faq-answer {
        max-height: 500px;
      }

      .faq-answer p {
        padding: 0 30px 25px;
        color: var(--gray);
        line-height: 1.7;
      }

      /* CTA Section */
      .cta {
        padding: 120px 0;
        text-align: center;
        background: linear-gradient(
          180deg,
          transparent 0%,
          rgba(139, 92, 246, 0.1) 100%
        );
      }

      .cta h2 {
        font-size: 3rem;
        font-weight: 800;
        margin-bottom: 20px;
      }

      .cta p {
        color: var(--gray);
        font-size: 1.2rem;
        max-width: 600px;
        margin: 0 auto 40px;
      }

      .cta-buttons {
        display: flex;
        gap: 20px;
        justify-content: center;
        flex-wrap: wrap;
      }

      .btn-whatsapp {
        background: #25d366;
        color: white;
        padding: 18px 40px;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 700;
        font-size: 1.1rem;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        transition: all 0.3s;
        box-shadow: 0 8px 30px rgba(37, 211, 102, 0.4);
      }

      .btn-whatsapp:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 40px rgba(37, 211, 102, 0.5);
      }

      /* Footer */
      .footer {
        padding: 60px 0 30px;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
      }

      .footer-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
      }

      .footer-logo {
        font-size: 1.5rem;
        font-weight: 800;
        background: var(--gradient-primary);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
      }

      .footer-links {
        display: flex;
        gap: 30px;
      }

      .footer-links a {
        color: var(--gray);
        text-decoration: none;
        transition: color 0.3s;
      }

      .footer-links a:hover {
        color: var(--primary-light);
      }

      .footer-copy {
        text-align: center;
        padding-top: 30px;
        margin-top: 30px;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
        color: var(--gray);
        font-size: 0.9rem;
      }

      /* Mobile Menu */
      .mobile-menu {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(15, 23, 42, 0.98);
        backdrop-filter: blur(20px);
        z-index: 999;
        padding: 100px 30px;
      }

      .mobile-menu.active {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 30px;
      }

      .mobile-menu a {
        color: white;
        text-decoration: none;
        font-size: 1.5rem;
        font-weight: 600;
      }

      /* Responsive */
      @media (max-width: 1024px) {
        .hero .container {
          grid-template-columns: 1fr;
          text-align: center;
        }

        .hero-content h1 {
          font-size: 2.8rem;
        }

        .hero-buttons {
          justify-content: center;
        }

        .hero-image {
          max-width: 400px;
          margin: 0 auto;
        }

        .features-grid {
          grid-template-columns: repeat(2, 1fr);
        }

        .showcase-grid {
          grid-template-columns: 1fr;
          gap: 50px;
        }

        .testimonials-grid {
          grid-template-columns: 1fr;
        }

        .stats .container {
          grid-template-columns: repeat(2, 1fr);
        }
      }

      @media (max-width: 768px) {
        .nav-links,
        .nav-cta {
          display: none;
        }

        .mobile-toggle {
          display: block;
        }

        .hero-content h1 {
          font-size: 2.2rem;
        }

        .hero-content p {
          font-size: 1rem;
        }

        .section-header h2 {
          font-size: 2rem;
        }

        .features-grid {
          grid-template-columns: 1fr;
        }

        .pricing-card {
          padding: 35px 25px;
        }

        .pricing-card .price {
          font-size: 3rem;
        }

        .cta h2 {
          font-size: 2rem;
        }

        .footer-content {
          flex-direction: column;
          text-align: center;
        }
      }
    </style>
  </head>
  <body>
    <div class="bg-animated"></div>

    <!-- Mobile Menu -->
    <div class="mobile-menu" id="mobileMenu">
      <a href="#features" onclick="closeMobileMenu()">Funcionalidades</a>
      <a href="#pricing" onclick="closeMobileMenu()">Preços</a>
      <a href="#faq" onclick="closeMobileMenu()">FAQ</a>
      <a href="#contact" class="btn-primary" onclick="closeMobileMenu()"
        >Comprar Agora</a
      >
    </div>

    <!-- Navigation -->
    <nav class="navbar" id="navbar">
      <div class="container">
        <div class="logo">Pedi<span>Mais</span></div>
        <ul class="nav-links">
          <li><a href="#features">Funcionalidades</a></li>
          <li><a href="#demo">Demo</a></li>
          <li><a href="#showcase">Demonstração</a></li>
          <li><a href="#pricing">Preços</a></li>
          <li><a href="#faq">FAQ</a></li>
        </ul>
        <a href="#contact" class="nav-cta">Comprar Agora</a>
        <button class="mobile-toggle" onclick="toggleMobileMenu()">
          <i class="fas fa-bars" id="menuIcon"></i>
        </button>
      </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
      <div class="container">
        <div class="hero-content">
          <h1>
            Transforme seu Delivery com o
            <span class="gradient-text">Sistema Mais Completo</span> do Mercado
          </h1>
          <p>
            Cardápio digital premium com WhatsApp integrado, PIX automático,
            Kanban de pedidos e muito mais. Tudo que você precisa para aumentar
            suas vendas e encantar seus clientes.
          </p>
          <div class="hero-buttons">
            <a href="#pricing" class="btn-primary">
              <i class="fas fa-rocket"></i> Adquirir Agora
            </a>
            <a href="#showcase" class="btn-secondary">
              <i class="fas fa-play-circle"></i> Ver Demo
            </a>
          </div>
        </div>
        <div class="hero-image">
          <div class="hero-badge">🔥 Oferta Limitada!</div>
          <img
            src="https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=500&h=700&fit=crop"
            alt="PediMais Demo"
            class="hero-mockup"
            style="object-fit: cover"
          />
        </div>
      </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
      <div class="container">
        <div class="stat-item">
          <h3>500+</h3>
          <p>Clientes Satisfeitos</p>
        </div>
        <div class="stat-item">
          <h3>50k+</h3>
          <p>Pedidos Processados</p>
        </div>
        <div class="stat-item">
          <h3>99%</h3>
          <p>Uptime Garantido</p>
        </div>
        <div class="stat-item">
          <h3>24/7</h3>
          <p>Suporte Técnico</p>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
      <div class="container">
        <div class="section-header">
          <span class="section-badge">✨ Funcionalidades</span>
          <h2>Tudo que Seu Delivery Precisa</h2>
          <p>
            Um sistema completo com as ferramentas mais modernas do mercado para
            você vender mais e melhor.
          </p>
        </div>
        <div class="features-grid">
          <div class="feature-card">
            <div class="feature-icon">
              <i class="fab fa-whatsapp"></i>
            </div>
            <h3>WhatsApp Integrado</h3>
            <p>
              Notificações automáticas, conexão por QR Code e mensagens
              personalizadas via Evolution API.
            </p>
          </div>
          <div class="feature-card">
            <div class="feature-icon">
              <i class="fas fa-qrcode"></i>
            </div>
            <h3>PIX Automático</h3>
            <p>
              Integração com Mercado Pago. Geração de QR Code PIX e confirmação
              automática de pagamento.
            </p>
          </div>
          <div class="feature-card">
            <div class="feature-icon">
              <i class="fas fa-columns"></i>
            </div>
            <h3>Kanban de Pedidos</h3>
            <p>
              Gestão visual estilo Trello com drag-and-drop, notificações
              sonoras e atualização em tempo real.
            </p>
          </div>
          <div class="feature-card">
            <div class="feature-icon">
              <i class="fas fa-mobile-alt"></i>
            </div>
            <h3>Design Responsivo</h3>
            <p>
              Interface premium com tema escuro/claro, glassmorphism e animações
              modernas.
            </p>
          </div>
          <div class="feature-card">
            <div class="feature-icon">
              <i class="fas fa-gift"></i>
            </div>
            <h3>Programa de Fidelidade</h3>
            <p>
              Sistema de pontos, recompensas personalizadas e gestão completa de
              clientes fiéis.
            </p>
          </div>
          <div class="feature-card">
            <div class="feature-icon">
              <i class="fas fa-truck"></i>
            </div>
            <h3>Gestão de Entregas</h3>
            <p>
              Taxa por bairro, valor fixo, entrega grátis e múltiplas formas de
              configuração.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Demo Section -->
    <section class="demo" id="demo">
      <div class="container">
        <div class="section-header">
          <span class="section-badge">🎯 Teste Grátis</span>
          <h2>Experimente Antes de Comprar</h2>
          <p>Acesse nosso sistema de demonstração e veja na prática todas as funcionalidades. Teste à vontade!</p>
        </div>
        <div class="demo-grid">
          <!-- Cardápio Demo -->
          <div class="demo-card">
            <div class="demo-icon">
              <i class="fas fa-utensils"></i>
            </div>
            <h3>Cardápio Digital</h3>
            <p>Veja como seus clientes irão visualizar o cardápio, adicionar produtos ao carrinho e finalizar pedidos.</p>
            <a href="https://conhecapedimais.shopdix.com.br/" target="_blank" class="btn-demo-secondary">
              <i class="fas fa-external-link-alt"></i> Acessar Cardápio
            </a>
          </div>
          <!-- Admin Demo -->
          <div class="demo-card">
            <div class="demo-icon">
              <i class="fas fa-cogs"></i>
            </div>
            <h3>Painel Administrativo</h3>
            <p>Explore o painel completo: dashboard, produtos, pedidos, configurações e muito mais.</p>
            <div class="demo-credentials">
              <h4>🔐 Dados de Acesso</h4>
              <div class="credential">
                <span class="label">E-mail:</span>
                <span class="value">atendimento@shopdix.com.br</span>
              </div>
              <div class="credential">
                <span class="label">Senha:</span>
                <span class="value">12345678</span>
              </div>
            </div>
            <a href="https://conhecapedimais.shopdix.com.br/admin" target="_blank" class="btn-demo">
              <i class="fas fa-sign-in-alt"></i> Acessar Painel
            </a>
          </div>
        </div>
      </div>
    </section>

    <!-- Showcase Section -->
    <section class="showcase" id="showcase">
      <div class="container">
        <div class="showcase-grid">
          <div class="showcase-content">
            <span class="section-badge">📱 Painel Moderno</span>
            <h2>Gerencie Tudo em Um Só Lugar</h2>
            <p>
              Painel administrativo completo com dashboard, gestão de produtos,
              pedidos, clientes, horários e muito mais. Tudo com interface
              intuitiva e moderna.
            </p>
            <ul class="showcase-list">
              <li>
                <i class="fas fa-check-circle"></i>
                <span>Dashboard com métricas em tempo real</span>
              </li>
              <li>
                <i class="fas fa-check-circle"></i>
                <span>Gestão completa de produtos e categorias</span>
              </li>
              <li>
                <i class="fas fa-check-circle"></i>
                <span>Múltiplos níveis de usuários</span>
              </li>
              <li>
                <i class="fas fa-check-circle"></i>
                <span>Configurações personalizáveis</span>
              </li>
              <li>
                <i class="fas fa-check-circle"></i>
                <span>Impressão térmica de pedidos</span>
              </li>
            </ul>
          </div>
          <div class="showcase-image">
            <img
              src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=600&h=400&fit=crop"
              alt="Dashboard PediMais"
            />
          </div>
        </div>
      </div>
    </section>

    <!-- Pricing Section -->
    <section class="pricing" id="pricing">
      <div class="container">
        <div class="section-header">
          <span class="section-badge">💰 Investimento</span>
          <h2>Preço Único, Sem Mensalidades</h2>
          <p>
            Adquira o sistema completo e use para sempre. Sem taxas recorrentes
            ou custos escondidos.
          </p>
        </div>
        <div class="pricing-card">
          <div class="pricing-popular">MAIS VENDIDO</div>
          <h3>Licença Vitalícia</h3>
          <div class="price">R$497 <span>à vista</span></div>
          <ul class="pricing-features">
            <li><i class="fas fa-check"></i> Código fonte completo</li>
            <li><i class="fas fa-check"></i> Banco de dados pronto</li>
            <li><i class="fas fa-check"></i> Integração WhatsApp</li>
            <li><i class="fas fa-check"></i> Integração Mercado Pago PIX</li>
            <li><i class="fas fa-check"></i> Painel administrativo completo</li>
            <li><i class="fas fa-check"></i> Área do cliente</li>
            <li><i class="fas fa-check"></i> Kanban de pedidos</li>
            <li><i class="fas fa-check"></i> Sistema de fidelidade</li>
            <li><i class="fas fa-check"></i> Tema escuro/claro</li>
            <li><i class="fas fa-check"></i> Suporte via WhatsApp</li>
            <li><i class="fas fa-check"></i> Atualizações futuras</li>
          </ul>
          <a href="#contact" class="btn-primary pricing-cta">
            <i class="fas fa-shopping-cart"></i> Comprar Agora
          </a>
        </div>
      </div>
    </section>

    <!-- Testimonials -->
    <section class="testimonials">
      <div class="container">
        <div class="section-header">
          <span class="section-badge">⭐ Depoimentos</span>
          <h2>O Que Nossos Clientes Dizem</h2>
        </div>
        <div class="testimonials-grid">
          <div class="testimonial-card">
            <div class="testimonial-stars">
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
            </div>
            <p>
              "Melhor investimento que fiz! O sistema é completo e o suporte é
              excelente. Meus pedidos aumentaram 40% no primeiro mês."
            </p>
            <div class="testimonial-author">
              <div class="testimonial-avatar">JM</div>
              <div class="testimonial-info">
                <h4>João Marcos</h4>
                <span>Pizzaria do João</span>
              </div>
            </div>
          </div>
          <div class="testimonial-card">
            <div class="testimonial-stars">
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
            </div>
            <p>
              "O Kanban de pedidos mudou minha vida! Agora tenho controle total
              da cozinha e os pedidos não atrasam mais."
            </p>
            <div class="testimonial-author">
              <div class="testimonial-avatar">AS</div>
              <div class="testimonial-info">
                <h4>Ana Silva</h4>
                <span>Burger House</span>
              </div>
            </div>
          </div>
          <div class="testimonial-card">
            <div class="testimonial-stars">
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
            </div>
            <p>
              "A integração com WhatsApp é perfeita! Meus clientes amam receber
              as notificações automáticas dos pedidos."
            </p>
            <div class="testimonial-author">
              <div class="testimonial-avatar">RC</div>
              <div class="testimonial-info">
                <h4>Roberto Costa</h4>
                <span>Sushi Express</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq" id="faq">
      <div class="container">
        <div class="section-header">
          <span class="section-badge">❓ Dúvidas</span>
          <h2>Perguntas Frequentes</h2>
        </div>
        <div class="faq-grid">
          <div class="faq-item">
            <div class="faq-question" onclick="toggleFaq(this)">
              <span>O sistema é de compra única ou tem mensalidade?</span>
              <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
              <p>
                Compra única! Você paga uma vez e usa para sempre. Não há taxas
                mensais, anuais ou custos escondidos.
              </p>
            </div>
          </div>
          <div class="faq-item">
            <div class="faq-question" onclick="toggleFaq(this)">
              <span>Preciso de conhecimento técnico para instalar?</span>
              <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
              <p>
                Conhecimentos básicos de hospedagem são recomendados. O sistema
                vem com documentação completa e oferecemos suporte para ajudar
                na instalação.
              </p>
            </div>
          </div>
          <div class="faq-item">
            <div class="faq-question" onclick="toggleFaq(this)">
              <span>Quais os requisitos de hospedagem?</span>
              <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
              <p>
                PHP 8.0+, MySQL 5.7+, HTTPS habilitado (SSL), cURL ativo e
                permissões de escrita. Qualquer hospedagem compartilhada de
                qualidade atende.
              </p>
            </div>
          </div>
          <div class="faq-item">
            <div class="faq-question" onclick="toggleFaq(this)">
              <span>O WhatsApp e PIX estão incluídos?</span>
              <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
              <p>
                Sim! A integração com WhatsApp (Evolution API) e Mercado Pago
                PIX estão 100% inclusas. Você só precisa configurar suas
                credenciais.
              </p>
            </div>
          </div>
          <div class="faq-item">
            <div class="faq-question" onclick="toggleFaq(this)">
              <span>Posso personalizar o sistema?</span>
              <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
              <p>
                Sim! Você recebe o código fonte completo e pode personalizar
                cores, logo, funcionalidades e tudo mais que precisar.
              </p>
            </div>
          </div>
          <div class="faq-item">
            <div class="faq-question" onclick="toggleFaq(this)">
              <span>Vocês oferecem suporte?</span>
              <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
              <p>
                Sim! Oferecemos suporte via WhatsApp para dúvidas de instalação
                e configuração. Estamos sempre prontos para ajudar.
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="cta" id="contact">
      <div class="container">
        <h2>Pronto Para Revolucionar Seu Delivery?</h2>
        <p>
          Junte-se a centenas de estabelecimentos que já aumentaram suas vendas
          com o PediMais.
        </p>
        <div class="cta-buttons">
          <a
            href="https://wa.me/5500000000000?text=Olá! Tenho interesse no sistema PediMais"
            class="btn-whatsapp"
            target="_blank"
          >
            <i class="fab fa-whatsapp"></i> Falar no WhatsApp
          </a>
          <a href="#pricing" class="btn-primary">
            <i class="fas fa-shopping-cart"></i> Comprar Agora
          </a>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
      <div class="container">
        <div class="footer-content">
          <div class="footer-logo">PediMais</div>
          <div class="footer-links">
            <a href="#features">Funcionalidades</a>
            <a href="#pricing">Preços</a>
            <a href="#faq">FAQ</a>
            <a href="#contact">Contato</a>
          </div>
        </div>
        <div class="footer-copy">
          © 2026 PediMais. Todos os direitos reservados.
        </div>
      </div>
    </footer>

    <script>
      // Navbar scroll effect
      window.addEventListener("scroll", function () {
        const navbar = document.getElementById("navbar");
        if (window.scrollY > 50) {
          navbar.classList.add("scrolled");
        } else {
          navbar.classList.remove("scrolled");
        }
      });

      // Mobile menu toggle
      function toggleMobileMenu() {
        const menu = document.getElementById("mobileMenu");
        const icon = document.getElementById("menuIcon");
        menu.classList.toggle("active");
        icon.classList.toggle("fa-bars");
        icon.classList.toggle("fa-times");
      }

      function closeMobileMenu() {
        const menu = document.getElementById("mobileMenu");
        const icon = document.getElementById("menuIcon");
        menu.classList.remove("active");
        icon.classList.add("fa-bars");
        icon.classList.remove("fa-times");
      }

      // FAQ toggle
      function toggleFaq(element) {
        const faqItem = element.parentElement;
        faqItem.classList.toggle("active");
      }

      // Smooth scroll for anchor links
      document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
        anchor.addEventListener("click", function (e) {
          e.preventDefault();
          const target = document.querySelector(this.getAttribute("href"));
          if (target) {
            target.scrollIntoView({
              behavior: "smooth",
              block: "start",
            });
          }
        });
      });
    </script>
  </body>
</html>
