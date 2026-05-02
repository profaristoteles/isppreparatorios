<?php
$site_config = $site_config ?? get_config($pdo);
?>
    </main><!-- fim #main-content -->
    <footer role="contentinfo" style="padding: 0; padding-bottom: 5rem; background: linear-gradient(180deg, var(--obsidian-deep) 0%, #010130 100%); border-top: 1px solid var(--glass-border);">
        <!-- Footer Principal em Colunas -->
        <div class="footer-grid" style="max-width: 1200px; margin: 0 auto; padding: 4rem 5% 3rem; display: grid; grid-template-columns: repeat(4, 1fr); gap: 3rem;">
            
            <!-- Coluna 1: Sobre -->
            <div>
                <?php if(file_exists('uploads/logo.png')): ?>
                    <a href="index.php"><img src="uploads/logo.png" alt="Logo ISP" style="max-height: 60px; margin-bottom: 1rem; mix-blend-mode: screen;"></a>
                <?php else: ?>
                    <h4 style="color: var(--brand-orange); font-size: 1.1rem; font-weight: 700; margin-bottom: 1.2rem; text-transform: uppercase; letter-spacing: 1px;">ISP Preparatórios</h4>
                <?php endif; ?>
                <p style="color: var(--text-secondary); font-size: 0.95rem; line-height: 1.7;">
                    <?= htmlspecialchars($site_config['footer_text'] ?? 'Preparando você para o futuro.') ?>
                </p>
                <p style="color: var(--text-secondary); font-size: 0.85rem; line-height: 1.7; margin-top: 0.8rem;">
                    <strong style="color: rgba(255,255,255,0.5);">CNPJ:</strong> 28.335.828/0001-10<br>
                    Caxias — MA
                </p>
                <div style="margin-top: 1.2rem; display: flex; gap: 0.8rem;">
                    <?php if(!empty($site_config['facebook'])): ?>
                        <a href="<?= htmlspecialchars($site_config['facebook']) ?>" target="_blank" class="footer-social-icon">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                    <?php endif; ?>
                    <?php if(!empty($site_config['instagram'])): ?>
                        <a href="<?= htmlspecialchars($site_config['instagram']) ?>" target="_blank" class="footer-social-icon">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                        </a>
                    <?php endif; ?>
                    <?php if(!empty($site_config['youtube'])): ?>
                        <a href="<?= htmlspecialchars($site_config['youtube']) ?>" target="_blank" class="footer-social-icon">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                        </a>
                    <?php endif; ?>
                    <?php if(!empty($site_config['tiktok'])): ?>
                        <a href="<?= htmlspecialchars($site_config['tiktok']) ?>" target="_blank" class="footer-social-icon">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.9-.32-1.98-.23-2.81.33-.85.51-1.44 1.43-1.58 2.41-.16 1.02.16 2.07.82 2.85.65.81 1.68 1.3 2.7 1.3 1.15-.03 2.25-.63 2.82-1.63.3-.47.48-1.01.49-1.57-.02-4.37-.03-8.73-.03-13.1z"/></svg>
                        </a>
                    <?php endif; ?>
                </div>
            </div>


            <!-- Coluna 2: Navegação -->
            <div>
                <h4 style="color: #fff; font-size: 1.1rem; font-weight: 700; margin-bottom: 1.2rem; text-transform: uppercase; letter-spacing: 1px;">Navegação</h4>
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.8rem;">
                    <li><a href="index.php" style="color: var(--text-secondary); text-decoration: none; font-size: 0.95rem; transition: color 0.3s;">Início</a></li>
                    <li><a href="cursos.php" style="color: var(--text-secondary); text-decoration: none; font-size: 0.95rem; transition: color 0.3s;">Cursos</a></li>
                    <li><a href="apostilas.php" style="color: var(--text-secondary); text-decoration: none; font-size: 0.95rem; transition: color 0.3s;">Apostilas</a></li>
                    <li><a href="blog.php" style="color: var(--text-secondary); text-decoration: none; font-size: 0.95rem; transition: color 0.3s;">Blog</a></li>
                    <li><a href="contato.php" style="color: var(--text-secondary); text-decoration: none; font-size: 0.95rem; transition: color 0.3s;">Contato</a></li>
                </ul>
            </div>

            <!-- Coluna 3: Legal -->
            <div>
                <h4 style="color: #fff; font-size: 1.1rem; font-weight: 700; margin-bottom: 1.2rem; text-transform: uppercase; letter-spacing: 1px;">Legal</h4>
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.8rem;">
                    <li><a href="privacidade.php" style="color: var(--text-secondary); text-decoration: none; font-size: 0.95rem; transition: color 0.3s;">Política de Privacidade</a></li>
                    <li><a href="termos.php" style="color: var(--text-secondary); text-decoration: none; font-size: 0.95rem; transition: color 0.3s;">Termos de Uso</a></li>
                </ul>
            </div>

            <!-- Coluna 4: Contato -->
            <div>
                <h4 style="color: #fff; font-size: 1.1rem; font-weight: 700; margin-bottom: 1.2rem; text-transform: uppercase; letter-spacing: 1px;">Contato</h4>
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 1rem;">
                    <?php if(!empty($site_config['phone'])): ?>
                    <li>
                        <a href="https://wa.me/55<?= preg_replace('/[^0-9]/', '', $site_config['phone']) ?>" target="_blank" class="footer-whatsapp" style="font-size: 0.95rem;">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/>
                            </svg>
                            <?= htmlspecialchars($site_config['phone']) ?>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if(!empty($site_config['email'])): ?>
                    <li>
                        <a href="mailto:<?= htmlspecialchars($site_config['email']) ?>" style="color: var(--text-secondary); text-decoration: none; font-size: 0.95rem; display: flex; align-items: center; gap: 0.5rem; transition: color 0.3s;">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                            <?= htmlspecialchars($site_config['email']) ?>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>

        </div>

        <!-- Linha Inferior (Copyright) -->
        <div style="width: 100%; max-width: 1200px; margin: 0 auto; padding: 2rem 5% 0; border-top: 1px solid var(--glass-border);">
            <p style="text-align: center; color: rgba(255,255,255,0.3); font-size: 0.85rem; margin: 0;">
                © <?= date('Y') ?> ISP Preparatórios. Todos os direitos reservados.
            </p>
        </div>
    </footer>



    <!-- LGPD Banner -->
    <div id="lgpd-banner" style="display:none; position: fixed; bottom: 0; left: 0; width: 100%; background: #02023a; border-top: 1px solid var(--prism-cyan); padding: 1.5rem 5%; z-index: 9999; flex-direction: column; align-items: center; justify-content: center; text-align: center; gap: 1rem; box-shadow: 0 -10px 30px rgba(0,0,0,0.5);">
        <p style="color: #ccc; font-size: 0.9rem; margin: 0; max-width: 800px; line-height: 1.5;">
            Utilizamos cookies e tecnologias semelhantes para melhorar a sua experiência em nossos serviços. Ao continuar navegando, você concorda com a nossa <a href="privacidade.php" style="color: var(--brand-orange); text-decoration: underline;">Política de Privacidade</a> e <a href="termos.php" style="color: var(--brand-orange); text-decoration: underline;">Termos de Uso</a>.
        </p>
        <button onclick="acceptCookies()" class="btn" style="padding: 0.5rem 2rem; font-size: 0.9rem;">Estou Ciente e Aceito</button>
    </div>

    <script src="js/main.js"></script>
    <script>
        // LGPD Logic
        const lgpdBanner = document.getElementById('lgpd-banner');
        const a11yWidget = document.getElementById('accessibility-widget');

        function adjustFloatingWidgets() {
            if (!lgpdBanner) return;
            const bannerVisible = lgpdBanner.style.display !== 'none';
            const bannerH = bannerVisible ? lgpdBanner.offsetHeight : 0;

            // Empurra o widget de acessibilidade para cima quando o banner está visível
            if (a11yWidget) {
                a11yWidget.style.bottom = (bannerH + 12) + 'px';
            }

            // Empurra o chat widget (LeadConnector) para cima quando o banner está visível
            // O chat usa shadow DOM, então usamos CSS variable/override via elemento pai
            const chatOffset = bannerH + 16;
            let styleEl = document.getElementById('chat-offset-style');
            if (!styleEl) {
                styleEl = document.createElement('style');
                styleEl.id = 'chat-offset-style';
                document.head.appendChild(styleEl);
            }
            if (bannerVisible) {
                styleEl.textContent = `
                    #lc_chat_layout,
                    [id*="chat-widget"],
                    iframe[src*="leadconnectorhq"],
                    iframe[src*="widgets.leadconnectorhq"],
                    div[class*="chat-widget"],
                    div[id*="lc_chat"] {
                        bottom: ${chatOffset}px !important;
                    }
                `;
            } else {
                styleEl.textContent = '';
            }
        }

        if (!localStorage.getItem('lgpd_accepted')) {
            lgpdBanner.style.display = 'flex';
            if (window.innerWidth > 768) {
                lgpdBanner.style.flexDirection = 'row';
                lgpdBanner.style.justifyContent = 'space-between';
                lgpdBanner.style.textAlign = 'left';
            }
            // Pequeno delay para garantir que o banner foi renderizado e tem altura
            requestAnimationFrame(() => adjustFloatingWidgets());
        } else {
            adjustFloatingWidgets();
        }

        function acceptCookies() {
            localStorage.setItem('lgpd_accepted', 'true');
            lgpdBanner.style.display = 'none';
            adjustFloatingWidgets();
        }

        // Reveal animation on scroll
        const observerOptions = {
            threshold: 0.1
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

        // Subtle mouse tracking for "Prismatic" effect on feature cards
        document.querySelectorAll('.feature-card').forEach(card => {
            card.addEventListener('mousemove', e => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                card.style.background = `radial-gradient(circle at ${x}px ${y}px, rgba(255,128,0,0.05) 0%, var(--obsidian-surface) 50%)`;
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.background = 'var(--obsidian-surface)';
            });
        });
    </script>
    <!-- Script de Acessibilidade -->
    <script>
    (function() {
        // Toggle do painel
        const toggleBtn = document.getElementById('a11y-toggle');
        const panel = document.getElementById('a11y-panel');
        if (toggleBtn && panel) {
            toggleBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                const isOpen = panel.classList.toggle('active');
                panel.setAttribute('aria-hidden', !isOpen);
                toggleBtn.setAttribute('aria-expanded', isOpen);
            });
            // Impedir que cliques dentro do painel o fechem
            panel.addEventListener('click', function(e) {
                e.stopPropagation();
            });
            // Fechar ao clicar fora
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.a11y-widget')) {
                    panel.classList.remove('active');
                    panel.setAttribute('aria-hidden', 'true');
                    toggleBtn.setAttribute('aria-expanded', 'false');
                }
            });
        }

        // Tamanho da fonte
        let fontStep = parseInt(localStorage.getItem('a11y_fontStep') || '0');
        applyFontSize(fontStep);

        window.changeFontSize = function(dir) {
            if (dir === 0) { fontStep = 0; }
            else { fontStep = Math.max(0, Math.min(3, fontStep + dir)); }
            localStorage.setItem('a11y_fontStep', fontStep);
            applyFontSize(fontStep);
        };

        function applyFontSize(step) {
            document.body.classList.remove('font-size-1', 'font-size-2', 'font-size-3');
            if (step > 0) document.body.classList.add('font-size-' + step);
        }

        // Alto contraste
        if (localStorage.getItem('a11y_contrast') === 'true') {
            document.body.classList.add('high-contrast');
            updateBtn('btn-contrast', true);
        }
        window.toggleHighContrast = function() {
            const active = document.body.classList.toggle('high-contrast');
            localStorage.setItem('a11y_contrast', active);
            updateBtn('btn-contrast', active);
        };

        // Destacar links
        if (localStorage.getItem('a11y_links') === 'true') {
            document.body.classList.add('highlight-links');
            updateBtn('btn-links', true);
        }
        window.toggleLinkHighlight = function() {
            const active = document.body.classList.toggle('highlight-links');
            localStorage.setItem('a11y_links', active);
            updateBtn('btn-links', active);
        };

        // Fonte legível
        if (localStorage.getItem('a11y_font') === 'true') {
            document.body.classList.add('readable-font');
            updateBtn('btn-font', true);
        }
        window.toggleReadableFont = function() {
            const active = document.body.classList.toggle('readable-font');
            localStorage.setItem('a11y_font', active);
            updateBtn('btn-font', active);
        };

        function updateBtn(id, active) {
            const btn = document.getElementById(id);
            if (btn) btn.classList.toggle('active-option', active);
        }
    })();
    </script>
    <!-- Chat Widget -->
    <script src="https://widgets.leadconnectorhq.com/loader.js" data-resources-url="https://widgets.leadconnectorhq.com/chat-widget/loader.js" data-widget-id="69efcc19f4f51d2b46569198"></script>
</body>
</html>
